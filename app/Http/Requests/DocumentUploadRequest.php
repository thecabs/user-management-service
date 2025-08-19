<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'type_document' => 'required|in:piece_face_avant,piece_face_arriere,selfie,autre'
        ];
    }
}