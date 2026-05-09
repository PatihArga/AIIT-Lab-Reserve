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

    @php
        $users = [
            ['id'=>1,'name'=>'Dr. Budi Santoso','email'=>'budi@ukrida.ac.id','role'=>'lecturer','program'=>'Teknik Informatika','active'=>true,'bookings'=>12],
            ['id'=>2,'name'=>'Dr. Siti Hartati','email'=>'siti@ukrida.ac.id','role'=>'lecturer','program'=>'Teknik Informatika','active'=>true,'bookings'=>8],
            ['id'=>3,'name'=>'Dr. Maria Lestari','email'=>'maria@ukrida.ac.id','role'=>'lecturer','program'=>'Sistem Informasi','active'=>true,'bookings'=>5],
            ['id'=>4,'name'=>'Tim Alpha','email'=>'tim.alpha@ukrida.ac.id','role'=>'team','program'=>'Teknik Informatika','active'=>true,'bookings'=>6,'pic'=>'Dr. Budi Santoso'],
            ['id'=>5,'name'=>'Tim Beta','email'=>'tim.beta@ukrida.ac.id','role'=>'team','program'=>'Teknik Informatika','active'=>true,'bookings'=>3,'pic'=>'Prof. Andi W.'],
            ['id'=>6,'name'=>'Tim Gamma','email'=>'tim.gamma@ukrida.ac.id','role'=>'team','program'=>'Sistem Informasi','active'=>false,'bookings'=>9,'pic'=>'Dr. Rina K.'],
        ];
    @endphp

    {{-- Filter bar --}}
    <div class="flex items-center gap-3 mb-6 flex-wrap" x-data="{ role: 'all' }">
        @foreach (['all' => 'Semua', 'lecturer' => 'Dosen', 'team' => 'Tim'] as $val => $label)
            <button type="button"
                    @click="role = '{{ $val }}'"
                    :class="role === '{{ $val }}' ? 'bg-ink-900 text-white border-ink-900' : 'bg-white text-ink-700/70 border-rule hover:border-ink-300'"
                    class="px-3.5 py-1.5 text-xs font-semibold uppercase tracking-label border rounded-md transition-all">
                {{ $label }}
            </button>
        @endforeach
        <div class="ml-auto flex items-center gap-2">
            <input type="search" placeholder="Cari nama / email…"
                   class="form-input py-1.5 text-xs w-52">
            <select class="form-select py-1.5 text-xs w-40">
                <option>Semua program studi</option>
                <option>Teknik Informatika</option>
                <option>Sistem Informasi</option>
            </select>
        </div>
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
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <div class="font-medium text-ink-900">{{ $user['name'] }}</div>
                            @if (isset($user['pic']))
                                <div class="text-xs text-ink-700/50">PIC: {{ $user['pic'] }}</div>
                            @endif
                        </td>
                        <td class="mono-code text-ink-700/70">{{ $user['email'] }}</td>
                        <td>
                            <span class="{{ $user['role'] === 'team' ? 'badge-outline' : 'badge-submitted' }} text-[10px]">
                                {{ $user['role'] === 'team' ? 'Tim' : 'Dosen' }}
                            </span>
                        </td>
                        <td class="text-ink-700/70 text-sm">{{ $user['program'] }}</td>
                        <td class="mono-data text-center">{{ $user['bookings'] }}</td>
                        <td>
                            @if ($user['active'])
                                <span class="badge-approved text-[10px]">Aktif</span>
                            @else
                                <span class="badge-cancelled text-[10px]">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.users.edit', $user['id']) }}" class="btn-ghost btn-sm">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between mt-4 text-xs text-ink-700/50">
        <span>Menampilkan 6 dari 6 pengguna</span>
        <div class="flex gap-1">
            <button class="px-3 py-1.5 rounded border border-rule bg-white text-ink-700/50 cursor-not-allowed">← Sebelumnya</button>
            <button class="px-3 py-1.5 rounded border border-rule bg-white text-ink-700/50 cursor-not-allowed">Berikutnya →</button>
        </div>
    </div>

</x-app-layout>
