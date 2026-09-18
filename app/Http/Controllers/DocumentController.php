<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Notifications\DocumentUploadedNotification;
use App\Support\Documentable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    /**
     * Upload one or more documents to the entity identified by {type}/{id}.
     * Authorization (uploadDocuments) is enforced in StoreDocumentRequest.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $model = $request->documentable();

        $uploaded = collect($request->file('files'))
            ->map(fn ($file) => $model->addDocument($file));

        $uploaded->each(fn (Media $media) => activity()
            ->performedOn($media)
            ->causedBy($request->user())
            ->withProperties(['file_name' => $media->file_name, 'size' => $media->size, 'attached_to' => $model::class.'#'.$model->id])
            ->event('created')
            ->log('Document uploaded'));

        $recipients = Documentable::documentRecipients($model, $request->user()->id);
        Notification::send($recipients, new DocumentUploadedNotification($model, $request->route('type'), $uploaded));

        return back()->with('status', 'Document(s) uploaded.');
    }

    /**
     * Stream a document from the private disk after checking the owning
     * entity's downloadDocuments ability. $media must actually belong to
     * the resolved entity, closing off cross-entity IDOR via a guessed
     * media id under an unrelated {type}/{id}.
     */
    public function download(string $type, int $id, Media $media): BinaryFileResponse
    {
        $model = $this->resolveOwnedMedia($type, $id, $media);

        $this->authorize('downloadDocuments', $model);

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * Delete a document. Authorization mirrors upload (deleteDocuments).
     */
    public function destroy(Request $request, string $type, int $id, Media $media): RedirectResponse
    {
        $model = $this->resolveOwnedMedia($type, $id, $media);

        $this->authorize('deleteDocuments', $model);

        activity()
            ->performedOn($media)
            ->causedBy($request->user())
            ->withProperties(['file_name' => $media->file_name, 'size' => $media->size, 'attached_to' => $model::class.'#'.$model->id])
            ->event('deleted')
            ->log('Document deleted');

        $media->delete();

        return back()->with('status', 'Document deleted.');
    }

    /**
     * Resolve the {type}/{id} entity and verify the given Media actually
     * belongs to it before any authorization check runs.
     */
    private function resolveOwnedMedia(string $type, int $id, Media $media): Model
    {
        $model = Documentable::resolve($type, $id);

        abort_unless(
            $media->model_type === $model::class && (int) $media->model_id === $model->id,
            404
        );

        return $model;
    }
}
