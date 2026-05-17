<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'team_name'        => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', 'unique:users,email'],
            'study_program_id' => ['required', 'integer', 'exists:study_programs,id'],
            'pic_user_id'      => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'lecturer')->where('is_active', true)),
            ],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'description'      => ['nullable', 'string', 'max:500'],
            'members'          => ['nullable', 'array'],
            // Each member is only valid when BOTH name and NIM are provided; blank rows are filtered out in the controller.
            'members.*.name'   => ['nullable', 'string', 'max:255', 'required_with:members.*.nim'],
            'members.*.nim'    => ['nullable', 'string', 'max:50',  'required_with:members.*.name'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'              => 'Email sudah terdaftar.',
            'pic_user_id.exists'        => 'Dosen PIC tidak valid atau tidak aktif.',
            'password.confirmed'        => 'Konfirmasi kata sandi tidak cocok.',
            'members.*.name.required_with' => 'Nama anggota wajib diisi jika NIM diisi.',
            'members.*.nim.required_with'  => 'NIM wajib diisi jika nama anggota diisi.',
        ];
    }
}
