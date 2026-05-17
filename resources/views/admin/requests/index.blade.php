<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Operasional"
            title="Permintaan Reservasi"
            meta="Semua permintaan masuk · {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}">
        </x-page-header>
    </x-slot:header>

    @php
        // C4 — 'pending' is a UI label that maps to ['submitted','under_review'] on the server side.
        $currentStatus = request('status', 'all');
        $tabs = [
            'all'          => 'Semua',
            'pending'      => 'Menunggu',
            'under_review' => 'Ditinjau',
            'approved'     => 'Disetujui',
            'rejected'     => 'Ditolak',
            'completed'    => 'Selesai',
        ];

        $typeLabel = fn($t) => match ($t) {
            'full_room'      => 'Komputer + Ruang',
            'computers_only' => 'Komputer Saja',
            'room_only'      => 'Ruang Saja',
            default          => $t,
        };

        $categoryLabel = fn($c) => match ($c) {
            'penelitian'       => 'Penelitian',
            'project_akademik' => 'Project Akademik',
            'praktikum'        => 'Praktikum',
            'tugas_akhir'      => 'Tugas Akhir',
            'lainnya'          => 'Lainnya',
            default            => '—',
        };
    @endphp

    {{-- Status tabs (server-side GET param) --}}
    <div class="flex items-center gap-1 mb-6 flex-wrap">
        @foreach ($tabs as $val => $label)
            @php $isActive = $currentStatus === $val; @endphp
            <a href="{{ route('admin.requests.index', array_filter([
                    'status' => $val === 'all' ? null : $val,
                    'q'      => request('q'),
                    'date'   => request('date'),
                ])) }}"
               class="px-3.5 py-1.5 text-xs font-semibold uppercase tracking-label border rounded-md transition-all
                      {{ $isActive ? 'bg-ink-900 text-white border-ink-900' : 'bg-white text-ink-700/70 border-rule hover:border-ink-300' }}">
                {{ $label }}
                @if ($val === 'pending' && $pendingCount > 0)
                    <span class="ml-1 font-mono bg-mark-500 text-ink-900 text-[10px] px-1.5 py-0.5 rounded font-semibold">{{ $pendingCount }}</span>
                @endif
            </a>
        @endforeach

        {{-- Search + date filter --}}
        <form method="GET" action="{{ route('admin.requests.index') }}" class="ml-auto flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $currentStatus !== 'all' ? $currentStatus : '' }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode / nama…"
                   class="form-input py-1.5 text-xs w-48">
            <input type="date" name="date" value="{{ request('date') }}" class="form-input py-1.5 text-xs w-36">
            <button type="submit" class="btn-ghost btn-sm">Terapkan</button>
            @if (request()->hasAny(['q', 'date']))
                <a href="{{ route('admin.requests.index', ['status' => $currentStatus !== 'all' ? $currentStatus : null]) }}"
                   class="btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Pemohon</th>
                    <th>Jenis</th>
                    <th>Jadwal</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th class="text-right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $b)
                    @php $isPending = in_array($b->status, ['submitted', 'under_review']); @endphp
                    <tr class="{{ $isPending ? 'row-mark' : '' }}">
                        <td class="mono-data">{{ $b->booking_code }}</td>
                        <td>
                            <div class="font-medium text-ink-900">{{ $b->user->name }}</div>
                            <div class="text-xs text-ink-700/50">{{ ucfirst($b->user->role) }}</div>
                        </td>
                        <td class="text-ink-700/70">
                            {{ $typeLabel($b->booking_type) }}
                            @if ($b->booking_type === 'computers_only' && $b->computers->count() > 0)
                                <span class="text-ink-700/40">({{ $b->computers->count() }})</span>
                            @endif
                        </td>
                        <td>
                            <div class="mono-data text-ink-900 text-sm">{{ $b->date->translatedFormat('d M Y') }}</div>
                            <div class="mono-code">{{ substr($b->start_time, 0, 5) }} – {{ substr($b->end_time, 0, 5) }}</div>
                        </td>
                        <td class="text-ink-700/70 text-sm">{{ $categoryLabel(optional($b->logbook)->category) }}</td>
                        <td><x-badge :status="$b->status" /></td>
                        <td class="text-right">
                            <a href="{{ route('admin.requests.show', $b) }}" class="btn-ghost btn-sm">Tinjau</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="!py-10 text-center text-ink-700/50">
                            Tidak ada reservasi yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginator --}}
    <div class="mt-4">
        {{ $bookings->links() }}
    </div>

</x-app-layout>
