<x-app-layout>

@push('styles')
<style>
/* ── TABLE & PANEL ──────────────────────────────────────────── */
.pill-tabs { display: flex; border: 1px solid rgba(15,36,96,.08); border-radius: 6px; padding: 2px; background: #FAFAF7; }
.pill-tab { padding: 5px 14px; font-size: 12px; font-weight: 600; border-radius: 4px; cursor: pointer; color: rgba(10,26,71,.4); transition: background .15s, color .15s; white-space: nowrap; user-select: none; }
.pill-tab.active { background: #fff; color: #0A1A47; box-shadow: 0 1px 2px rgba(10,26,71,.08); }
.bookings-tbl { width: 100%; border-collapse: collapse; }
.bookings-tbl thead th { font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: rgba(10,26,71,.38); padding: 8px 0; text-align: left; border-bottom: 1px solid rgba(15,36,96,.08); }
.bookings-tbl thead th:last-child { text-align: right; }
.bookings-tbl tbody tr { transition: background .15s; }
.bookings-tbl tbody tr:hover { background: #FAFAF7; }
.bookings-tbl tbody td { padding: 13px 0; border-bottom: 1px solid rgba(15,36,96,.08); vertical-align: middle; }
.bookings-tbl tbody tr:last-child td { border-bottom: none; }
.r-panel-section { padding: 18px 20px; }
.r-panel-section + .r-panel-section { border-top: 1px solid rgba(15,36,96,.08); }
.hours-row { display: flex; align-items: center; justify-content: space-between; padding: 5px 0; }
.hours-row + .hours-row { border-top: 1px solid rgba(15,36,96,.08); }
.meta-row { display: flex; align-items: center; justify-content: space-between; padding: 5px 0; }
.meta-row + .meta-row { border-top: 1px solid rgba(15,36,96,.08); }
.pc-dot-row { display: grid; grid-template-columns: repeat(9, 1fr); gap: 5px; margin-bottom: 8px; }
.pc-dot { aspect-ratio: 1; border-radius: 3px; background: #2eb8a0; }
.pc-dot.mt { background: #F5B800; }
</style>
@endpush

    @php
        $monthNamesId = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $monthShortId = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $dayNamesId   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $nextBooking  = $upcomingBookings->first();
        if ($nextBooking) {
            $nbDate = $nextBooking->date;
            $nextLabel = $dayNamesId[$nbDate->dayOfWeek] . ', ' . $nbDate->day . ' ' . $monthShortId[$nbDate->month - 1]
                . ' · ' . substr($nextBooking->start_time, 0, 5) . ' — ' . substr($nextBooking->end_time, 0, 5);
        }
    @endphp

    <x-slot:header>
        <div class="pb-5 mb-0 border-b border-rule">
            <div class="text-[0.7rem] font-semibold uppercase tracking-label text-mark-600 mb-1">Beranda</div>
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div>
                    <h2 class="font-display text-2xl sm:text-3xl font-bold text-ink-900 tracking-tight">Halo, {{ explode(' ', Auth::user()->name)[0] }}.</h2>
                    <p class="mt-1 text-xs sm:text-sm text-ink-700/60">
                        @if ($nextBooking)
                            Sesi berikutnya: {{ $nextLabel }} ({{ $nextBooking->booking_code }})
                        @else
                            Belum ada sesi mendatang.
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('booking.history') }}" class="btn-ghost btn-sm">Lihat Riwayat</a>
                    <a href="{{ route('calendar.index') }}" class="btn-mark btn-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Buat Reservasi
                    </a>
                </div>
            </div>
        </div>
    </x-slot:header>

    {{-- ── STAT CARDS ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">

        <div class="bg-white border border-rule rounded-xl p-4 sm:p-5 shadow-card flex flex-col gap-2 sm:gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-[9.5px] sm:text-[10px] font-bold uppercase tracking-label text-ink-700/40 leading-tight">Sesi Mendatang</span>
                <span class="w-7 h-7 rounded-md bg-ink-50 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5 stroke-ink-700" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
            </div>
            <div>
                <div class="font-mono text-[1.7rem] sm:text-[2rem] font-bold text-ink-900 tracking-tight leading-none">{{ $stats['upcoming_count'] }}</div>
                <div class="text-[10.5px] sm:text-[11.5px] text-ink-700/40 mt-1">{{ now()->translatedFormat('M Y') }}</div>
            </div>
        </div>

        <div class="bg-white border border-rule rounded-xl p-4 sm:p-5 shadow-card flex flex-col gap-2 sm:gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-[9.5px] sm:text-[10px] font-bold uppercase tracking-label text-ink-700/40 leading-tight">Total Bulan Ini</span>
                <span class="w-7 h-7 rounded-md flex items-center justify-center shrink-0" style="background:#d4f5ef">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="#2eb8a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </span>
            </div>
            <div>
                <div class="font-mono text-[1.7rem] sm:text-[2rem] font-bold text-ink-900 tracking-tight leading-none">{{ $stats['this_month_total'] }}</div>
                <div class="text-[10.5px] sm:text-[11.5px] text-ink-700/40 mt-1">{{ $monthNamesId[now()->month - 1] }} {{ now()->year }}</div>
            </div>
        </div>

        <div class="bg-white border border-rule rounded-xl p-4 sm:p-5 shadow-card flex flex-col gap-2 sm:gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-[9.5px] sm:text-[10px] font-bold uppercase tracking-label text-ink-700/40 leading-tight">Menunggu</span>
                <span class="w-7 h-7 rounded-md bg-mark-50 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5 stroke-mark-600" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
            </div>
            <div>
                <div class="font-mono text-[1.7rem] sm:text-[2rem] font-bold text-mark-600 tracking-tight leading-none">{{ $stats['pending_count'] }}</div>
                <div class="text-[10.5px] sm:text-[11.5px] text-ink-700/40 mt-1">{{ $stats['pending_code'] ?? '—' }}</div>
            </div>
        </div>

        <div class="bg-white border border-rule rounded-xl p-4 sm:p-5 shadow-card flex flex-col gap-2 sm:gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-[9.5px] sm:text-[10px] font-bold uppercase tracking-label text-ink-700/40 leading-tight">Total Pemakaian</span>
                <span class="w-7 h-7 rounded-md bg-ink-50 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5 stroke-ink-700" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
            </div>
            <div>
                <div class="font-mono text-[1.7rem] sm:text-[2rem] font-bold text-ink-900 tracking-tight leading-none">{{ $stats['total_hours'] }}h</div>
                <div class="text-[10.5px] sm:text-[11.5px] text-ink-700/40 mt-1">Disetujui / selesai</div>
            </div>
        </div>

    </div>

    {{-- ── CALENDAR CTA (replaces the old interactive calendar widget) ── --}}
    <a href="{{ route('calendar.index') }}"
       class="block bg-white border border-rule rounded-xl shadow-card p-5 mb-6 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-label text-ink-700/40 mb-1">Jadwal Lab</div>
                <div class="text-base font-bold text-ink-900 tracking-tight">Lihat Kalender Reservasi</div>
                <p class="text-sm text-ink-700/50 mt-1">Klik atau seret pada slot waktu untuk membuat reservasi baru.</p>
            </div>
            <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 transition-colors group-hover:bg-indigo-100" style="background:#eef0fe">
                <svg class="w-5 h-5" fill="none" stroke="#4f46e5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/></svg>
            </span>
        </div>
    </a>

    {{-- ── BOTTOM GRID: table + right panel ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-4">

        @php
            $typeLabelMap = [
                'full_room'      => 'Ruang + Komputer',
                'computers_only' => 'Komputer Saja',
                'room_only'      => 'Ruang Saja',
            ];
            $renderBookingRows = function ($collection) use ($monthShortId, $typeLabelMap) {
                if ($collection->isEmpty()) {
                    return '<tr><td colspan="4" style="padding:24px 0;text-align:center;color:rgba(10,26,71,.4);font-size:13px;">Belum ada reservasi pada tab ini.</td></tr>';
                }
                $out = '';
                foreach ($collection as $b) {
                    $d = $b->date;
                    $dateStr = $d->day . ' ' . $monthShortId[$d->month - 1] . ' ' . $d->year;
                    $time = substr($b->start_time, 0, 5) . ' — ' . substr($b->end_time, 0, 5);
                    $type = $typeLabelMap[$b->booking_type] ?? $b->booking_type;
                    $sub  = $b->booking_type === 'computers_only' ? $b->computers->count() . ' unit' : '—';
                    $badgeView = view('components.badge', ['status' => $b->status])->render();
                    $url = route('booking.show', $b);
                    $out .= '<tr onclick="window.location=\'' . $url . '\'" style="cursor:pointer">'
                          . '<td class="font-mono text-[12.5px] font-medium text-ink-700">' . e($b->booking_code) . '</td>'
                          . '<td><div class="text-sm font-semibold text-ink-900">' . $dateStr . '</div>'
                          . '<div class="font-mono text-[11.5px] text-ink-700/40 mt-0.5">' . $time . '</div></td>'
                          . '<td><div class="text-sm text-ink-700/70">' . e($type) . '</div>'
                          . '<div class="text-[11.5px] text-ink-700/40">' . $sub . '</div></td>'
                          . '<td><div class="flex items-center justify-end gap-2.5">' . $badgeView . '</div></td>'
                          . '</tr>';
                }
                return $out;
            };
            $upcomingHtml  = $renderBookingRows($upcomingBookings);
            $completedHtml = $renderBookingRows($completedBookings);
        @endphp

        {{-- Reservations table card --}}
        <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden order-2 lg:order-1">
            <div class="px-4 sm:px-5 pt-4 sm:pt-5 pb-0 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-label text-ink-700/60">Reservasi</span>
                    <span class="bg-ink-50 text-ink-700 text-[11px] font-bold px-2 py-0.5 rounded-full" id="bookings-count">{{ $upcomingBookings->count() }}</span>
                </div>
                <div class="pill-tabs">
                    <div class="pill-tab active" onclick="switchBookingTab(this,'mendatang')">Mendatang</div>
                    <div class="pill-tab" onclick="switchBookingTab(this,'selesai')">Selesai</div>
                </div>
            </div>
            <div class="px-4 sm:px-5 pb-4 sm:pb-5 pt-3 overflow-x-auto">
                <table class="bookings-tbl" style="min-width:460px">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal &amp; Waktu</th>
                            <th>Jenis</th>
                            <th style="text-align:right">Status</th>
                        </tr>
                    </thead>
                    <tbody id="bookings-tbody">{!! $upcomingHtml !!}</tbody>
                </table>
            </div>
        </div>

        {{-- Right panel: lab hours + status --}}
        <div class="bg-white border border-rule rounded-xl shadow-card order-1 lg:order-2">
            <div class="r-panel-section">
                <div class="text-[10px] font-bold uppercase tracking-label text-ink-700/40 mb-3.5">Jam Operasional Lab</div>
                <div class="hours-row">
                    <span class="text-[12.5px] text-ink-700/55 font-medium">Senin &#8212; Sabtu</span>
                    <span class="font-mono text-[12.5px] font-semibold text-ink-900">08:00 &#8211; 22:00</span>
                </div>
                <div class="hours-row">
                    <span class="text-[12.5px] text-ink-700/55 font-medium">Minggu</span>
                    <span class="text-[12px] text-ink-700/35 font-medium">Tutup</span>
                </div>
                <div class="mt-3.5 pt-3 border-t border-rule">
                    <div class="meta-row">
                        <span class="text-xs text-ink-700/40">Durasi maks.</span>
                        <span class="font-mono text-xs font-semibold text-ink-900">4 jam / sesi</span>
                    </div>
                    <div class="meta-row">
                        <span class="text-xs text-ink-700/40">Buffer antar sesi</span>
                        <span class="font-mono text-xs font-semibold text-ink-900">15 menit</span>
                    </div>
                </div>
            </div>
            <div class="r-panel-section">
                <div class="text-[10px] font-bold uppercase tracking-label text-ink-700/40 mb-3">Status Lab</div>
                <div class="flex items-baseline gap-1 mb-2.5">
                    <span class="font-mono text-[28px] font-bold text-ink-900 leading-none tracking-tight">8</span>
                    <span class="text-sm text-ink-700/40 font-medium">/ 9 unit tersedia</span>
                </div>
                <div class="pc-dot-row">
                    <div class="pc-dot"></div><div class="pc-dot"></div><div class="pc-dot"></div>
                    <div class="pc-dot"></div><div class="pc-dot"></div><div class="pc-dot"></div>
                    <div class="pc-dot"></div><div class="pc-dot"></div><div class="pc-dot mt"></div>
                </div>
                <div class="flex gap-3 mb-2">
                    <div class="flex items-center gap-1.5 text-[11px] text-ink-700/40">
                        <div class="w-2 h-2 rounded-sm" style="background:#2eb8a0"></div> Tersedia
                    </div>
                    <div class="flex items-center gap-1.5 text-[11px] text-ink-700/40">
                        <div class="w-2 h-2 rounded-sm bg-mark-500"></div> Perawatan
                    </div>
                </div>
                <p class="text-[11.5px] text-ink-700/40 leading-relaxed">1 unit dalam pemeliharaan terjadwal hingga Senin, 11 Mei.</p>
            </div>
        </div>

    </div>

@push('scripts')
<script>
const BOOKING_ROWS = {
    mendatang: {!! json_encode($upcomingHtml) !!},
    selesai:   {!! json_encode($completedHtml) !!},
};
const BOOKING_COUNTS = {
    mendatang: {{ $upcomingBookings->count() }},
    selesai:   {{ $completedBookings->count() }},
};
function switchBookingTab(el, mode) {
    document.querySelectorAll('.pill-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('bookings-tbody').innerHTML = BOOKING_ROWS[mode] || '';
    document.getElementById('bookings-count').textContent = BOOKING_COUNTS[mode] ?? 0;
}
</script>
@endpush

</x-app-layout>
