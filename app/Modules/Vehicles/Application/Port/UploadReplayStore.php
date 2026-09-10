<?php

namespace App\Modules\Vehicles\Application\Port;

use App\Modules\Vehicles\Application\Data\ReplayClaim;
use DateTimeImmutable;

interface UploadReplayStore
{
    public function claim(int $userId, int $vehicleId, string $key, string $fingerprint, DateTimeImmutable $expiresAt): ReplayClaim;

    /** @param array<string, mixed> $body */
    public function complete(int $claimId, array $body, int $status, string $etag): void;

    public function abandon(int $claimId): void;
}
