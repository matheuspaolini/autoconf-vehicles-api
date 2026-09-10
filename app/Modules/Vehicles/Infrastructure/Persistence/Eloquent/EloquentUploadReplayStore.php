<?php

namespace App\Modules\Vehicles\Infrastructure\Persistence\Eloquent;

use App\Modules\Vehicles\Application\Data\ReplayClaim;
use App\Modules\Vehicles\Application\Port\UploadReplayStore;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class EloquentUploadReplayStore implements UploadReplayStore
{
    public function claim(int $userId, int $vehicleId, string $key, string $fingerprint, DateTimeImmutable $expiresAt): ReplayClaim
    {
        try {
            $record = DB::transaction(static fn (): VehicleUploadRequestRecord => VehicleUploadRequestRecord::query()->create([
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'idempotency_key' => $key,
                'request_hash' => $fingerprint,
                'status' => 'processing',
                'expires_at' => $expiresAt,
            ]));

            return new ReplayClaim((int) $record->getKey(), 'claimed');
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23505') {
                throw $exception;
            }
        }

        /** @var VehicleUploadRequestRecord $existing */
        $existing = VehicleUploadRequestRecord::query()
            ->where('user_id', $userId)
            ->where('vehicle_id', $vehicleId)
            ->where('idempotency_key', $key)
            ->firstOrFail();

        if ($existing->expires_at->isPast()) {
            $existing->delete();

            return $this->claim($userId, $vehicleId, $key, $fingerprint, $expiresAt);
        }

        if (! \hash_equals($existing->request_hash, $fingerprint)) {
            return new ReplayClaim((int) $existing->getKey(), 'different');
        }

        return new ReplayClaim(
            id: (int) $existing->getKey(),
            status: $existing->status,
            responseStatus: $existing->response_status,
            responseBody: $existing->response_body,
            responseEtag: $existing->response_etag,
        );
    }

    public function complete(int $claimId, array $body, int $status, string $etag): void
    {
        VehicleUploadRequestRecord::query()->whereKey($claimId)->update([
            'status' => 'completed',
            'response_status' => $status,
            'response_body' => $body,
            'response_etag' => $etag,
        ]);
    }

    public function abandon(int $claimId): void
    {
        VehicleUploadRequestRecord::query()->whereKey($claimId)->where('status', 'processing')->delete();
    }
}
