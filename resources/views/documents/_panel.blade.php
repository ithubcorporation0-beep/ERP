@php
    $documents = $model->documents();
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Documents') }}</h3>

    @can('uploadDocuments', $model)
        <form method="POST" action="{{ route('documents.store', ['type' => $type, 'id' => $model->id]) }}"
            enctype="multipart/form-data" class="mb-6">
            @csrf

            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[16rem]">
                    <x-input-label for="documents-files-{{ $type }}-{{ $model->id }}" value="Upload files" />
                    <input id="documents-files-{{ $type }}-{{ $model->id }}" type="file" name="files[]" multiple
                        accept=".pdf,.docx,.xlsx,.csv,.png,.jpg,.jpeg,.txt"
                        class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:bg-gray-800 file:text-white hover:file:bg-gray-700" />
                    <p class="mt-1 text-xs text-gray-500">{{ __('PDF, DOCX, XLSX, CSV, PNG, JPG, or TXT. Max 20MB per file.') }}</p>
                    <x-input-error :messages="$errors->get('files')" class="mt-2" />
                    <x-input-error :messages="$errors->get('files.*')" class="mt-2" />
                </div>

                <x-secondary-button type="submit">{{ __('Upload') }}</x-secondary-button>
            </div>
        </form>
    @endcan

    @can('downloadDocuments', $model)
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('File') }}</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Type') }}</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Size') }}</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Uploaded') }}</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($documents as $document)
                    <tr>
                        <td class="px-3 py-2 text-sm text-gray-900">{{ $document->file_name }}</td>
                        <td class="px-3 py-2 text-sm text-gray-500">{{ $document->mime_type }}</td>
                        <td class="px-3 py-2 text-sm text-gray-500">{{ $document->human_readable_size }}</td>
                        <td class="px-3 py-2 text-sm text-gray-500">{{ $document->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2 text-right text-sm space-x-3">
                            <a href="{{ route('documents.download', ['type' => $type, 'id' => $model->id, 'media' => $document->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ __('Download') }}
                            </a>

                            @can('deleteDocuments', $model)
                                <form method="POST" action="{{ route('documents.destroy', ['type' => $type, 'id' => $model->id, 'media' => $document->id]) }}" class="inline"
                                    onsubmit="return confirm('{{ __('Delete this document?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-2 text-sm text-gray-500">{{ __('No documents uploaded yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endcan
</div>
