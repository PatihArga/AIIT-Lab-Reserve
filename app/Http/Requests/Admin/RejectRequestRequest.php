<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'admin_notes' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_notes.required' => 'Alasan penolakan wajib diisi.',
            'admin_notes.min'      => 'Alasan minimal 10 karakter.',
            'admin_notes.max'      => 'Alasan maksimal 2000 karakter.',
        ];
    }
}
