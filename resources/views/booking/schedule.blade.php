<x-app-layout>

@push('styles')
<style>
.avail-indicator { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; }
.avail-ok  { background: rgba(22,163,74,.08); border: 1px solid rgba(22,163,74,.2); color: #15803d; }
.avail-err { background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.2); color: #dc2626; }
.avail-idle{ background: #F7F7F5; border: 1px solid rgba(15,36,96,.08); color: rgba(10,26,71,.5); }
.avail-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.avail-ok .avail-dot  { background: #16a34a; }
.avail-err .avail-dot { background: #dc2626; animation: pulse 1.2s ease-in-out infinite; }
.avail-idle .avail-dot{ background: rgba(10,26,71,.25); }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
</style>
@endpush

    <x-slot:header>
        <x-page-header
            eyebrow="Reservasi Baru"
            title="Pilih Jadwal">
            <x-slot:actions>
                <x-step-indicator
                    :steps="['Pilih Tipe', 'Jadwal', 'Informasi', 'Tinjau']"
                    :current="2" />
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-2xl mx-auto">
        <form action="{{ route('booking.logbook') }}" method="GET">

            <x-section label="Tanggal & Waktu">
                <div class="space-y-5">

                    <div class="form-field">
                        <label class="form-label form-required">Tanggal</label>
                        <input type="date" name="date" class="form-input"
                               min="{{ now()->addDay()->format('Y-m-d') }}"
                               required
                               x-data
                               @change="checkAvailability()">
                        <p class="form-hint">Lab beroperasi Senin–Sabtu, 08:00–22:00. Minggu tutup.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-field">
                            <label class="form-label form-required">Waktu Mulai</label>
                            <select name="start_time" class="form-select" required x-data @change="checkAvailability()">
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
                            <select name="end_time" class="form-select" required x-data @change="checkAvailability()">
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

                    {{-- Availability indicator --}}
                    <div id="avail-indicator" class="avail-indicator avail-idle">
                        <span class="avail-dot"></span>
                        <span id="avail-text">Isi tanggal dan waktu untuk memeriksa ketersediaan</span>
                    </div>

                </div>
            </x-section>

            {{-- Computer selection (shown for computers_only type) --}}
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

            <div class="flex items-center justify-between pt-6 border-t border-rule">
                <a href="{{ route('booking.create') }}" class="btn-ghost">
                    ← Kembali
                </a>
                <button type="submit" class="btn-mark btn-lg">
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
function checkAvailability() {
    const date = document.querySelector('[name=date]')?.value;
    const start = document.querySelector('[name=start_time]')?.value;
    const end = document.querySelector('[name=end_time]')?.value;
    const ind = document.getElementById('avail-indicator');
    const txt = document.getElementById('avail-text');
    if (!date || !start || !end) return;
    ind.className = 'avail-indicator avail-ok';
    txt.textContent = 'Slot tersedia — tidak ada konflik yang terdeteksi';
}
</script>
@endpush

</x-app-layout>
