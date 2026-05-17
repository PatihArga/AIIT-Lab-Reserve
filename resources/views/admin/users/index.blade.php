<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Manajemen"
            title="Pengguna & Tim"
            meta="Dosen dan tim mahasiswa yang terdaftar">
            <x-slot:actions>
                <a href="{{ route('admin.teams.create') }}" class="btn-ghost btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Tim
                </a>
                <a href="{{ route('admin.users.create') }}" class="btn-mark btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Dosen
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    @php $currentRole = request('role', 'all'); @endphp

    {{-- Filter bar (server-side GET) --}}
    <div class="flex items-center gap-3 mb-6 flex-wrap">
        @foreach (['all' => 'Semua', 'lecturer' => 'Dosen', 'team' => 'Tim'] as $val => $label)
            @php $isActive = $currentRole === $val; @endphp
            <a href="{{ route('admin.users.index', array_filter([
                    'role'             => $val === 'all' ? null : $val,
                    'q'                => request('q'),
                    'study_program_id' => request('study_program_id'),
                ])) }}"
               class="px-3.5 py-1.5 text-xs font-semibold uppercase tracking-label border rounded-md transition-all
                      {{ $isActive ? 'bg-ink-900 text-white border-ink-900' : 'bg-white text-ink-700/70 border-rule hover:border-ink-300' }}">
                {{ $label }}
            </a>
        @endforeach

        <form method="GET" action="{{ route('admin.users.index') }}" class="ml-auto flex items-center gap-2">
            <input type="hidden" name="role" value="{{ $currentRole !== 'all' ? $currentRole : '' }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama / email…"
                   class="form-input py-1.5 text-xs w-52">
            <select name="study_program_id" class="form-select py-1.5 text-xs w-40">
                <option value="">Semua program studi</option>
                @foreach ($studyPrograms as $sp)
                    <option value="{{ $sp->id }}" @selected(request('study_program_id') == $sp->id)>{{ $sp->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-ghost btn-sm">Terapkan</button>
            @if (request()->hasAny(['q', 'study_program_id']))
                <a href="{{ route('admin.users.index', ['role' => $currentRole !== 'all' ? $currentRole : null]) }}"
                   class="btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Peran</th>
                    <th>Program Studi</th>
                    <th>Reservasi</th>
                    <th>Status</th>
                    <th class="text-right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="font-medium text-ink-900">{{ $user->name }}</div>
                            @if ($user->teamAccount?->picLecturer)
                                <div class="text-xs text-ink-700/50">PIC: {{ $user->teamAccount->picLecturer->name }}</div>
                            @endif
                        </td>
                        <td class="mono-code text-ink-700/70">{{ $user->email }}</td>
                        <td>
                            <span class="{{ $user->role === 'team' ? 'badge-outline' : 'badge-submitted' }} text-[10px]">
                                {{ $user->role === 'team' ? 'Tim' : 'Dosen' }}
                            </span>
                        </td>
                        <td class="text-ink-700/70 text-sm">{{ $user->studyProgram?->name ?? '—' }}</td>
                        <td class="mono-data text-center">{{ $user->bookings_count }}</td>
                        <td>
                            @if ($user->is_active)
                                <span class="badge-approved text-[10px]">Aktif</span>
                            @else
                                <span class="badge-cancelled text-[10px]">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if ($user->role === 'team' && $user->teamAccount)
                                <a href="{{ route('admin.teams.edit', $user->teamAccount) }}" class="btn-ghost btn-sm">Edit Tim</a>
                            @else
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost btn-sm">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="!py-10 text-center text-ink-700/50">
                            Tidak ada pengguna yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

</x-app-layout>
