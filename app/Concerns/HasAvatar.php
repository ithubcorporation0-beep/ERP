<?php

namespace App\Concerns;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A single-file "avatar" media collection, stored on the private disk like
 * every other upload in this app. Served through AvatarController rather
 * than a public URL, so viewing one still requires authentication.
 */
trait HasAvatar
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->useDisk('private')
            ->singleFile();
    }

    public function avatar(): ?Media
    {
        return $this->getFirstMedia('avatar');
    }

    public function setAvatar(UploadedFile $file): Media
    {
        return $this->addMedia($file)->toMediaCollection('avatar');
    }
}
