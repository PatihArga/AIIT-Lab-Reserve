<x-app-layout>

    <x-slot:header>
        <div class="pb-5 mb-0 border-b border-rule">
            <div class="text-[0.7rem] font-semibold uppercase tracking-label text-mark-600 mb-1">Catatan</div>
            <h2 class="font-display text-2xl sm:text-3xl font-bold text-ink-900 tracking-tight">Logbook</h2>
            <p class="mt-1 text-xs sm:text-sm text-ink-700/60">
                Catatan kegiatan untuk setiap reservasi lab kamu.
            </p>
        </div>
    </x-slot:header>

    @php
        // Type → [label, dot colour] (same mapping used on the calendar).
        $typeMeta = function ($b) {
            return match (true) {
                $b->booking_type === 'computers_only' => ['Komputer Saja', '#4f46e5'],
                $b->booking_type === 'full_room'      => ['Ruang + Komputer', '#7c3aed'],
                $b->room_sharing === 'exclusive'      => ['Ruang Eksklusif', '#0d9488'],
                default                               => ['Ruang Berbagi', '#d97706'],
            };
        };
    @endphp

    @if ($errors->any())
        <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-5">
        @forelse ($bookings as $booking)
            @php [$typeLabel, $typeColor] = $typeMeta($booking); @endphp

            <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">

                {{-- Row 1 — booking header --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-5 py-3.5 bg-ink-50/40 border-b border-rule">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="font-mono text-xs font-semibold text-ink-700 bg-white border border-rule-strong rounded px-2 py-1">
                            {{ $booking->booking_code }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-sm font-semibold" style="color: {{ $typeColor }}">
                            <span class="w-2 h-2 rounded-full" style="background: {{ $typeColor }}"></span>
                            {{ $typeLabel }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 text-sm text-ink-700/60">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-ink-700/40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/></svg>
                            {{ $booking->date->translatedFormat('d M Y') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 font-mono">
                            <svg class="w-4 h-4 text-ink-700/40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
                            {{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}
                        </span>
                    </div>
                </div>

                {{-- Row 2 — logbook notes (editable) --}}
                @php $installOn = old('needs_installation', $booking->logbook->needs_installation ?? false); @endphp
                <form method="POST" action="{{ route('logbook.update', $booking) }}" class="px-5 py-5"
                      x-data="{ install: {{ $installOn ? 'true' : 'false' }} }">
                    @csrf
                    @method('PUT')

                    <div class="text-[10px] font-bold uppercase tracking-label text-ink-700/40 mb-2.5">Catatan Logbook</div>

                    {{-- Auto-grows to fit its content so long notes don't scroll inside a fixed box --}}
                    <textarea name="checkpoint_progress" rows="4" required
                              class="form-textarea"
                              style="resize:none; overflow:hidden;"
                              x-data
                              x-init="$nextTick(() => { $el.style.height='auto'; $el.style.height=($el.scrollHeight + $el.offsetHeight - $el.clientHeight)+'px'; })"
                              @input="$el.style.height='auto'; $el.style.height=($el.scrollHeight + $el.offsetHeight - $el.clientHeight)+'px'"
                              placeholder="Tulis catatan kegiatan kamu untuk sesi ini… (min. 10 karakter)">{{ old('checkpoint_progress', $booking->logbook->checkpoint_progress ?? '') }}</textarea>

                    {{-- Software installation report — checkbox defaults to the reservation choice --}}
                    <div class="mt-4 pt-4 border-t border-rule">
                        {{-- Hidden field so the boolean always posts, even when unchecked --}}
                        <input type="hidden" name="needs_installation" :value="install ? '1' : '0'">

                        <label class="flex items-start gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" x-model="install"
                                   class="mt-0.5 h-4 w-4 rounded border-rule-strong" style="accent-color:#4f46e5;">
                            <span class="text-sm font-medium text-ink-900 leading-tight">
                                Instalasi perangkat lunak
                                <span class="block mt-0.5 text-xs font-normal text-ink-700/50">Centang jika kamu mengunduh atau memasang perangkat lunak pada sesi ini.</span>
                            </span>
                        </label>

                        {{-- Software list — revealed only when the box is checked --}}
                        <div x-show="install"
                             style="{{ $installOn ? '' : 'display:none' }}"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="mt-3">
                            <div class="text-[10px] font-bold uppercase tracking-label text-ink-700/40 mb-2">Perangkat Lunak yang Diunduh / Diinstal</div>
                            <textarea name="special_software" rows="3" :required="install"
                                      class="form-textarea" style="resize:none;"
                                      placeholder="Contoh: Python 3.12, Visual Studio Code, MATLAB R2024a…">{{ old('special_software', $booking->logbook->special_software ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-3">
                        <button type="submit" class="btn-mark btn-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                            Simpan Catatan
                        </button>
                    </div>
                </form>
            </div>
        @empty
            <div class="bg-white border border-rule rounded-xl shadow-card px-6 py-16 text-center">
                <div class="w-12 h-12 mx-auto rounded-xl bg-ink-50 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-ink-700/40" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </div>
                <p class="text-sm font-medium text-ink-900">Belum ada catatan logbook</p>
                <p class="text-xs text-ink-700/50 mt-1">Reservasi yang sudah disetujui akan muncul di sini untuk kamu catat.</p>
            </div>
        @endforelse
    </div>

    @if ($bookings->hasPages())
        <div class="mt-6">
            {{ $bookings->links() }}
        </div>
    @endif

</x-app-layout>
