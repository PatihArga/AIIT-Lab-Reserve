<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Sistem"
            title="Audit Log"
            meta="Riwayat semua aktivitas sistem">
        </x-page-header>
    </x-slot:header>

    {{-- Filter bar (GET form; selects/dates auto-submit) --}}
    <form method="GET" action="{{ route('admin.audit-log.index') }}" class="flex items-center gap-3 mb-6 flex-wrap">
        <select name="action" onchange="this.form.submit()" class="form-select py-1.5 text-xs w-44">
            <option value="all">Semua aksi</option>
            @foreach ($actions as $a)
                <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $a }}</option>
            @endforeach
        </select>

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
            @if (request()->hasAny(['action', 'user_id', 'date_from', 'date_to', 'q']))
                <a href="{{ route('admin.audit-log.index') }}" class="text-xs text-ink-700/50 hover:text-ink-900 whitespace-nowrap">Reset</a>
            @endif
        </div>
    </form>

    {{-- Log list --}}
    <div class="bg-white border border-rule rounded-xl shadow-card divide-y divide-rule overflow-hidden">
        @forelse ($logs as $log)
            <div class="px-6 py-4 flex items-start gap-4 hover:bg-ink-50/30 transition-colors">
                <div class="flex flex-col items-center mt-1 shrink-0">
                    <span class="w-2 h-2 rounded-full {{ $log['color'] }}"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="font-mono text-[11px] font-semibold text-ink-700/60 bg-ink-50 border border-rule-strong rounded px-1.5 py-0.5">
                                {{ $log['action'] }}
                            </span>
                            <span class="text-sm text-ink-900 ml-2">{{ $log['desc'] }}</span>
                        </div>
                        <span class="mono-code text-ink-700/40 shrink-0">{{ $log['time'] }}</span>
                    </div>
                    <div class="mt-1.5 flex items-center gap-3 text-xs text-ink-700/50">
                        <span>oleh <span class="font-medium text-ink-700/70">{{ $log['user'] }}</span></span>
                        @if ($log['target'] !== '—')
                            <span>·</span>
                            <span class="mono-data text-ink-700/60">{{ $log['target'] }}</span>
                        @endif
                    </div>

                    {{-- Before/after diff — collapsed by default --}}
                    @if (! empty($log['changes']))
                        <div x-data="{ open: false }" class="mt-2">
                            <button type="button" @click="open = !open"
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-mark-600 hover:text-mark-700">
                                <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                                <span x-text="open ? 'Sembunyikan perubahan' : 'Lihat perubahan'"></span>
                            </button>
                            <div x-show="open" style="display:none"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="mt-2 space-y-3">
                                @foreach ($log['changes'] as $ch)
                                    <div>
                                        <div class="text-[10px] font-bold uppercase tracking-label text-ink-700/40 mb-1">{{ $ch['label'] }}</div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                            <div class="rounded-md border border-rule bg-ink-50/50 px-2.5 py-1.5">
                                                <div class="text-[10px] uppercase tracking-label text-ink-700/40 mb-0.5">Sebelum</div>
                                                <div class="text-ink-700/80 whitespace-pre-wrap break-words">{{ $ch['before'] }}</div>
                                            </div>
                                            <div class="rounded-md border border-status-approved/30 bg-status-approved/5 px-2.5 py-1.5">
                                                <div class="text-[10px] uppercase tracking-label text-ink-700/40 mb-0.5">Sesudah</div>
                                                <div class="text-ink-900 whitespace-pre-wrap break-words">{{ $ch['after'] }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center text-sm text-ink-700/40">Tidak ada entri yang cocok dengan filter.</div>
        @endforelse
    </div>

    <div class="flex items-center justify-between mt-4 text-xs text-ink-700/50">
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
