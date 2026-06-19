<x-app-layout>

@push('styles')
<style>
    @media print {
        /* Force accent dots / tags / hairlines to print */
        *, *::before, *::after {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body { background: #fff !important; }
        @page { margin: 14mm; }
        .audit-entry, .print-keep { break-inside: avoid; }
        /* Reveal every diff panel on paper, regardless of toggle state */
        .audit-diff { display: block !important; }
    }
</style>
@endpush

    <x-slot:header>
        <x-page-header
            eyebrow="Sistem"
            title="Audit Log"
            meta="Riwayat semua aktivitas sistem">
            <x-slot:actions>
                <button type="button" onclick="window.print()" class="btn-secondary btn-sm print:hidden" title="Cetak atau simpan sebagai PDF">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Cetak PDF
                </button>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    {{-- System-wide overview (independent of the filters) --}}
    <div class="print-keep grid grid-cols-2 sm:grid-cols-4 gap-px bg-rule border border-rule rounded-xl shadow-card overflow-hidden mb-6">
        <div class="bg-white px-4 py-4">
            <div class="font-display text-2xl font-extrabold text-ink-900 leading-none">{{ $stats['total'] }}</div>
            <div class="text-xs text-ink-700/50 mt-1.5">Total aktivitas</div>
        </div>
        <div class="bg-white px-4 py-4">
            <div class="font-display text-2xl font-extrabold text-mark-600 leading-none">{{ $stats['today'] }}</div>
            <div class="text-xs text-ink-700/50 mt-1.5">Hari ini</div>
        </div>
        <div class="bg-white px-4 py-4">
            <div class="font-display text-2xl font-extrabold text-ink-900 leading-none">{{ $stats['processed'] }}</div>
            <div class="text-xs text-ink-700/50 mt-1.5">Reservasi diproses</div>
        </div>
        <div class="bg-white px-4 py-4">
            <div class="font-display text-2xl font-extrabold text-ink-900 leading-none">{{ $stats['active_users'] }}</div>
            <div class="text-xs text-ink-700/50 mt-1.5">Pengguna aktif</div>
        </div>
    </div>

    @php
        // Action filter is now a checkbox group. Without the `af` marker
        // (fresh load) every box defaults to checked → all actions shown.
        $afApplied     = request()->has('af');
        $selected      = (array) request('actions', []);
        $selectedCount = $afApplied ? count(array_intersect($actions->all(), $selected)) : $actions->count();
        $allSelected   = $selectedCount === $actions->count();
    @endphp

    {{-- Filter bar (GET form; selects/dates auto-submit) --}}
    <form method="GET" action="{{ route('admin.audit-log.index') }}" class="flex items-center gap-3 mb-6 flex-wrap print:hidden">
        {{-- Marker so we can tell "fresh load" (show all) from "everything unchecked" (show none) --}}
        <input type="hidden" name="af" value="1">

        {{-- Action filter — checkbox group in a dropdown --}}
        <div x-data="{ open: false, setAll(v) { $refs.actionList.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = v) } }"
             class="relative">
            <button type="button" @click="open = !open"
                    class="py-1.5 px-3 text-xs w-44 bg-white border border-rule-strong rounded-md text-ink-900 flex items-center justify-between gap-2 hover:bg-ink-50 transition-colors">
                <span>{{ $allSelected ? 'Semua aksi' : 'Aksi · ' . $selectedCount }}</span>
                <svg class="w-3 h-3 text-ink-700/40 transition-transform shrink-0" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
            </button>

            <div x-show="open" @click.outside="open = false" style="display:none"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute z-30 mt-1 w-64 bg-white border border-rule rounded-lg shadow-modal p-2">
                <div class="flex items-center justify-between px-1.5 pb-2 mb-1 border-b border-rule">
                    <span class="text-[11px] font-semibold uppercase tracking-label text-ink-700/50">Filter Aksi</span>
                    <div class="flex items-center gap-2 text-[11px] font-medium">
                        <button type="button" @click="setAll(true)" class="text-mark-600 hover:text-mark-700">Pilih semua</button>
                        <span class="text-rule-strong">·</span>
                        <button type="button" @click="setAll(false)" class="text-ink-700/50 hover:text-ink-900">Kosongkan</button>
                    </div>
                </div>
                <div x-ref="actionList" class="max-h-60 overflow-y-auto space-y-0.5 pr-1">
                    @forelse ($actions as $a)
                        <label class="flex items-center gap-2.5 px-1.5 py-1.5 rounded-md hover:bg-ink-50 cursor-pointer">
                            <input type="checkbox" name="actions[]" value="{{ $a }}"
                                   class="h-3.5 w-3.5 rounded border-rule-strong" style="accent-color:#0A1A47;"
                                   {{ (! $afApplied || in_array($a, $selected, true)) ? 'checked' : '' }}>
                            <span class="font-mono text-[11px] text-ink-700/80">{{ $a }}</span>
                        </label>
                    @empty
                        <p class="px-1.5 py-2 text-xs text-ink-700/40">Belum ada aksi tercatat.</p>
                    @endforelse
                </div>
                <button type="submit" class="btn-mark btn-sm w-full mt-2 justify-center">Terapkan</button>
            </div>
        </div>

        <select name="user_id" onchange="this.form.submit()" class="form-select py-1.5 text-xs w-44">
            <option value="all">Semua pengguna</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>

        <div class="flex items-center gap-2">
            <input type="date" name="date_from" value="{{ request('date_from') }}" onchange="this.form.submit()" class="form-input py-1.5 text-xs w-36">
            <span class="text-ink-700/40 text-sm">–</span>
            <input type="date" name="date_to" value="{{ request('date_to') }}" onchange="this.form.submit()" class="form-input py-1.5 text-xs w-36">
        </div>

        <div class="ml-auto flex items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari aksi / kode…" class="form-input py-1.5 text-xs w-44">
            @if (request()->hasAny(['actions', 'af', 'user_id', 'date_from', 'date_to', 'q']))
                <a href="{{ route('admin.audit-log.index') }}" class="text-xs text-ink-700/50 hover:text-ink-900 whitespace-nowrap">Reset</a>
            @endif
        </div>
    </form>

    {{-- Timeline, grouped by day --}}
    @php $grouped = $logs->getCollection()->groupBy('date_key'); @endphp

    @forelse ($grouped as $dateKey => $entries)
        @php $first = $entries->first(); @endphp
        <div class="audit-day">
            {{-- Day header --}}
            <div class="flex items-center gap-3 mt-7 mb-3.5 first:mt-0">
                <span class="font-display font-bold text-[13px] text-ink-900 bg-bg border border-rule rounded-full px-3 py-1 whitespace-nowrap">
                    {{ $first['day_label'] }}
                    @if ($first['day_rel'] !== '')
                        <span class="text-ink-700/40 font-medium">· {{ $first['day_rel'] }}</span>
                    @endif
                </span>
                <span class="h-px flex-1 bg-rule"></span>
                <span class="font-mono text-[11px] text-ink-700/40 whitespace-nowrap">{{ $entries->count() }} aktivitas</span>
            </div>

            {{-- Timeline rail --}}
            <div class="relative pl-7">
                <span class="absolute left-[6px] top-1 bottom-1 w-0.5 bg-rule"></span>

                @foreach ($entries as $log)
                    <div class="audit-entry relative flex items-start gap-4 py-3.5 border-b border-rule last:border-b-0">
                        {{-- node dot --}}
                        <span class="absolute -left-[26px] top-4 w-2.5 h-2.5 rounded-full"
                              style="background: {{ $log['color'] }}; box-shadow: 0 0 0 4px #FAFAF7, 0 0 0 5px {{ $log['color'] }}40;"></span>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <span class="font-mono text-[11px] font-medium px-2 py-0.5 rounded"
                                      style="color: {{ $log['color'] }}; background-color: {{ $log['color'] }}1A;">{{ $log['action'] }}</span>
                                <span class="font-display font-semibold text-[15px] text-ink-900">{{ $log['desc'] }}</span>
                            </div>

                            <div class="mt-1.5 flex items-center gap-2 flex-wrap text-[13px] text-ink-700/50">
                                <span>oleh <b class="font-semibold text-ink-700/80">{{ $log['user'] }}</b></span>
                                @if ($log['target'] !== '—')
                                    <span class="w-[3px] h-[3px] rounded-full bg-rule-strong"></span>
                                    <span class="font-mono text-xs text-ink-700/70 bg-ink-50 border border-rule rounded px-1.5 py-0.5">{{ $log['target'] }}</span>
                                @endif
                                @if ($log['inline_diff'])
                                    <span class="w-[3px] h-[3px] rounded-full bg-rule-strong"></span>
                                    <span class="font-mono text-xs inline-flex items-center gap-1.5">
                                        <span class="text-ink-700/40 line-through">{{ $log['inline_diff']['before'] }}</span>
                                        <span class="text-ink-700/30">→</span>
                                        <span class="text-status-approved font-medium">{{ $log['inline_diff']['after'] }}</span>
                                    </span>
                                @endif
                            </div>

                            {{-- Expandable before/after diff (logbook edits) --}}
                            @if (! empty($log['changes']))
                                <div x-data="{ open: false }">
                                    <button type="button" @click="open = !open" :aria-expanded="open"
                                            class="print:hidden mt-2 inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-mark-600 hover:text-mark-700">
                                        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                                        <span x-text="open ? 'Sembunyikan perubahan' : 'Lihat perubahan'"></span>
                                    </button>
                                    <div x-show="open" style="display:none"
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         class="audit-diff mt-2.5 border border-rule rounded-lg overflow-hidden font-mono text-xs">
                                        @foreach ($log['changes'] as $ch)
                                            <div class="flex border-t border-rule first:border-t-0">
                                                <div class="w-32 shrink-0 px-3 py-1.5 bg-ink-50 text-ink-700/70 border-r border-rule">{{ $ch['label'] }}</div>
                                                <div class="px-3 py-1.5 flex items-start gap-2 flex-wrap min-w-0">
                                                    <span class="text-status-rejected line-through opacity-70 break-words">{{ $ch['before'] }}</span>
                                                    <span class="text-ink-700/40">→</span>
                                                    <span class="text-status-approved font-medium break-words">{{ $ch['after'] }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="shrink-0 font-mono text-xs text-ink-700/50 pt-0.5 whitespace-nowrap">{{ $log['time'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-white border border-rule rounded-xl shadow-card px-6 py-16 text-center">
            <p class="text-sm text-ink-700/40">Tidak ada entri yang cocok dengan filter.</p>
        </div>
    @endforelse

    {{-- Pagination --}}
    <div class="flex items-center justify-between mt-8 text-xs text-ink-700/50 print:hidden">
        <span>Menampilkan {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} entri</span>
        <div class="flex gap-1">
            @if ($logs->onFirstPage())
                <span class="px-3 py-1.5 rounded border border-rule bg-white text-ink-700/30 cursor-not-allowed">← Sebelumnya</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="px-3 py-1.5 rounded border border-rule bg-white hover:bg-ink-50 text-ink-700/70 transition-colors">← Sebelumnya</a>
            @endif
            @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="px-3 py-1.5 rounded border border-rule bg-white hover:bg-ink-50 text-ink-700/70 transition-colors">Berikutnya →</a>
            @else
                <span class="px-3 py-1.5 rounded border border-rule bg-white text-ink-700/30 cursor-not-allowed">Berikutnya →</span>
            @endif
        </div>
    </div>

</x-app-layout>
