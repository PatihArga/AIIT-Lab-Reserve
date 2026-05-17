<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Beranda Admin"
            title="Dashboard"
            meta="Diperbarui beberapa detik lalu · {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}">

            <x-slot:actions>
                <a href="{{ route('admin.requests.index', ['status' => 'pending']) }}" class="btn-mark btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
                    </svg>
                    Tinjau {{ $stats['pending_count'] }} Permintaan
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    @php
        $online      = $computers->where('status', 'online')->count();
        $maintenance = $computers->where('status', 'maintenance')->count();
        $offline     = $computers->where('status', 'offline')->count();
    @endphp

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-6">

        {{-- Menunggu Tinjauan (primary, mark accent) --}}
        <div class="bg-white border border-rule rounded-xl shadow-card p-4 sm:p-5
                    border-l-[3px] border-l-mark-500 flex flex-col">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="text-[10px] sm:text-[11px] uppercase tracking-label font-bold text-ink-700/60 leading-tight">
                    Menunggu Tinjauan
                </div>
                <div class="w-8 h-8 rounded-md bg-mark-50 border border-mark-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-mark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-[1.7rem] sm:text-[2rem] font-bold text-ink-900 leading-none tracking-tight">{{ $stats['pending_count'] }}</div>
            <div class="text-[11px] sm:text-xs text-ink-700/50 mt-2">Submitted + Under Review</div>
        </div>

        {{-- Disetujui Bulan Ini --}}
        <div class="bg-white border border-rule rounded-xl shadow-card p-4 sm:p-5 flex flex-col">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="text-[10px] sm:text-[11px] uppercase tracking-label font-bold text-ink-700/60 leading-tight">
                    Disetujui Bulan Ini
                </div>
                <div class="w-8 h-8 rounded-md bg-ink-50 border border-rule flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-status-approved" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <div class="text-[1.7rem] sm:text-[2rem] font-bold text-ink-900 leading-none tracking-tight">{{ $stats['approved_this_month'] }}</div>
            <div class="text-[11px] sm:text-xs text-ink-700/50 mt-2">{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</div>
        </div>

        {{-- Unit Aktif --}}
        <div class="bg-white border border-rule rounded-xl shadow-card p-4 sm:p-5 flex flex-col">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="text-[10px] sm:text-[11px] uppercase tracking-label font-bold text-ink-700/60 leading-tight">
                    Unit Aktif
                </div>
                <div class="w-8 h-8 rounded-md bg-ink-50 border border-rule flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-ink-700/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div class="text-[1.7rem] sm:text-[2rem] font-bold text-ink-900 leading-none tracking-tight">{{ $stats['computers_online'] }}<span class="text-base text-ink-700/40">/{{ $stats['computers_total'] }}</span></div>
            <div class="text-[11px] sm:text-xs text-ink-700/50 mt-2">{{ $online }} daring · {{ $maintenance }} perawatan</div>
        </div>

    </div>

    {{-- Lab status mini-strip --}}
    <div class="bg-white border border-rule rounded-lg px-4 py-2.5 mb-8 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
        <div class="flex items-center gap-2 text-xs">
            <span class="w-2 h-2 rounded-sm" style="background:#2eb8a0"></span>
            <span class="font-mono font-semibold text-ink-900">{{ $online }}</span>
            <span class="text-ink-700/60">unit online</span>
        </div>
        <span class="hidden sm:inline text-ink-700/20">·</span>
        <div class="flex items-center gap-2 text-xs">
            <span class="w-2 h-2 rounded-sm bg-mark-500"></span>
            <span class="font-mono font-semibold text-ink-900">{{ $maintenance }}</span>
            <span class="text-ink-700/60">perawatan</span>
        </div>
        @if ($offline > 0)
            <span class="hidden sm:inline text-ink-700/20">·</span>
            <div class="flex items-center gap-2 text-xs">
                <span class="w-2 h-2 rounded-sm bg-ink-700/40"></span>
                <span class="font-mono font-semibold text-ink-900">{{ $offline }}</span>
                <span class="text-ink-700/60">offline</span>
            </div>
        @endif
        <span class="hidden sm:inline text-ink-700/20">·</span>
        <div class="text-xs text-ink-700/60">
            Lab buka <span class="font-mono font-semibold text-ink-900">08:00–22:00</span> hari ini
        </div>
    </div>

    @php
        $typeLabel = fn($t) => match ($t) {
            'full_room'      => 'Komputer + Ruang',
            'computers_only' => 'Komputer Saja',
            'room_only'      => 'Ruang Saja',
            default          => $t,
        };
    @endphp

    {{-- Two-column: Pending requests + Activity feed --}}
    <div class="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-4 lg:gap-6">

        {{-- LEFT: Pending requests card --}}
        <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">

            {{-- Card header strip --}}
            <div class="px-5 py-4 border-b border-rule flex items-center justify-between gap-3">
                <div class="section-label flex items-baseline gap-2">
                    <span>Permintaan Aktif</span>
                    <span class="font-mono text-ink-700/40 normal-case tracking-normal">· {{ $pendingBookings->count() }}</span>
                </div>
                <a href="{{ route('admin.requests.index') }}" class="btn-ghost btn-sm">
                    Lihat Semua
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            @if ($pendingBookings->isEmpty() && $recentActivity->isEmpty())
                <div class="p-5">
                    <x-empty-state
                        title="Belum ada permintaan"
                        desc="Permintaan baru dari pengguna akan muncul di sini." />
                </div>
            @else
                {{-- Mobile rows --}}
                <div class="lg:hidden divide-y divide-rule">
                    @foreach ($pendingBookings as $b)
                        <a href="{{ route('admin.requests.show', $b) }}"
                           class="block p-4 active:bg-ink-50/40 transition-colors border-l-[3px] border-l-mark-500">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="font-mono text-sm font-semibold text-ink-900">{{ $b->booking_code }}</span>
                                <x-badge :status="$b->status" />
                            </div>
                            <div class="text-sm font-medium text-ink-900">{{ $b->user->name }}</div>
                            <div class="text-xs text-ink-700/50 mb-2">{{ ucfirst($b->user->role) }}</div>
                            <div class="text-sm text-ink-700/80 mb-1">{{ $typeLabel($b->booking_type) }}</div>
                            <div class="flex items-center gap-2 text-xs text-ink-700/60 font-mono">
                                <span>{{ $b->date->translatedFormat('d M Y') }}</span>
                                <span class="text-ink-700/30">·</span>
                                <span>{{ substr($b->start_time, 0, 5) }} — {{ substr($b->end_time, 0, 5) }}</span>
                            </div>
                            <div class="flex items-center justify-end mt-3">
                                <span class="btn-ghost btn-sm pointer-events-none">Tinjau</span>
                            </div>
                        </a>
                    @endforeach

                    @if ($recentActivity->isNotEmpty())
                        <div class="px-4 py-2 bg-ink-50/50 text-[10px] uppercase tracking-label font-bold text-ink-700/50">
                            Baru Diproses
                        </div>
                    @endif

                    @foreach ($recentActivity as $b)
                        <a href="{{ route('admin.requests.show', $b) }}" class="block p-4 opacity-80">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="font-mono text-sm font-semibold text-ink-700/60">{{ $b->booking_code }}</span>
                                <x-badge :status="$b->status" />
                            </div>
                            <div class="text-sm text-ink-700/70">{{ $b->user->name }}</div>
                            <div class="text-sm text-ink-700/60 mt-1">{{ $typeLabel($b->booking_type) }}</div>
                            <div class="flex items-center gap-2 text-xs text-ink-700/50 font-mono mt-1">
                                <span>{{ $b->date->translatedFormat('d M Y') }}</span>
                                <span class="text-ink-700/30">·</span>
                                <span>{{ substr($b->start_time, 0, 5) }} — {{ substr($b->end_time, 0, 5) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Desktop table --}}
                <div class="hidden lg:block">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="!pl-5">Kode</th>
                                <th>Pemohon</th>
                                <th>Jenis</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="text-right !pr-5"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingBookings as $b)
                                <tr class="row-mark">
                                    <td class="mono-data !pl-5">{{ $b->booking_code }}</td>
                                    <td>
                                        <div class="font-medium">{{ $b->user->name }}</div>
                                        <div class="text-xs text-ink-700/50">{{ ucfirst($b->user->role) }}</div>
                                    </td>
                                    <td class="text-ink-700/70">{{ $typeLabel($b->booking_type) }}</td>
                                    <td>
                                        <div class="mono-data text-ink-900">{{ $b->date->translatedFormat('d M Y') }}</div>
                                        <div class="mono-code">{{ substr($b->start_time, 0, 5) }} — {{ substr($b->end_time, 0, 5) }}</div>
                                    </td>
                                    <td><x-badge :status="$b->status" /></td>
                                    <td class="text-right !pr-5">
                                        <a href="{{ route('admin.requests.show', $b) }}" class="btn-ghost btn-sm">Tinjau</a>
                                    </td>
                                </tr>
                            @empty
                                @if ($recentActivity->isEmpty())
                                    <tr>
                                        <td colspan="6" class="!py-6 text-center text-ink-700/50">Belum ada permintaan menunggu.</td>
                                    </tr>
                                @endif
                            @endforelse

                            @if ($recentActivity->isNotEmpty())
                                <tr class="bg-ink-50/50">
                                    <td colspan="6" class="!py-2 !pl-5 text-[10px] uppercase tracking-label font-bold text-ink-700/50">
                                        Baru Diproses
                                    </td>
                                </tr>
                            @endif

                            @foreach ($recentActivity as $b)
                                <tr>
                                    <td class="mono-data text-ink-700/60 !pl-5">{{ $b->booking_code }}</td>
                                    <td class="text-ink-700/70">{{ $b->user->name }}</td>
                                    <td class="text-ink-700/50">{{ $typeLabel($b->booking_type) }}</td>
                                    <td>
                                        <div class="mono-data text-ink-700/60">{{ $b->date->translatedFormat('d M Y') }}</div>
                                        <div class="mono-code text-ink-700/40">{{ substr($b->start_time, 0, 5) }} — {{ substr($b->end_time, 0, 5) }}</div>
                                    </td>
                                    <td><x-badge :status="$b->status" /></td>
                                    <td class="text-right !pr-5">
                                        <a href="{{ route('admin.requests.show', $b) }}" class="btn-ghost btn-sm">Lihat</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- RIGHT: Activity feed card --}}
        <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden flex flex-col">

            {{-- Card header strip --}}
            <div class="px-5 py-4 border-b border-rule">
                <div class="section-label">Aktivitas Terkini</div>
            </div>

            {{-- Body --}}
            <ul class="p-5 space-y-5 flex-1">
                @forelse ($recentActivity as $b)
                    @php
                        $dotColor = match ($b->status) {
                            'approved'  => 'bg-status-approved',
                            'rejected'  => 'bg-status-rejected',
                            'completed' => 'bg-status-completed',
                            default     => 'bg-ink-700/30',
                        };
                        $verb = match ($b->status) {
                            'approved'  => 'menyetujui',
                            'rejected'  => 'menolak',
                            'completed' => 'menandai selesai',
                            default     => 'memproses',
                        };
                    @endphp
                    <li class="flex gap-3">
                        <div class="w-1.5 mt-1.5 shrink-0">
                            <span class="block w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-ink-900">
                                {{ $b->reviewer?->name ?? 'Admin' }} {{ $verb }}
                                <a href="{{ route('admin.requests.show', $b) }}" class="mono-data text-ink-900 hover:underline">{{ $b->booking_code }}</a>
                            </p>
                            @if ($b->status === 'rejected' && $b->admin_notes)
                                <p class="text-xs text-ink-700/50 mt-0.5">"{{ \Illuminate\Support\Str::limit($b->admin_notes, 80) }}"</p>
                            @endif
                            <p class="text-xs text-ink-700/50 mono-code mt-0.5">{{ optional($b->reviewed_at)->diffForHumans() }}</p>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-ink-700/50">Belum ada aktivitas.</li>
                @endforelse
            </ul>

            {{-- Footer strip --}}
            <a href="{{ route('admin.audit-log.index') }}"
               class="px-5 py-3 border-t border-rule text-xs font-semibold uppercase tracking-label text-ink-700/70 hover:text-ink-900 hover:bg-ink-50/50 transition-colors flex items-center justify-between">
                <span>Buka Audit Log</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Computer status overview --}}
    <x-section label="Status Komputer" title="{{ $stats['computers_total'] }} Unit · Lab 401" class="mt-12 sm:mt-16">
        <x-slot:actions>
            <a href="{{ route('admin.computers.index') }}" class="btn-ghost btn-sm">Kelola</a>
        </x-slot:actions>

        <x-computer-grid :computers="$computers" />
    </x-section>

</x-app-layout>
