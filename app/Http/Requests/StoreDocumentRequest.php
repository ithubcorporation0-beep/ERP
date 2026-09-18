<?php

namespace App\Http\Requests;

use App\Support\Documentable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    private ?Model $resolvedDocumentable = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('uploadDocuments', $this->documentable());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:20480', 'mimes:pdf,docx,xlsx,csv,png,jpg,txt'],
        ];
    }

    /**
     * The entity these documents are being attached to, resolved from the
     * {type}/{id} route segments and memoized so authorize() and the
     * controller share a single lookup.
     */
    public function documentable(): Model
    {
        return $this->resolvedDocumentable ??= Documentable::resolve($this->route('type'), $this->route('id'));
    }
}
