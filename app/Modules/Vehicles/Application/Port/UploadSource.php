<?php

namespace App\Modules\Vehicles\Application\Port;

interface UploadSource
{
    public function originalName(): string;

    public function mediaType(): string;

    public function sizeInBytes(): int;

    public function contentHash(): string;

    /** @return resource */
    public function openStream();
}
