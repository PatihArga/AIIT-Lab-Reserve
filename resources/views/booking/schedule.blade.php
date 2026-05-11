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
                                        <input type="radio" name="room_sharing" value="exclusive" class="accent-ink-700">
                                        <div>
                                            <div class="text-sm font-medium text-ink-900">Eksklusif</div>
                                            <div class="text-xs text-ink-700/50">Ruangan hanya untuk Anda, tidak ada pengguna lain di slot yang sama</div>
                                        </div>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 rounded-lg border border-rule bg-white hover:bg-ink-50/60 cursor-pointer has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/40 transition-all">
                                        <input type="radio" name="room_sharing" value="shared" class="accent-ink-700">
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
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-field">
                        <label class="form-label form-required">Waktu Mulai</label>
                        <select name="start_time" class="form-select" required>
                            <option value="" disabled selected>Pilih jam…</option>
                            @for ($h = 8; $h <= 21; $h++)
                                <option value="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00">
                                    {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00
                                </option>
                                <option value="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:30">
                                    {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:30
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label form-required">Waktu Selesai</label>
                        <select name="end_time" class="form-select" required>
                            <option value="" disabled selected>Pilih jam…</option>
                            @for ($h = 8; $h <= 22; $h++)
                                <option value="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00">
                                    {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00
                                </option>
                                @if ($h < 22)
                                <option value="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:30">
                                    {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:30
                                </option>
                                @endif
                            @endfor
                        </select>
                    </div>
                </div>
            </x-section>

            {{-- ── § Pilih Unit Komputer (hidden when type = room_only) ── --}}
            <div x-show="selected !== 'room_only'" x-transition>
                <x-section label="Pilih Unit Komputer">
                    <p class="text-sm text-ink-700/60 mb-4">
                        Pilih unit komputer yang ingin Anda gunakan. Unit yang diarsir sedang dalam pemeliharaan.
                    </p>
                    @php
                        $dummyComputers = collect(range(1, 9))->map(fn($n) => (object)[
                            'id'     => $n,
                            'label'  => 'PC-' . str_pad($n, 2, '0', STR_PAD_LEFT),
                            'status' => $n === 9 ? 'maintenance' : 'online',
                        ]);
                    @endphp
                    <x-computer-grid :computers="$dummyComputers" :selectable="true" name="computers" />
                    <p class="form-hint mt-3">Pilih minimal 1 unit. Anda dapat memilih hingga semua unit yang tersedia.</p>
                </x-section>
            </div>

            <div class="flex items-center justify-between pt-6 border-t border-rule">
                <a href="{{ route('dashboard') }}" class="btn-ghost">
                    ← Batal
                </a>
                <button type="submit"
                        :disabled="!selected || !isoDate"
                        :class="(!selected || !isoDate) ? 'btn-mark btn-lg opacity-40 cursor-not-allowed' : 'btn-mark btn-lg'">
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

    return {
        selected: '',
        isoDate: '',
        year: today.getFullYear(),
        month: today.getMonth(),
        cells: [],

        init() {
            this.build();
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
