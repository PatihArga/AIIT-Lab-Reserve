<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $team   = $this->route('team');
        $userId = $team?->user_id;

        return [
            'team_name'        => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'study_program_id' => ['required', 'integer', 'exists:study_programs,id'],
            'pic_user_id'      => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'lecturer')->where('is_active', true)),
            ],
            'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
            'description'      => ['nullable', 'string', 'max:500'],
            'is_active'        => ['nullable'],
            'members'          => ['nullable', 'array'],
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
