<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'study_program_id' => ['required', 'integer', 'exists:study_programs,id'],
            'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
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
