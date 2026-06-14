<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeamRequest;
use App\Http\Requests\Admin\UpdateTeamRequest;
use App\Models\StudyProgram;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminTeamController extends Controller
{
    public function create(): View
    {
        $studyPrograms = StudyProgram::where('is_active', true)->orderBy('name')->get();
        $lecturers     = User::where('role', 'lecturer')->where('is_active', true)->orderBy('name')->get();

        return view('admin.teams.create', compact('studyPrograms', 'lecturers'));
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        // M5 fix: filter out blank member rows so we never insert empty NOT-NULL fields.
        $members = collect($request->input('members', []))
            ->map(fn ($m) => [
                'name' => trim($m['name'] ?? ''),
                'nim'  => trim($m['nim']  ?? ''),
            ])
            ->filter(fn ($m) => $m['name'] !== '' && $m['nim'] !== '')
            ->values();

        $team = DB::transaction(function () use ($request, $members) {
            $user = User::create([
                'name'             => $request->team_name,
                'email'            => $request->email,
                // Teams authenticate via the study-program flow (no per-account password),
                // but users.password is NOT NULL — store an unusable random hash.
                'password'         => Hash::make(Str::random(40)),
                'role'             => 'team',
                'study_program_id' => $request->study_program_id,
                'is_active'        => true,
            ]);

            $team = Team::create([
                'user_id'          => $user->id,
                'pic_lecturer_id'  => $request->pic_user_id,
                'study_program_id' => $request->study_program_id,
                'name'             => $request->team_name,
                'description'      => $request->description,
                'is_active'        => true,
            ]);

            foreach ($members as $m) {
                TeamMember::create([
                    'team_id'           => $team->id,
                    'student_name'      => $m['name'],
                    'student_id_number' => $m['nim'],
                ]);
            }

            AuditLogService::record('team.created', $team, [], [
                'name'             => $team->name,
                'email'            => $user->email,
                'pic_lecturer_id'  => $team->pic_lecturer_id,
                'study_program_id' => $team->study_program_id,
                'member_count'     => $members->count(),
            ]);

            return $team;
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'Tim ' . $team->name . ' berhasil dibuat.');
    }

    public function edit(Team $team): View
    {
        $team->load(['members', 'userAccount', 'picLecturer', 'studyProgram']);

        $studyPrograms = StudyProgram::where('is_active', true)->orderBy('name')->get();
        $lecturers     = User::where('role', 'lecturer')->where('is_active', true)->orderBy('name')->get();

        return view('admin.teams.edit', compact('team', 'studyPrograms', 'lecturers'));
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $members = collect($request->input('members', []))
            ->map(fn ($m) => [
                'name' => trim($m['name'] ?? ''),
                'nim'  => trim($m['nim']  ?? ''),
            ])
            ->filter(fn ($m) => $m['name'] !== '' && $m['nim'] !== '')
            ->values();

        $oldValues = [
            'name'             => $team->name,
            'email'            => $team->userAccount?->email,
            'pic_lecturer_id'  => $team->pic_lecturer_id,
            'study_program_id' => $team->study_program_id,
            'is_active'        => $team->is_active,
            'member_count'     => $team->members->count(),
        ];

        DB::transaction(function () use ($request, $team, $members) {
            $user = $team->userAccount;
            if ($user) {
                $userPayload = [
                    'name'             => $request->team_name,
                    'email'            => $request->email,
                    'study_program_id' => $request->study_program_id,
                    'is_active'        => $request->boolean('is_active', true),
                ];
                if ($request->filled('password')) {
                    $userPayload['password'] = Hash::make($request->password);
                }
                $user->update($userPayload);
            }

            $team->update([
                'pic_lecturer_id'  => $request->pic_user_id,
                'study_program_id' => $request->study_program_id,
                'name'             => $request->team_name,
                'description'      => $request->description,
                'is_active'        => $request->boolean('is_active', true),
            ]);

            // Sync members: simplest correct approach — delete and re-insert.
            $team->members()->delete();
            foreach ($members as $m) {
                TeamMember::create([
                    'team_id'           => $team->id,
                    'student_name'      => $m['name'],
                    'student_id_number' => $m['nim'],
                ]);
            }
        });

        AuditLogService::record('team.updated', $team, $oldValues, [
            'name'             => $team->fresh()->name,
            'email'            => $request->email,
            'pic_lecturer_id'  => $request->pic_user_id,
            'study_program_id' => $request->study_program_id,
            'is_active'        => $request->boolean('is_active', true),
            'member_count'     => $members->count(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Tim ' . $team->name . ' diperbarui.');
    }
}
