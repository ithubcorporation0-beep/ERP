<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Adds a single "documents" media collection, stored on the private disk,
 * to any model. Used by every entity that supports file attachments
 * (Customer, Project, Task, Invoice, Payment, Expense).
 */
trait HasDocuments
{
    use InteractsWithMedia;

    /**
     * File type and size are enforced by StoreDocumentRequest, the only
     * path files reach this collection through; no acceptsMimeTypes()
     * guard is added here since MediaLibrary sniffs real file bytes,
     * which would reject some genuinely-valid uploads.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')->useDisk('private');
    }

    public function documents(): Collection
    {
        return $this->getMedia('documents');
    }

    public function addDocument(UploadedFile $file): Media
    {
        return $this->addMedia($file)->toMediaCollection('documents');
    }
}
