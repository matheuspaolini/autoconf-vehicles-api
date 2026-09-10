<?php

namespace App\Modules\Vehicles\Application\Port;

use DateTimeImmutable;

interface CleanupStore
{
    /** @return list<int> */
    public function overdueTaskIds(DateTimeImmutable $cutoff, int $limit): array;

    public function dispatch(int $taskId): void;

    /** @return array{paths: list<string>, directory: string|null}|null */
    public function pendingTask(int $taskId): ?array;

    public function complete(int $taskId): void;

    public function fail(int $taskId, string $message): void;

    public function pruneExpiredUploadRequests(DateTimeImmutable $now): void;

    public function removeOrphans(DateTimeImmutable $cutoff): void;
}
