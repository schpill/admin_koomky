<?php

namespace App\Http\Requests\Api\V1\Documents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxSize = config('performance.max_document_upload_mb', 50) * 1024;

        return [
            'file' => [
                'required',
                'file',
                "max:{$maxSize}",
            ],
            'title' => 'nullable|string|max:255',
            'client_id' => [
                'nullable',
                'uuid',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()->id);
                }),
            ],
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ];
    }
}
