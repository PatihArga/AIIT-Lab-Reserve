<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Operasional"
            title="Manajemen Komputer"
            meta="9 unit · Lab 401">
        </x-page-header>
    </x-slot:header>

    @php
        $computers = collect([
            ['id'=>1,'label'=>'PC-01','status'=>'online','specs'=>'Core i7-12700 · 16GB RAM · 512GB SSD'],
            ['id'=>2,'label'=>'PC-02','status'=>'online','specs'=>'Core i7-12700 · 16GB RAM · 512GB SSD'],
            ['id'=>3,'label'=>'PC-03','status'=>'online','specs'=>'Core i7-12700 · 16GB RAM · 512GB SSD'],
            ['id'=>4,'label'=>'PC-04','status'=>'online','specs'=>'Core i7-12700 · 16GB RAM · 512GB SSD'],
            ['id'=>5,'label'=>'PC-05','status'=>'online','specs'=>'Core i7-12700 · 16GB RAM · 512GB SSD'],
            ['id'=>6,'label'=>'PC-06','status'=>'online','specs'=>'Core i7-12700 · 16GB RAM · 512GB SSD'],
            ['id'=>7,'label'=>'PC-07','status'=>'maintenance','specs'=>'Core i7-12700 · 16GB RAM · 512GB SSD'],
            ['id'=>8,'label'=>'PC-08','status'=>'online','specs'=>'Core i7-12700 · 16GB RAM · 512GB SSD'],
            ['id'=>9,'label'=>'PC-09','status'=>'offline','specs'=>'Core i5-11400 · 8GB RAM · 256GB SSD'],
        ]);
    @endphp

    {{-- Summary chips --}}
    <div class="flex gap-3 mb-8">
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-rule shadow-card text-sm">
            <span class="w-2 h-2 rounded-full bg-status-approved"></span>
            <span class="text-ink-900 font-medium">7 Online</span>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-rule shadow-card text-sm">
            <span class="w-2 h-2 rounded-full bg-mark-500"></span>
            <span class="text-ink-900 font-medium">1 Pemeliharaan</span>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-rule shadow-card text-sm">
            <span class="w-2 h-2 rounded-full bg-status-cancelled"></span>
            <span class="text-ink-900 font-medium">1 Nonaktif</span>
        </div>
    </div>

    {{-- Computer grid (management view) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($computers as $pc)
            @php
                $statusColor = match($pc['status']) {
                    'online'      => 'bg-status-approved',
                    'maintenance' => 'bg-mark-500',
                    'offline'     => 'bg-status-cancelled',
                    default       => 'bg-ink-700/30',
                };
                $statusLabel = match($pc['status']) {
                    'online'      => 'Online',
                    'maintenance' => 'Pemeliharaan',
                    'offline'     => 'Nonaktif',
                    default       => $pc['status'],
                };
                $cardBorder = $pc['status'] === 'maintenance' ? 'border-mark-300/60' : ($pc['status'] === 'offline' ? 'border-rule' : 'border-rule');
                $cardBg = $pc['status'] === 'maintenance' ? 'bg-mark-50/40' : ($pc['status'] === 'offline' ? 'bg-ink-50/40' : 'bg-white');
            @endphp
            <div class="rounded-xl border {{ $cardBorder }} {{ $cardBg }} shadow-card p-5" x-data="{ editNote: false }">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full {{ $statusColor }} shrink-0"></span>
                        <span class="font-mono text-lg font-bold text-ink-900">{{ $pc['label'] }}</span>
                    </div>
                    <span class="text-[10px] uppercase tracking-label font-semibold
                                 {{ $pc['status'] === 'online' ? 'text-status-approved' : ($pc['status'] === 'maintenance' ? 'text-mark-600' : 'text-ink-700/40') }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <p class="text-xs text-ink-700/50 mb-4">{{ $pc['specs'] }}</p>

                @if ($pc['status'] === 'maintenance')
                    <div class="mb-3 text-xs text-mark-600 bg-mark-100/60 border border-mark-300/40 rounded p-2">
                        Penggantian thermal paste & pembersihan kipas · Sejak 07 Mei 2026
                    </div>
                @endif

                {{-- Toggle status --}}
                <div class="flex gap-2">
                    @if ($pc['status'] === 'online')
                        <button class="btn-secondary btn-sm flex-1 justify-center">
                            Tandai Pemeliharaan
                        </button>
                    @elseif ($pc['status'] === 'maintenance')
                        <button class="btn-mark btn-sm flex-1 justify-center">
                            Selesai, Online
                        </button>
                        <button class="btn-ghost btn-sm">
                            Edit Catatan
                        </button>
                    @else
                        <button class="btn-secondary btn-sm flex-1 justify-center">
                            Aktifkan
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

</x-app-layout>
