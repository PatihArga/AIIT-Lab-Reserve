<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Operasional"
            title="Manajemen Komputer"
            meta="{{ $computers->count() }} unit · Lab 401">
        </x-page-header>
    </x-slot:header>

    {{-- Summary chips --}}
    <div class="flex gap-3 mb-8 flex-wrap">
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-rule shadow-card text-sm">
            <span class="w-2 h-2 rounded-full bg-status-approved"></span>
            <span class="text-ink-900 font-medium">{{ $counts['online'] }} Online</span>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-rule shadow-card text-sm">
            <span class="w-2 h-2 rounded-full bg-mark-500"></span>
            <span class="text-ink-900 font-medium">{{ $counts['maintenance'] }} Pemeliharaan</span>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-rule shadow-card text-sm">
            <span class="w-2 h-2 rounded-full bg-status-cancelled"></span>
            <span class="text-ink-900 font-medium">{{ $counts['offline'] }} Nonaktif</span>
        </div>
    </div>

    {{-- Computer grid (management view) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($computers as $pc)
            @php
                $statusColor = match($pc->status) {
                    'online'      => 'bg-status-approved',
                    'maintenance' => 'bg-mark-500',
                    'offline'     => 'bg-status-cancelled',
                    default       => 'bg-ink-700/30',
                };
                $statusLabel = match($pc->status) {
                    'online'      => 'Online',
                    'maintenance' => 'Pemeliharaan',
                    'offline'     => 'Nonaktif',
                    default       => $pc->status,
                };
                $cardBorder = $pc->status === 'maintenance' ? 'border-mark-300/60' : 'border-rule';
                $cardBg     = $pc->status === 'maintenance' ? 'bg-mark-50/40' : ($pc->status === 'offline' ? 'bg-ink-50/40' : 'bg-white');
            @endphp
            <div class="rounded-xl border {{ $cardBorder }} {{ $cardBg }} shadow-card p-5"
                 x-data="{ editNote: false }">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full {{ $statusColor }} shrink-0"></span>
                        <span class="font-mono text-lg font-bold text-ink-900">{{ $pc->label }}</span>
                    </div>
                    <span class="text-[10px] uppercase tracking-label font-semibold
                                 {{ $pc->status === 'online' ? 'text-status-approved' : ($pc->status === 'maintenance' ? 'text-mark-600' : 'text-ink-700/40') }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <p class="text-xs text-ink-700/50 mb-4 min-h-[2.5rem]">
                    {{ $pc->specs_note ?: 'Belum ada catatan spesifikasi.' }}
                </p>

                {{-- Edit Catatan form (Alpine-toggleable) --}}
                <div x-show="editNote" x-transition class="mb-3">
                    <form method="POST" action="{{ route('admin.computers.status', $pc) }}" class="space-y-2">
                        @csrf
                        <input type="hidden" name="status" value="{{ $pc->status }}">
                        <textarea name="specs_note" rows="2" maxlength="500"
                                  class="form-textarea text-xs w-full"
                                  placeholder="Catatan spesifikasi atau status…">{{ $pc->specs_note }}</textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-mark btn-sm flex-1 justify-center">Simpan</button>
                            <button type="button" @click="editNote = false" class="btn-ghost btn-sm">Batal</button>
                        </div>
                    </form>
                </div>

                {{-- Toggle status forms --}}
                <div x-show="!editNote" class="flex gap-2 flex-wrap">
                    @if ($pc->status === 'online')
                        <form method="POST" action="{{ route('admin.computers.status', $pc) }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="status" value="maintenance">
                            <button type="submit" class="btn-secondary btn-sm w-full justify-center">Tandai Pemeliharaan</button>
                        </form>
                        <button type="button" @click="editNote = true" class="btn-ghost btn-sm">Catatan</button>
                    @elseif ($pc->status === 'maintenance')
                        <form method="POST" action="{{ route('admin.computers.status', $pc) }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="status" value="online">
                            <button type="submit" class="btn-mark btn-sm w-full justify-center">Selesai, Online</button>
                        </form>
                        <button type="button" @click="editNote = true" class="btn-ghost btn-sm">Catatan</button>
                    @else
                        <form method="POST" action="{{ route('admin.computers.status', $pc) }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="status" value="online">
                            <button type="submit" class="btn-secondary btn-sm w-full justify-center">Aktifkan</button>
                        </form>
                        <button type="button" @click="editNote = true" class="btn-ghost btn-sm">Catatan</button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

</x-app-layout>
