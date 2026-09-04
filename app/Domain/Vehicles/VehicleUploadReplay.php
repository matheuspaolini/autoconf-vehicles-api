<?php

namespace App\Domain\Vehicles;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleUploadRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class VehicleUploadReplay
{
    /** @param list<UploadedFile> $files */
    public function fingerprint(array $files): string
    {
        $parts = \array_map(function (UploadedFile $file): array {
            $path = $file->getRealPath();

            if (! \is_string($path) || ($contentHash = \hash_file('sha256', $path)) === false) {
                throw new \RuntimeException('The uploaded file could not be fingerprinted.');
            }

            return [
                'hash' => $contentHash,
                'mime' => $file->getMimeType(),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];
        }, $files);

        return \hash('sha256', \json_encode($parts, JSON_THROW_ON_ERROR));
    }

    public function claim(User $user, Vehicle $vehicle, string $key, string $fingerprint): VehicleUploadRequest
    {
        try {
            return DB::transaction(fn (): VehicleUploadRequest => VehicleUploadRequest::query()->create([
                'user_id' => $user->getKey(),
                'vehicle_id' => $vehicle->getKey(),
                'idempotency_key' => $key,
                'request_hash' => $fingerprint,
                'status' => 'processing',
                'expires_at' => now()->addDay(),
            ]));
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23505') {
                throw $exception;
            }

            /** @var VehicleUploadRequest $existing */
            $existing = VehicleUploadRequest::query()
                ->where('user_id', $user->getKey())
                ->where('vehicle_id', $vehicle->getKey())
                ->where('idempotency_key', $key)
                ->firstOrFail();

            if ($existing->expires_at->isPast()) {
                $existing->delete();

                return $this->claim($user, $vehicle, $key, $fingerprint);
            }

            if (! \hash_equals($existing->request_hash, $fingerprint)) {
                throw new HttpException(409, 'This Idempotency-Key was already used with a different upload.');
            }

            if ($existing->status === 'processing') {
                throw new HttpException(409, 'This upload is already processing.', null, ['Retry-After' => '5']);
            }

            return $existing;
        }
    }

    /** @param array<string, mixed> $body */
    public function complete(VehicleUploadRequest $request, array $body, int $status, string $etag): void
    {
        $request->forceFill([
            'status' => 'completed',
            'response_status' => $status,
            'response_body' => $body,
            'response_etag' => $etag,
        ])->save();
    }

    public function abandon(VehicleUploadRequest $request): void
    {
        if ($request->status === 'processing') {
            $request->delete();
        }
    }
}
