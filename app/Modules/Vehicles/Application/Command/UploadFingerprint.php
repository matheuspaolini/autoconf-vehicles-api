<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Port\UploadSource;

final class UploadFingerprint
{
    /** @param list<UploadSource> $files */
    public function make(array $files): string
    {
        $parts = \array_map(static fn (UploadSource $file): array => [
            'hash' => $file->contentHash(),
            'mime' => $file->mediaType(),
            'name' => $file->originalName(),
            'size' => $file->sizeInBytes(),
        ], $files);

        return \hash('sha256', \json_encode($parts, JSON_THROW_ON_ERROR));
    }
}
