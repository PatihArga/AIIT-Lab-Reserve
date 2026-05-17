<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', 'unique:users,email'],
            'study_program_id' => ['required', 'integer', 'exists:study_programs,id'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'is_active'        => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'              => 'Email sudah terdaftar.',
            'password.confirmed'        => 'Konfirmasi kata sandi tidak cocok.',
            'study_program_id.exists'   => 'Program studi tidak valid.',
        ];
    }
}
