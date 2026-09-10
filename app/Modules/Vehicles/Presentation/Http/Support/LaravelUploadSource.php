<?php

namespace App\Modules\Vehicles\Presentation\Http\Support;

use App\Modules\Vehicles\Application\Port\UploadSource;
use Illuminate\Http\UploadedFile;
use RuntimeException;

final readonly class LaravelUploadSource implements UploadSource
{
    public function __construct(private UploadedFile $file) {}

    public function originalName(): string
    {
        return $this->file->getClientOriginalName();
    }

    public function mediaType(): string
    {
        return $this->file->getMimeType() ?? 'application/octet-stream';
    }

    public function sizeInBytes(): int
    {
        return $this->file->getSize();
    }

    public function contentHash(): string
    {
        $path = $this->file->getRealPath();

        if (! \is_string($path) || ($hash = \hash_file('sha256', $path)) === false) {
            throw new RuntimeException('The uploaded file could not be fingerprinted.');
        }

        return $hash;
    }

    public function openStream()
    {
        $stream = \fopen($this->file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('The uploaded file could not be opened.');
        }

        return $stream;
    }
}
