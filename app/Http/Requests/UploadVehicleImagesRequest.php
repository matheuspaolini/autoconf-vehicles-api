<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadVehicleImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageImages', $this->route('vehicle')) ?? false;
    }

    public function rules(): array
    {
        return ['files' => ['required', 'array', 'min:1', 'max:10'], 'files.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']];
    }
}
