<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with(['studyProgram', 'teamAccount.picLecturer'])
            ->withCount('bookings')
            ->where('role', '!=', 'admin')
            ->orderBy('name');

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }
        if ($request->filled('study_program_id') && $request->study_program_id !== 'all') {
            $query->where('study_program_id', $request->study_program_id);
        }
        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($q) use ($term) {
                $q->where('name',  'like', '%' . $term . '%')
                  ->orWhere('email', 'like', '%' . $term . '%');
            });
        }

        $users          = $query->paginate(20)->withQueryString();
        $studyPrograms  = StudyProgram::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'studyPrograms'));
    }

    public function create(): View
    {
        $studyPrograms = StudyProgram::where('is_active', true)->orderBy('name')->get();
        return view('admin.users.create', compact('studyPrograms'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name'             => $request->name,
                'email'            => $request->email,
                'password'         => Hash::make($request->password),
                'role'             => 'lecturer',
                'study_program_id' => $request->study_program_id,
                'is_active'        => $request->boolean('is_active', true),
            ]);

            AuditLogService::record('user.created', $user, [], [
                'name'             => $user->name,
                'email'            => $user->email,
                'role'             => $user->role,
                'study_program_id' => $user->study_program_id,
                'is_active'        => $user->is_active,
            ]);

            return $user;
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'Akun dosen ' . $user->name . ' berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        abort_if($user->isAdmin(), 403, 'Akun admin tidak dapat disunting dari panel ini.');
        $studyPrograms = StudyProgram::where('is_active', true)->orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'studyPrograms'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403, 'Akun admin tidak dapat disunting dari panel ini.');

        $oldValues = $user->only(['name', 'email', 'study_program_id', 'is_active']);

        $payload = [
            'name'             => $request->name,
            'email'            => $request->email,
            'study_program_id' => $request->study_program_id,
            'is_active'        => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->password);
        }

        $user->update($payload);

        $newValues = $user->only(['name', 'email', 'study_program_id', 'is_active']);
        if ($request->filled('password')) {
            $newValues['password'] = '[changed]';
        }

        AuditLogService::record('user.updated', $user, $oldValues, $newValues);

        return redirect()->route('admin.users.index')
            ->with('success', 'Data ' . $user->name . ' diperbarui.');
    }
}
