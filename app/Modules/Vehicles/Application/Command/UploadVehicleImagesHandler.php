<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Data\RenderedUploadResponse;
use App\Modules\Vehicles\Application\Port\Clock;
use App\Modules\Vehicles\Application\Port\MediaStore;
use App\Modules\Vehicles\Application\Port\TransactionRunner;
use App\Modules\Vehicles\Application\Port\UploadedImagesResponse;
use App\Modules\Vehicles\Application\Port\UploadReplayStore;
use App\Modules\Vehicles\Application\Port\VehicleReadGateway;
use App\Modules\Vehicles\Application\Port\VehicleWriteGateway;
use App\Modules\Vehicles\Application\Result\UploadVehicleImagesError;
use App\Modules\Vehicles\Application\Result\UploadVehicleImagesResult;
use App\Modules\Vehicles\Domain\Model\VehicleError;
use App\Modules\Vehicles\Domain\Model\VehicleImage;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleVersion;

final readonly class UploadVehicleImagesHandler
{
    private const REPLAY_LIFETIME = '+1 day';

    public function __construct(
        private VehicleWriteGateway $vehicles,
        private VehicleReadGateway $reads,
        private UploadReplayStore $replays,
        private MediaStore $media,
        private UploadedImagesResponse $responses,
        private TransactionRunner $transactions,
        private Clock $clock,
        private UploadFingerprint $fingerprint,
    ) {}

    public function handle(UploadVehicleImages $command): UploadVehicleImagesResult
    {
        $now = $this->clock->now();
        $claim = $this->replays->claim(
            $command->actor->userId,
            $command->vehicleId,
            $command->idempotencyKey,
            $this->fingerprint->make($command->files),
            $now->modify(self::REPLAY_LIFETIME),
        );

        if ($claim->status === 'completed') {
            return UploadVehicleImagesResult::success(new RenderedUploadResponse(
                $claim->responseBody ?? [],
                $claim->responseStatus ?? 201,
                $claim->responseEtag ?? '',
            ));
        }

        if ($claim->status === 'different') {
            return UploadVehicleImagesResult::failure(UploadVehicleImagesError::IdempotencyConflict);
        }

        if ($claim->status === 'processing') {
            return UploadVehicleImagesResult::failure(UploadVehicleImagesError::UploadProcessing, true);
        }

        if ($command->versionHeaderMissing) {
            $this->replays->abandon($claim->id);

            return UploadVehicleImagesResult::failure(UploadVehicleImagesError::MissingVersion);
        }

        if ($command->expectedVersion === null) {
            $this->replays->abandon($claim->id);

            return UploadVehicleImagesResult::failure(UploadVehicleImagesError::VersionConflict);
        }

        $stored = [];

        try {
            $vehicleId = new VehicleId($command->vehicleId);

            foreach ($command->files as $source) {
                $stored[] = $this->media->store($vehicleId, $source);
            }

            $result = $this->transactions->run(function () use ($claim, $command, $vehicleId, $stored, $now): UploadVehicleImagesResult {
                $vehicle = $this->vehicles->getForUpdate($vehicleId);

                if ($vehicle === null) {
                    return UploadVehicleImagesResult::failure(UploadVehicleImagesError::NotFound);
                }

                $images = \array_map(
                    static fn ($image): VehicleImage => new VehicleImage(null, $image->path, false, $now),
                    $stored,
                );
                $outcome = $vehicle->attachImages(
                    $images,
                    $command->actor,
                    new VehicleVersion($command->expectedVersion),
                    $now,
                );

                if ($outcome->failed()) {
                    return UploadVehicleImagesResult::failure($this->mapError($outcome->error));
                }

                $this->vehicles->update($vehicle);
                $projection = $this->reads->detail($vehicleId);

                if ($projection === null) {
                    throw new \LogicException('The updated Vehicle projection is missing.');
                }

                $paths = \array_column($stored, 'path');
                $created = \array_values(\array_filter(
                    $projection->images,
                    static fn ($image): bool => \in_array($image->path, $paths, true),
                ));
                $response = $this->responses->render($command->vehicleId, $vehicle->version()->value, $created);
                $this->replays->complete($claim->id, $response->body, $response->status, $response->etag);

                return UploadVehicleImagesResult::success($response);
            });

            if ($result->error !== null) {
                $this->rollback($claim->id, $stored);
            }

            return $result;
        } catch (\Throwable $exception) {
            $this->rollback($claim->id, $stored);

            throw $exception;
        }
    }

    private function rollback(int $claimId, array $stored): void
    {
        $this->media->delete(\array_column($stored, 'path'));
        $this->replays->abandon($claimId);
    }

    private function mapError(?VehicleError $error): UploadVehicleImagesError
    {
        return match ($error) {
            VehicleError::Forbidden => UploadVehicleImagesError::Forbidden,
            VehicleError::VersionConflict => UploadVehicleImagesError::VersionConflict,
            VehicleError::GalleryCapacityExceeded => UploadVehicleImagesError::GalleryCapacityExceeded,
            default => throw new \LogicException('Unexpected image-upload error.'),
        };
    }
}
