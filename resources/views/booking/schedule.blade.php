<x-app-layout>

@push('styles')
<style>
.bk-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.bk-cal-wd { text-align: center; font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: rgba(10,26,71,.38); padding: 6px 0; }
.bk-cal-day { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; border-radius: 50%; cursor: pointer; color: #0A1A47; transition: background .15s, color .15s, box-shadow .15s; margin: 0 auto; user-select: none; }
.bk-cal-day:hover:not(.is-disabled):not(.is-empty):not(.is-selected) { background: #EEF2FF; color: #1A3C8F; }
.bk-cal-day.is-empty { cursor: default; }
.bk-cal-day.is-disabled { color: rgba(10,26,71,.25); cursor: not-allowed; }
.bk-cal-day.is-today { background: #0A1A47; color: #fff; font-weight: 700; }
.bk-cal-day.is-selected { background: #F5B800; color: #0A1A47; font-weight: 700; box-shadow: 0 0 0 3px rgba(245,184,0,.18); }
.bk-cal-day.is-today.is-selected { background: #F5B800; color: #0A1A47; }
.bk-cal-nav { width: 30px; height: 30px; border-radius: 6px; border: 1px solid rgba(15,36,96,.08); background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; color: rgba(10,26,71,.5); transition: background .15s, color .15s; }
.bk-cal-nav:hover { background: #FAFAF7; color: #0A1A47; }
.bk-cal-nav svg { stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
</style>
@endpush

    <x-slot:header>
        <x-page-header
            eyebrow="Reservasi Baru"
            title="Buat Reservasi">
            <x-slot:actions>
                <x-step-indicator
                    :steps="['Pilih Jadwal', 'Informasi', 'Tinjau']"
                    :current="1" />
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-2xl mx-auto">

        @if (session('error'))
            <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('booking.logbook') }}" method="GET"
              x-data="bookingForm()"
              @submit.prevent="(selected && isoDate) && $el.submit()">

            <input type="hidden" name="date" :value="isoDate">

            {{-- ── § Tipe Reservasi ── --}}
            <x-section label="Tipe Reservasi">
                <div class="space-y-3">

                    {{-- Komputer Saja --}}
                    <label class="block cursor-pointer">
                        <input type="radio" name="type" value="computers_only" class="sr-only peer" x-model="selected">
                        <div class="flex items-start gap-5 p-5 rounded-xl border-2 border-rule bg-white
                                    peer-checked:border-ink-700 peer-checked:bg-ink-50/40
                                    hover:border-ink-300 transition-all duration-150">
                            <div class="w-10 h-10 rounded-lg bg-ink-50 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-ink-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                          d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-ink-900">Komputer Saja</span>
                                    <span class="text-[10px] uppercase tracking-label font-semibold text-ink-700/50 border border-rule rounded px-1.5 py-0.5">
                                        Pilih unit spesifik
                                    </span>
                                </div>
                                <p class="text-sm text-ink-700/60 mt-1">
                                    Pesan satu atau lebih unit komputer tertentu (PC-01 hingga PC-09) tanpa memonopoli seluruh ruangan.
                                </p>
                            </div>
                        </div>
                    </label>

                    {{-- Ruang + Komputer --}}
                    <label class="block cursor-pointer">
                        <input type="radio" name="type" value="full_room" class="sr-only peer" x-model="selected">
                        <div class="flex items-start gap-5 p-5 rounded-xl border-2 border-rule bg-white
                                    peer-checked:border-mark-500 peer-checked:bg-mark-50/60
                                    hover:border-mark-300 transition-all duration-150">
                            <div class="w-10 h-10 rounded-lg bg-mark-50 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-mark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-ink-900">Ruang + Komputer</span>
                                    <span class="text-[10px] uppercase tracking-label font-semibold text-mark-600/70 border border-mark-300/60 rounded px-1.5 py-0.5 bg-mark-50">
                                        Seluruh lab
                                    </span>
                                </div>
                                <p class="text-sm text-ink-700/60 mt-1">
                                    Pesan seluruh ruangan beserta semua 9 unit komputer yang tersedia. Cocok untuk praktikum kelas penuh.
                                </p>
                            </div>
                        </div>
                    </label>

                    {{-- Ruang Saja --}}
                    <label class="block cursor-pointer">
                        <input type="radio" name="type" value="room_only" class="sr-only peer" x-model="selected">
                        <div class="flex items-start gap-5 p-5 rounded-xl border-2 border-rule bg-white
                                    peer-checked:border-ink-700 peer-checked:bg-ink-50/40
                                    hover:border-ink-300 transition-all duration-150">
                            <div class="w-10 h-10 rounded-lg bg-ink-50 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-ink-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                          d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <span class="font-semibold text-ink-900">Ruang Saja</span>
                                <p class="text-sm text-ink-700/60 mt-1">
                                    Pesan ruangan tanpa komputer. Pilihan berbagi ruang tersedia jika slot lain masih memungkinkan.
                                </p>
                                <div class="mt-4 space-y-2" x-show="selected === 'room_only'" x-transition>
                                    <p class="text-xs font-semibold uppercase tracking-label text-ink-700/60 mb-2">Mode penggunaan</p>
                                    <label class="flex items-center gap-3 p-3 rounded-lg border border-rule bg-white hover:bg-ink-50/60 cursor-pointer has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/40 transition-all">
                                        <input type="radio" name="room_sharing" value="exclusive" class="accent-ink-700"
                                               x-model="roomSharing"
                                               {{ ($draft['room_sharing'] ?? null) === 'exclusive' ? 'checked' : '' }}>
                                        <div>
                                            <div class="text-sm font-medium text-ink-900">Eksklusif</div>
                                            <div class="text-xs text-ink-700/50">Ruangan hanya untuk Anda, tidak ada pengguna lain di slot yang sama</div>
                                        </div>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 rounded-lg border border-rule bg-white hover:bg-ink-50/60 cursor-pointer has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/40 transition-all">
                                        <input type="radio" name="room_sharing" value="shared" class="accent-ink-700"
                                               x-model="roomSharing"
                                               {{ ($draft['room_sharing'] ?? null) === 'shared' ? 'checked' : '' }}>
                                        <div>
                                            <div class="text-sm font-medium text-ink-900">Berbagi</div>
                                            <div class="text-xs text-ink-700/50">Ruangan dapat digunakan bersama dengan pemohon lain di slot yang sama</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </label>

                </div>
            </x-section>

            {{-- ── § Tanggal (calendar) ── --}}
            <x-section label="Tanggal">
                <div class="bg-white border border-rule rounded-xl p-4 sm:p-5">

                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-bold text-ink-900 tracking-tight" x-text="monthLabel"></span>
                        <div class="flex items-center gap-1.5">
                            <button type="button" class="bk-cal-nav" @click="nav(-1)" aria-label="Bulan sebelumnya">
                                <svg width="14" height="14" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                            </button>
                            <button type="button" class="bk-cal-nav" @click="nav(1)" aria-label="Bulan berikutnya">
                                <svg width="14" height="14" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="bk-cal-grid mb-1">
                        <div class="bk-cal-wd">Min</div><div class="bk-cal-wd">Sen</div><div class="bk-cal-wd">Sel</div>
                        <div class="bk-cal-wd">Rab</div><div class="bk-cal-wd">Kam</div><div class="bk-cal-wd">Jum</div>
                        <div class="bk-cal-wd">Sab</div>
                    </div>

                    <div class="bk-cal-grid">
                        <template x-for="cell in cells" :key="cell.key">
                            <div class="bk-cal-day"
                                 :class="cellClass(cell)"
                                 @click="!cell.empty && !cell.disabled && pick(cell.day)"
                                 x-text="cell.label"></div>
                        </template>
                    </div>

                    <p class="text-xs text-ink-700/50 mt-3" x-show="!isoDate">
                        Pilih tanggal — Minggu tutup, hari lampau tidak tersedia.
                    </p>
                    <p class="text-xs text-ink-700/70 mt-3" x-show="isoDate" x-cloak>
                        Tanggal terpilih: <span class="font-mono font-semibold text-ink-900" x-text="prettyDate"></span>
                    </p>
                </div>
            </x-section>

            {{-- ── § Waktu ── --}}
            <x-section label="Waktu">
                @php
                    $draftStart = $draft['start_time'] ?? '';
                    $draftEnd   = $draft['end_time'] ?? '';
                @endphp
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-field">
                        <label class="form-label form-required">Waktu Mulai</label>
                        <select name="start_time" class="form-select" required x-model="startTime">
                            <option value="" disabled {{ $draftStart === '' ? 'selected' : '' }}>Pilih jam…</option>
                            @for ($h = 8; $h <= 21; $h++)
                                @php $t1 = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00'; @endphp
                                <option value="{{ $t1 }}" {{ $draftStart === $t1 ? 'selected' : '' }}>{{ $t1 }}</option>
                                @php $t2 = str_pad($h, 2, '0', STR_PAD_LEFT) . ':30'; @endphp
                                <option value="{{ $t2 }}" {{ $draftStart === $t2 ? 'selected' : '' }}>{{ $t2 }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label form-required">Waktu Selesai</label>
                        <select name="end_time" class="form-select" required x-model="endTime">
                            <option value="" disabled {{ $draftEnd === '' ? 'selected' : '' }}>Pilih jam…</option>
                            @for ($h = 8; $h <= 22; $h++)
                                @php $e1 = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00'; @endphp
                                <option value="{{ $e1 }}" {{ $draftEnd === $e1 ? 'selected' : '' }}>{{ $e1 }}</option>
                                @if ($h < 22)
                                    @php $e2 = str_pad($h, 2, '0', STR_PAD_LEFT) . ':30'; @endphp
                                    <option value="{{ $e2 }}" {{ $draftEnd === $e2 ? 'selected' : '' }}>{{ $e2 }}</option>
                                @endif
                            @endfor
                        </select>
                    </div>
                </div>
            </x-section>

            {{-- ── § Pilih Unit Komputer (hidden when type = room_only) ── --}}
            <div x-show="selected !== 'room_only'" x-transition>
                <x-section label="Pilih Unit Komputer">

                    {{-- Status banner driven by pcLoadingState --}}
                    <div class="mb-4 min-h-[20px]">
                        <template x-if="pcLoadingState === 'idle'">
                            <p class="text-sm text-ink-700/60">
                                Pilih tanggal &amp; waktu di atas untuk melihat ketersediaan unit pada slot tersebut.
                            </p>
                        </template>
                        <template x-if="pcLoadingState === 'loading'">
                            <p class="text-sm text-ink-700/60 flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8"/></svg>
                                Memuat ketersediaan unit untuk slot ini…
                            </p>
                        </template>
                        <template x-if="pcLoadingState === 'loaded'">
                            <p class="text-sm text-ink-700/60">
                                Pilih unit <span class="font-semibold text-emerald-600">Tersedia</span>.
                                Unit <span class="font-semibold text-amber-600">Terpakai</span> sudah dipesan pada slot ini.
                            </p>
                        </template>
                        <template x-if="pcLoadingState === 'error'">
                            <p class="text-sm text-red-600">
                                Gagal memuat ketersediaan unit. Coba ubah waktu untuk mencoba lagi.
                            </p>
                        </template>
                    </div>

                    {{-- Per-PC dynamic grid --}}
                    <div class="grid grid-cols-3 gap-3">
                        @foreach ($computers as $computer)
                            @php
                                $hwDisabled = ($computer->status ?? 'online') !== 'online';
                                $isSelected = in_array($computer->id, $draft['computers'] ?? []);
                                $hwStatusLabel = match($computer->status ?? 'online') {
                                    'online'      => 'Tersedia',
                                    'maintenance' => 'Pemeliharaan',
                                    'offline'     => 'Nonaktif',
                                    default       => 'Tersedia',
                                };
                            @endphp

                            <label class="relative flex flex-col items-center justify-center gap-1 aspect-square rounded-md
                                          border border-rule-strong transition-all
                                          @if (! $hwDisabled) hover:border-ink-500 has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/60 has-[:checked]:shadow-subtle cursor-pointer @else bg-ink-50/40 opacity-60 cursor-not-allowed @endif"
                                   :class="{
                                       'opacity-50 cursor-not-allowed pointer-events-none': getPcState({{ $computer->id }}) && !getPcState({{ $computer->id }}).available,
                                       'ring-1 ring-emerald-300': getPcState({{ $computer->id }}) && getPcState({{ $computer->id }}).available,
                                   }">

                                <input type="checkbox" name="computers[]" value="{{ $computer->id }}"
                                       {{ $isSelected ? 'checked' : '' }}
                                       @if ($hwDisabled) disabled @endif
                                       :disabled="(getPcState({{ $computer->id }}) && !getPcState({{ $computer->id }}).available) || {{ $hwDisabled ? 'true' : 'false' }}"
                                       class="sr-only peer">

                                {{-- Checkbox indicator --}}
                                <span class="absolute top-2 right-2 w-4 h-4 rounded border border-rule-strong
                                             peer-checked:bg-mark-500 peer-checked:border-mark-500
                                             flex items-center justify-center transition-all">
                                    <svg class="w-3 h-3 text-ink-900 opacity-0 peer-checked:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>

                                {{-- Slot status badge (top-left) — shown only when slot data is loaded --}}
                                <template x-if="getPcState({{ $computer->id }})">
                                    <span class="absolute top-2 left-2 text-[8.5px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded"
                                          :class="getPcState({{ $computer->id }}).available
                                              ? 'bg-emerald-100 text-emerald-700'
                                              : (getPcState({{ $computer->id }}).status !== 'online' ? 'bg-ink-100 text-ink-700/60' : 'bg-amber-100 text-amber-700')">
                                        <span x-text="getPcState({{ $computer->id }}).available
                                              ? 'Tersedia'
                                              : (getPcState({{ $computer->id }}).status !== 'online' ? 'Perawatan' : 'Terpakai')"></span>
                                    </span>
                                </template>

                                <svg class="w-7 h-7 text-ink-700/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span class="font-mono text-sm font-semibold text-ink-900">{{ $computer->label }}</span>
                                <span class="text-[0.6rem] uppercase tracking-label font-semibold text-ink-700/50">{{ $hwStatusLabel }}</span>
                            </label>
                        @endforeach
                    </div>

                    <p class="form-hint mt-3">Pilih minimal 1 unit. Unit yang sedang Terpakai pada slot ini akan dinonaktifkan otomatis.</p>
                </x-section>
            </div>

            {{-- ── § Indikator Ketersediaan ── --}}
            <div class="mt-6" x-show="canCheck" x-cloak>
                <div class="rounded-lg border px-4 py-3 flex items-center gap-3 text-sm"
                     :class="availClass">
                    <template x-if="availStatus === 'loading'">
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8"/></svg>
                    </template>
                    <template x-if="availStatus === 'available'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="availStatus === 'conflict' || availStatus === 'error'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><line x1="12" y1="8"  x2="12" y2="13" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/></svg>
                    </template>
                    <span x-text="availMessage"></span>
                </div>
            </div>

            <div class="flex items-center justify-between pt-6 border-t border-rule mt-6">
                <a href="{{ route('dashboard') }}" class="btn-ghost">
                    ← Batal
                </a>
                <button type="submit"
                        :disabled="!selected || !isoDate || availStatus === 'conflict'"
                        :class="(!selected || !isoDate || availStatus === 'conflict') ? 'btn-mark btn-lg opacity-40 cursor-not-allowed' : 'btn-mark btn-lg'">
                    Lanjut: Isi Informasi
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

        </form>
    </div>

@push('scripts')
<script>
function bookingForm() {
    const MONTHS_ID = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const DAYS_ID = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const draft = @json($draft ?? null);
    const draftDate = draft && draft.date ? draft.date : '';
    const draftYear = draftDate ? parseInt(draftDate.slice(0, 4), 10) : today.getFullYear();
    const draftMonth = draftDate ? parseInt(draftDate.slice(5, 7), 10) - 1 : today.getMonth();
    const checkUrl       = @json(route('api.availability.check'));
    const pcAvailUrl     = @json(route('api.availability.computers'));

    return {
        selected: draft && draft.type ? draft.type : '',
        isoDate: draftDate,
        startTime: draft && draft.start_time ? draft.start_time : '',
        endTime:   draft && draft.end_time   ? draft.end_time   : '',
        roomSharing: draft && draft.room_sharing ? draft.room_sharing : '',
        year: draftYear,
        month: draftMonth,
        cells: [],

        availStatus: 'idle',     // idle | loading | available | conflict | error
        availMessage: '',
        availTimer: null,

        // Per-PC availability for the selected slot (driven by /api/computers/available)
        pcAvailability: [],
        pcLoadingState: 'idle',  // idle | loading | loaded | error
        pcTimer: null,

        init() {
            this.build();
            // Watch the fields that affect availability
            ['selected', 'isoDate', 'startTime', 'endTime', 'roomSharing'].forEach(prop => {
                this.$watch(prop, () => this.scheduleAvailabilityCheck());
            });
            // Watch the time-only fields → trigger per-PC availability load
            ['isoDate', 'startTime', 'endTime'].forEach(prop => {
                this.$watch(prop, () => this.schedulePcAvailabilityLoad());
            });
            // Watch computer checkboxes by listening to their change events
            this.$el.querySelectorAll('input[name="computers[]"]').forEach(cb => {
                cb.addEventListener('change', () => this.scheduleAvailabilityCheck());
            });
            // Initial check if we have a hydrated draft
            this.$nextTick(() => {
                this.scheduleAvailabilityCheck();
                this.schedulePcAvailabilityLoad();
            });
        },

        schedulePcAvailabilityLoad() {
            if (!this.isoDate || !this.startTime || !this.endTime) {
                this.pcAvailability = [];
                this.pcLoadingState = 'idle';
                return;
            }
            if (this.startTime >= this.endTime) return;
            if (this.pcTimer) clearTimeout(this.pcTimer);
            this.pcTimer = setTimeout(() => this.loadPcAvailability(), 300);
        },

        async loadPcAvailability() {
            this.pcLoadingState = 'loading';
            const params = new URLSearchParams({
                date: this.isoDate,
                start_time: this.startTime,
                end_time: this.endTime,
            });
            try {
                const res = await fetch(pcAvailUrl + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('http ' + res.status);
                const data = await res.json();
                this.pcAvailability = data.computers;
                this.pcLoadingState = 'loaded';
                // Deselect any currently-checked PCs that are no longer available
                this.$nextTick(() => {
                    let changed = false;
                    this.$el.querySelectorAll('input[name="computers[]"]:checked').forEach(cb => {
                        const pcId = parseInt(cb.value, 10);
                        const pc = this.pcAvailability.find(p => p.id === pcId);
                        if (pc && !pc.available) { cb.checked = false; changed = true; }
                    });
                    if (changed) this.scheduleAvailabilityCheck();
                });
            } catch (e) {
                this.pcLoadingState = 'error';
            }
        },

        getPcState(pcId) {
            if (this.pcLoadingState !== 'loaded' || !this.pcAvailability.length) return null;
            return this.pcAvailability.find(p => p.id === pcId) || null;
        },

        get canCheck() {
            if (!this.selected || !this.isoDate || !this.startTime || !this.endTime) return false;
            if (this.startTime >= this.endTime) return false;
            if (this.selected === 'room_only' && !this.roomSharing) return false;
            return true;
        },

        get availClass() {
            return {
                'border-rule bg-white text-ink-700/60': this.availStatus === 'loading',
                'border-emerald-200 bg-emerald-50 text-emerald-700': this.availStatus === 'available',
                'border-red-200 bg-red-50 text-red-700': this.availStatus === 'conflict' || this.availStatus === 'error',
            };
        },

        getSelectedComputers() {
            return Array.from(this.$el.querySelectorAll('input[name="computers[]"]:checked'))
                .map(el => parseInt(el.value, 10));
        },

        scheduleAvailabilityCheck() {
            if (!this.canCheck) {
                this.availStatus = 'idle';
                this.availMessage = '';
                return;
            }
            if (this.availTimer) clearTimeout(this.availTimer);
            this.availTimer = setTimeout(() => this.runAvailabilityCheck(), 250);
        },

        async runAvailabilityCheck() {
            this.availStatus = 'loading';
            this.availMessage = 'Memeriksa ketersediaan…';
            const params = new URLSearchParams();
            params.append('type', this.selected);
            params.append('date', this.isoDate);
            params.append('start_time', this.startTime);
            params.append('end_time', this.endTime);
            if (this.selected === 'room_only' && this.roomSharing) {
                params.append('room_sharing', this.roomSharing);
            }
            if (this.selected === 'computers_only') {
                this.getSelectedComputers().forEach(id => params.append('computers[]', id));
            }
            try {
                const res = await fetch(checkUrl + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('http ' + res.status);
                const data = await res.json();
                this.availStatus  = data.available ? 'available' : 'conflict';
                this.availMessage = data.message;
            } catch (e) {
                this.availStatus = 'error';
                this.availMessage = 'Gagal memeriksa ketersediaan. Coba lagi.';
            }
        },

        get monthLabel() {
            return MONTHS_ID[this.month] + ' ' + this.year;
        },

        get prettyDate() {
            if (!this.isoDate) return '';
            const [y, m, d] = this.isoDate.split('-').map(Number);
            const dt = new Date(y, m - 1, d);
            return DAYS_ID[dt.getDay()] + ', ' + d + ' ' + MONTHS_ID[m - 1] + ' ' + y;
        },

        nav(dir) {
            this.month += dir;
            if (this.month > 11) { this.month = 0; this.year++; }
            if (this.month < 0)  { this.month = 11; this.year--; }
            this.build();
        },

        pick(day) {
            const mm = String(this.month + 1).padStart(2, '0');
            const dd = String(day).padStart(2, '0');
            this.isoDate = this.year + '-' + mm + '-' + dd;
            this.build();
        },

        cellClass(cell) {
            const out = [];
            if (cell.empty) out.push('is-empty', 'is-disabled');
            else {
                if (cell.disabled) out.push('is-disabled');
                if (cell.today) out.push('is-today');
                if (cell.selected) out.push('is-selected');
            }
            return out.join(' ');
        },

        build() {
            const firstDay = new Date(this.year, this.month, 1).getDay();
            const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
            const out = [];

            for (let i = 0; i < firstDay; i++) {
                out.push({ key: 'lead-' + i, day: null, label: '', empty: true, disabled: true });
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const date = new Date(this.year, this.month, d);
                date.setHours(0, 0, 0, 0);
                const isSunday = date.getDay() === 0;
                const isPast = date < today;
                const isToday = date.getTime() === today.getTime();
                const isSelected = this.isoDate ===
                    this.year + '-' + String(this.month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');

                out.push({
                    key: this.year + '-' + this.month + '-' + d,
                    day: d,
                    label: String(d),
                    empty: false,
                    disabled: isSunday || isPast,
                    today: isToday,
                    selected: isSelected,
                });
            }

            this.cells = out;
        },
    };
}
</script>
@endpush

</x-app-layout>
