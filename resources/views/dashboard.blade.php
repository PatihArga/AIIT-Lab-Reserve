<x-app-layout>

@push('styles')
<style>
/* ── CALENDAR ─────────────────────────────────────────────── */
.cal-body { display: grid; grid-template-columns: 1fr; align-items: start; }
@media (min-width: 768px) { .cal-body { grid-template-columns: 1fr 1px 1fr; } }
.cal-grid-panel { padding: 12px 16px 16px; }
.cal-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); margin-bottom: 4px; }
.cal-wd { text-align: center; font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: rgba(10,26,71,.38); padding: 4px 0; }
.cal-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; min-height: 220px; align-items: start; }
.cal-day { width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; font-size: 12.5px; font-weight: 500; border-radius: 50%; cursor: pointer; color: #0A1A47; transition: background .15s, color .15s; position: relative; margin: 0 auto; }
.cal-day:hover:not(.day-empty):not(.day-other) { background: #EEF2FF; color: #1A3C8F; }
.cal-day.day-other { color: rgba(10,26,71,.32); cursor: default; }
.cal-day.day-empty { cursor: default; }
.cal-day.day-today { background: #0A1A47; color: #fff; font-weight: 700; }
.cal-day.day-selected { background: #F5B800; color: #0A1A47; font-weight: 700; }
.cal-day.day-has-event::after { content: ''; position: absolute; bottom: 3px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; border-radius: 50%; background: #2eb8a0; }
.cal-day.day-today.day-has-event::after { background: #F5B800; }
.cal-day.day-selected.day-has-event::after { background: #0A1A47; }
.cal-nav-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid rgba(15,36,96,.08); background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; color: rgba(10,26,71,.38); transition: background .15s, color .15s; }
.cal-nav-btn:hover { background: #FAFAF7; color: #0A1A47; }
.cal-nav-btn svg { stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.cal-slots-panel { padding: 12px 16px 16px; display: flex; flex-direction: column; gap: 8px; overflow-y: auto; max-height: 300px; border-top: 1px solid rgba(15,36,96,.08); }
@media (min-width: 768px) { .cal-slots-panel { border-top: none; max-height: 340px; } }
.cal-divider { display: none; }
@media (min-width: 768px) { .cal-divider { display: block; background: rgba(15,36,96,.08); } }
.cal-slots-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px; }
.cal-slot { padding: 7px 8px; border-radius: 6px; border: 1px solid rgba(15,36,96,.08); background: #FAFAF7; cursor: pointer; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 2px; transition: background .15s, border-color .15s, color .15s, transform .1s; overflow: hidden; }
.cal-slot:hover { border-color: #DBEAFE; background: #fff; }
.cal-slot:active { transform: scale(0.97); }
.cal-slot.slot-booked { background: #FFFBEB; border-color: #FDE68A; color: #D9A300; cursor: not-allowed; }
.interval-btn { padding: 4px 10px; font-size: 10.5px; font-weight: 600; font-family: 'Sora', sans-serif; border: none; background: transparent; color: rgba(10,26,71,.4); cursor: pointer; transition: background .15s, color .15s; }
.interval-btn.active { background: #0A1A47; color: #fff; }
.interval-btn:hover:not(.active) { background: #FAFAF7; color: #0A1A47; }

/* ── SLOT MODAL ─────────────────────────────────────────────── */
.slot-modal-overlay { position: fixed; inset: 0; background: rgba(10,26,71,.45); backdrop-filter: blur(4px); z-index: 500; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity .25s; }
.slot-modal-overlay.open { opacity: 1; pointer-events: all; }
.slot-modal { background: #fff; border: 1px solid rgba(15,36,96,.08); border-radius: 14px; box-shadow: 0 24px 64px rgba(10,26,71,.22); width: 480px; max-width: calc(100vw - 40px); max-height: calc(100vh - 60px); overflow-y: auto; padding: 28px; transform: translateY(16px) scale(0.97); opacity: 0; transition: transform .28s cubic-bezier(.34,1.56,.64,1), opacity .25s; }
.slot-modal-overlay.open .slot-modal { transform: translateY(0) scale(1); opacity: 1; }
.modal-close { width: 28px; height: 28px; border-radius: 6px; border: 1px solid rgba(15,36,96,.08); background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; color: rgba(10,26,71,.38); transition: background .15s, color .15s; flex-shrink: 0; }
.modal-close:hover { background: #FAFAF7; color: #0A1A47; }
.modal-close svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.res-type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px; }
.type-card { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 8px; border-radius: 10px; border: 1.5px solid rgba(15,36,96,.08); background: #FAFAF7; cursor: pointer; text-align: center; font-family: 'Sora', sans-serif; transition: border-color .15s, background .15s, box-shadow .15s; }
.type-card:hover { border-color: #DBEAFE; background: #fff; }
.type-card.active { border-color: #F5B800; background: #FFFBEB; box-shadow: 0 0 0 3px rgba(245,184,0,.12); }
.type-card-icon { width: 32px; height: 32px; border-radius: 6px; background: #fff; border: 1px solid rgba(15,36,96,.08); display: flex; align-items: center; justify-content: center; }
.type-card.active .type-card-icon { background: #F5B800; border-color: #D9A300; }
.type-card-icon svg { width: 16px; height: 16px; stroke: rgba(10,26,71,.5); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.type-card.active .type-card-icon svg { stroke: #0A1A47; }
.type-card-name { font-size: 11.5px; font-weight: 700; color: #0A1A47; }
.type-card-desc { font-size: 9.5px; color: rgba(10,26,71,.4); line-height: 1.3; }
.sharing-btn { flex: 1; display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 6px; border: 1.5px solid rgba(15,36,96,.08); background: #FAFAF7; cursor: pointer; font-family: 'Sora', sans-serif; text-align: left; transition: border-color .15s, background .15s; }
.sharing-btn:hover { border-color: #DBEAFE; background: #fff; }
.sharing-btn.active { border-color: #F5B800; background: #FFFBEB; }
.sharing-radio { width: 14px; height: 14px; border-radius: 50%; border: 2px solid rgba(15,36,96,.16); flex-shrink: 0; transition: border-color .15s, background .15s; }
.sharing-btn.active .sharing-radio { border-color: #D9A300; background: #F5B800; }
.modal-computers-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
.computer-slot { border-radius: 10px; border: 1.5px solid rgba(15,36,96,.08); padding: 14px 10px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px; cursor: pointer; opacity: 0; transform: translateY(12px); }
@keyframes slotReveal { to { opacity: 1; transform: translateY(0); } }
@keyframes pulseGlow { 0%, 100% { box-shadow: 0 0 0 0 rgba(46,184,160,0); } 50% { box-shadow: 0 0 0 4px rgba(46,184,160,.2); } }
.slot-available { border-color: rgba(15,36,96,.08); background: #FAFAF7; animation: slotReveal .4s forwards, pulseGlow 2s ease-in-out .6s 3; }
.slot-available:hover { border-color: #2eb8a0; box-shadow: 0 0 0 3px #d4f5ef; background: #fff; }
.slot-booked-pc { border-color: #FDE68A; background: #FFFBEB; cursor: not-allowed; animation: slotReveal .4s forwards; }
.slot-maintenance-pc { border-color: rgba(15,36,96,.08); background: #FAFAF7; opacity: .5 !important; cursor: not-allowed; animation: slotReveal .4s forwards; }
.slot-monitor { width: 36px; height: 26px; border-radius: 3px; border: 2px solid; position: relative; }
.slot-monitor::after { content: ''; position: absolute; bottom: -7px; left: 50%; transform: translateX(-50%); width: 12px; height: 5px; border-radius: 0 0 2px 2px; border: 2px solid; border-top: none; }
.slot-available .slot-monitor { border-color: #2eb8a0; background: #d4f5ef; }
.slot-available .slot-monitor::after { border-color: #2eb8a0; }
.slot-booked-pc .slot-monitor { border-color: #D9A300; background: #FFFBEB; }
.slot-booked-pc .slot-monitor::after { border-color: #D9A300; }
.slot-maintenance-pc .slot-monitor { border-color: rgba(10,26,71,.28); background: rgba(15,36,96,.06); }
.slot-maintenance-pc .slot-monitor::after { border-color: rgba(10,26,71,.28); }
.slot-num { font-size: 11px; font-weight: 700; font-family: 'JetBrains Mono', monospace; }
.slot-available .slot-num { color: #2eb8a0; }
.slot-booked-pc .slot-num { color: #D9A300; }
.slot-maintenance-pc .slot-num { color: rgba(10,26,71,.38); }
.slot-status-text { font-size: 9.5px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
.slot-available .slot-status-text { color: #2eb8a0; }
.slot-booked-pc .slot-status-text { color: #D9A300; }
.slot-maintenance-pc .slot-status-text { color: rgba(10,26,71,.38); }

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
.tbl-action-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid rgba(15,36,96,.08); background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; color: rgba(10,26,71,.35); transition: background .15s, color .15s; }
.tbl-action-btn:hover { background: #FAFAF7; color: #0A1A47; }
.tbl-action-btn svg { width: 14px; height: 14px; fill: currentColor; }
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

    <x-slot:header>
        <div class="pb-5 mb-0 border-b border-rule">
            <div class="text-[0.7rem] font-semibold uppercase tracking-label text-mark-600 mb-1">Beranda</div>
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div>
                    <h2 class="font-display text-2xl sm:text-3xl font-bold text-ink-900 tracking-tight">Halo, {{ explode(' ', Auth::user()->name)[0] }}.</h2>
                    <p class="mt-1 text-xs sm:text-sm text-ink-700/60">Sesi berikutnya: Selasa, 12 Mei · 09:00 — 12:00 (Tim Alpha)</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="#" class="btn-ghost btn-sm">Lihat Riwayat</a>
                    <a href="#" class="btn-mark btn-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Buat Reservasi
                    </a>
                </div>
            </div>
        </div>
    </x-slot:header>

    {{-- ── STAT CARDS ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">

        <div class="bg-white border border-rule rounded-xl p-5 shadow-card flex flex-col gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-label text-ink-700/40">Sesi Mendatang</span>
                <span class="w-7 h-7 rounded-md bg-ink-50 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5 stroke-ink-700" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
            </div>
            <div>
                <div class="font-mono text-[2rem] font-bold text-ink-900 tracking-tight leading-none">2</div>
                <div class="text-[11.5px] text-ink-700/40 mt-1">Mei 12 – Mei 18</div>
            </div>
        </div>

        <div class="bg-white border border-rule rounded-xl p-5 shadow-card flex flex-col gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-label text-ink-700/40">Total Bulan Ini</span>
                <span class="w-7 h-7 rounded-md flex items-center justify-center shrink-0" style="background:#d4f5ef">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="#2eb8a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </span>
            </div>
            <div>
                <div class="font-mono text-[2rem] font-bold text-ink-900 tracking-tight leading-none">14</div>
                <div class="text-[11.5px] text-ink-700/40 mt-1"><span style="color:#2eb8a0" class="font-semibold">&#8593; 3</span> vs bulan lalu</div>
            </div>
        </div>

        <div class="bg-white border border-rule rounded-xl p-5 shadow-card flex flex-col gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-label text-ink-700/40">Menunggu Persetujuan</span>
                <span class="w-7 h-7 rounded-md bg-mark-50 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5 stroke-mark-600" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
            </div>
            <div>
                <div class="font-mono text-[2rem] font-bold text-mark-600 tracking-tight leading-none">1</div>
                <div class="text-[11.5px] text-ink-700/40 mt-1">LAB-0044</div>
            </div>
        </div>

        <div class="bg-white border border-rule rounded-xl p-5 shadow-card flex flex-col gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-label text-ink-700/40">Total Pemakaian</span>
                <span class="w-7 h-7 rounded-md bg-ink-50 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5 stroke-ink-700" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
            </div>
            <div>
                <div class="font-mono text-[2rem] font-bold text-ink-900 tracking-tight leading-none">38h</div>
                <div class="text-[11.5px] text-ink-700/40 mt-1">Tahun akademik ini</div>
            </div>
        </div>

    </div>

    {{-- ── INTERACTIVE CALENDAR ── --}}
    <div class="bg-white border border-rule rounded-xl shadow-card mb-6">

        <div class="flex items-center justify-between px-5 py-4 border-b border-rule">
            <div class="flex items-center gap-2.5">
                <span class="text-sm font-bold text-ink-900 tracking-tight" id="cal-month-label">Mei 2026</span>
                <span class="bg-ink-50 text-ink-700 text-[11px] font-bold px-2 py-0.5 rounded-full" id="cal-event-count">3 reservasi</span>
            </div>
            <div class="flex items-center gap-1">
                <button onclick="calNav(-1)" class="cal-nav-btn" title="Bulan sebelumnya">
                    <svg width="14" height="14" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button onclick="calNav(1)" class="cal-nav-btn" title="Bulan berikutnya">
                    <svg width="14" height="14" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </div>

        <div class="cal-body">
            <div class="cal-grid-panel">
                <div class="cal-weekdays">
                    <div class="cal-wd">Min</div><div class="cal-wd">Sen</div><div class="cal-wd">Sel</div>
                    <div class="cal-wd">Rab</div><div class="cal-wd">Kam</div><div class="cal-wd">Jum</div>
                    <div class="cal-wd">Sab</div>
                </div>
                <div class="cal-days" id="cal-days"></div>
            </div>

            <div class="cal-divider"></div>

            <div class="cal-slots-panel">
                <div class="flex items-center justify-between gap-2 shrink-0">
                    <span class="text-[10px] font-bold uppercase tracking-label text-ink-700/40" id="cal-slots-header">Pilih tanggal</span>
                    <div class="flex border border-rule rounded overflow-hidden">
                        <button class="interval-btn active" data-val="60" onclick="setSlotInterval(60, this)">1 jam</button>
                        <button class="interval-btn" style="border-left:1px solid rgba(15,36,96,.08)" data-val="30" onclick="setSlotInterval(30, this)">30 mnt</button>
                    </div>
                </div>
                <div class="cal-slots-grid" id="cal-slots-grid">
                    <div style="grid-column:1/-1;text-align:center;padding:20px 0;color:rgba(10,26,71,.4);font-size:12px;">&#8595; Pilih tanggal di atas</div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── BOTTOM GRID: table + right panel ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-4">

        {{-- Reservations table card --}}
        <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">
            <div class="px-5 pt-5 pb-0 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-[10px] font-bold uppercase tracking-label text-ink-700/60">Reservasi Mendatang</span>
                    <span class="bg-ink-50 text-ink-700 text-[11px] font-bold px-2 py-0.5 rounded-full">3</span>
                </div>
                <div class="pill-tabs">
                    <div class="pill-tab active" onclick="switchBookingTab(this,'mendatang')">Mendatang</div>
                    <div class="pill-tab" onclick="switchBookingTab(this,'selesai')">Selesai</div>
                </div>
            </div>
            <div class="px-3 sm:px-5 pb-5 pt-3 overflow-x-auto">
                <table class="bookings-tbl">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal &amp; Waktu</th>
                            <th>Jenis</th>
                            <th style="text-align:right">Status</th>
                        </tr>
                    </thead>
                    <tbody id="bookings-tbody">
                        <tr>
                            <td class="font-mono text-[12.5px] font-medium text-ink-700">LAB-0042</td>
                            <td>
                                <div class="text-sm font-semibold text-ink-900">12 Mei 2026</div>
                                <div class="font-mono text-[11.5px] text-ink-700/40 mt-0.5">09:00 &#8212; 12:00</div>
                            </td>
                            <td>
                                <div class="text-sm text-ink-700/70">Komputer + Ruang</div>
                                <div class="text-[11.5px] text-ink-700/40">5 unit</div>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2.5">
                                    <x-badge status="approved" />
                                    <button class="tbl-action-btn"><svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="12" cy="19" r="1.2"/></svg></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-mono text-[12.5px] font-medium text-ink-700">LAB-0044</td>
                            <td>
                                <div class="text-sm font-semibold text-ink-900">18 Mei 2026</div>
                                <div class="font-mono text-[11.5px] text-ink-700/40 mt-0.5">14:00 &#8212; 17:00</div>
                            </td>
                            <td>
                                <div class="text-sm text-ink-700/70">Ruang Saja</div>
                                <div class="text-[11.5px] text-ink-700/40">&#8212;</div>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2.5">
                                    <x-badge status="pending" />
                                    <button class="tbl-action-btn"><svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="12" cy="19" r="1.2"/></svg></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-mono text-[12.5px] font-medium text-ink-700">LAB-0036</td>
                            <td>
                                <div class="text-sm font-semibold text-ink-900">25 Mei 2026</div>
                                <div class="font-mono text-[11.5px] text-ink-700/40 mt-0.5">10:00 &#8212; 12:00</div>
                            </td>
                            <td>
                                <div class="text-sm text-ink-700/70">Komputer Saja</div>
                                <div class="text-[11.5px] text-ink-700/40">2 unit</div>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2.5">
                                    <x-badge status="approved" />
                                    <button class="tbl-action-btn"><svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="12" cy="19" r="1.2"/></svg></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Right panel: lab hours + status --}}
        <div class="bg-white border border-rule rounded-xl shadow-card">
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

    {{-- ── SLOT AVAILABILITY MODAL ── --}}
    <div class="slot-modal-overlay" id="slot-modal-overlay" onclick="closeSlotModal(event)">
        <div class="slot-modal" id="slot-modal">

            <div class="flex items-start justify-between mb-5">
                <div>
                    <div class="inline-flex items-center gap-1.5 bg-mark-50 border border-mark-100 rounded-full px-3 py-1 text-xs font-bold text-mark-600 mb-2">
                        <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span id="modal-date-text">&#8212;</span>
                    </div>
                    <div class="text-[18px] font-bold text-ink-900 leading-tight tracking-tight" id="modal-time-title">&#8212;</div>
                    <div class="text-sm text-ink-700/55 mt-0.5">Pilih tipe reservasi untuk sesi ini</div>
                </div>
                <button class="modal-close" onclick="closeSlotModal(null, true)">
                    <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="text-[10px] font-bold uppercase tracking-label text-ink-700/40 mb-2.5">Tipe Reservasi</div>
            <div class="res-type-grid" id="modal-type-grid">
                <button class="type-card active" data-type="computer" onclick="selectResType('computer')">
                    <div class="type-card-icon"><svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
                    <div class="type-card-name">Komputer</div>
                    <div class="type-card-desc">Pilih unit tertentu</div>
                </button>
                <button class="type-card" data-type="both" onclick="selectResType('both')">
                    <div class="type-card-icon"><svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/><rect x="16" y="6" width="4" height="3" rx="0.5" fill="currentColor" stroke="none"/></svg></div>
                    <div class="type-card-name">Ruang + Komputer</div>
                    <div class="type-card-desc">Ruang penuh + semua unit</div>
                </button>
                <button class="type-card" data-type="room" onclick="selectResType('room')">
                    <div class="type-card-icon"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                    <div class="type-card-name">Ruang Saja</div>
                    <div class="type-card-desc">Tanpa komputer</div>
                </button>
            </div>

            <div id="modal-sharing-row" style="display:none;margin-bottom:16px;">
                <div class="text-[10px] font-bold uppercase tracking-label text-ink-700/40 mb-2">Penggunaan Ruang</div>
                <div class="flex gap-2">
                    <button class="sharing-btn active" data-val="exclusive" onclick="selectSharing('exclusive')">
                        <span class="sharing-radio"></span>
                        <div>
                            <div class="text-xs font-bold text-ink-900">Eksklusif</div>
                            <div class="text-[10px] text-ink-700/40">Hanya untuk Anda</div>
                        </div>
                    </button>
                    <button class="sharing-btn" data-val="shared" onclick="selectSharing('shared')">
                        <span class="sharing-radio"></span>
                        <div>
                            <div class="text-xs font-bold text-ink-900">Berbagi</div>
                            <div class="text-[10px] text-ink-700/40">Bisa digunakan bersama</div>
                        </div>
                    </button>
                </div>
            </div>

            <div id="modal-computers-section">
                <div class="text-[10px] font-bold uppercase tracking-label text-ink-700/40 mb-3.5" id="modal-computer-label">Pilih unit komputer</div>
                <div class="modal-computers-grid" id="modal-computers"></div>
                <div class="flex gap-2.5 p-3.5 bg-bg rounded-md border border-rule mb-4 text-[12.5px]" id="modal-summary"></div>
            </div>

            <div class="flex gap-2">
                <button class="btn-ghost flex-1 justify-center" onclick="closeSlotModal(null, true)">Tutup</button>
                <button class="btn-mark flex-[2] justify-center" id="modal-reserve-btn" onclick="closeSlotModal(null, true)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Buat Reservasi Sesi Ini
                </button>
            </div>

        </div>
    </div>

@push('scripts')
<script>
const MONTHS_ID = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const TODAY = new Date(2026, 4, 8);
const RESERVATIONS = { 12: [9,10,11], 18: [14,15,16], 25: [10,11] };

function getComputerStates(day, startHour) {
    const states = [0,0,0,0,0,0,0,0,2];
    if (RESERVATIONS[day] && RESERVATIONS[day].includes(Math.floor(startHour))) {
        const n = day === 12 ? 5 : day === 18 ? 0 : 2;
        for (let i = 0; i < n; i++) states[i] = 1;
    }
    return states;
}

let calYear = 2026, calMonth = 4, selectedDay = null;

function renderCalendar() {
    document.getElementById('cal-month-label').textContent = MONTHS_ID[calMonth] + ' ' + calYear;
    const resvCount = (calYear === 2026 && calMonth === 4) ? 3 : 0;
    document.getElementById('cal-event-count').textContent = resvCount + ' reservasi';
    const firstDay    = new Date(calYear, calMonth, 1).getDay();
    const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
    const daysInPrev  = new Date(calYear, calMonth, 0).getDate();
    const grid = document.getElementById('cal-days');
    grid.innerHTML = '';
    for (let i = firstDay - 1; i >= 0; i--) {
        const el = document.createElement('div');
        el.className = 'cal-day day-other';
        el.textContent = daysInPrev - i;
        grid.appendChild(el);
    }
    for (let d = 1; d <= daysInMonth; d++) {
        const el = document.createElement('div');
        el.className = 'cal-day';
        el.textContent = d;
        const isToday  = calYear === TODAY.getFullYear() && calMonth === TODAY.getMonth() && d === TODAY.getDate();
        const hasResv  = calYear === 2026 && calMonth === 4 && RESERVATIONS[d];
        const isSunday = new Date(calYear, calMonth, d).getDay() === 0;
        if (isToday)  el.classList.add('day-today');
        if (hasResv)  el.classList.add('day-has-event');
        if (d === selectedDay && calYear === 2026 && calMonth === 4) el.classList.add('day-selected');
        if (isSunday) el.classList.add('day-other');
        else el.addEventListener('click', () => selectDay(d));
        grid.appendChild(el);
    }
    const total = firstDay + daysInMonth;
    const remaining = total % 7 === 0 ? 0 : 7 - (total % 7);
    for (let d = 1; d <= remaining; d++) {
        const el = document.createElement('div');
        el.className = 'cal-day day-other';
        el.textContent = d;
        grid.appendChild(el);
    }
}

function calNav(dir) {
    calMonth += dir;
    if (calMonth > 11) { calMonth = 0; calYear++; }
    if (calMonth < 0)  { calMonth = 11; calYear--; }
    selectedDay = null;
    renderCalendar();
    resetSlots();
}

function selectDay(day) { selectedDay = day; renderCalendar(); renderTimeSlots(day); }

let slotIntervalMin = 60, currentDay = null;

function buildTimeSlots(intervalMin) {
    const slots = [], LAB_OPEN = 8 * 60, LAB_CLOSE = 22 * 60;
    for (let t = LAB_OPEN; t < LAB_CLOSE; t += intervalMin) {
        if (t + intervalMin > LAB_CLOSE) break;
        slots.push({ startMin: t, endMin: t + intervalMin, startHour: t / 60 });
    }
    return slots;
}

function setSlotInterval(min, btn) {
    slotIntervalMin = min;
    document.querySelectorAll('.interval-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (currentDay !== null) renderTimeSlots(currentDay);
}

function renderTimeSlots(day) {
    currentDay = day;
    const dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    document.getElementById('cal-slots-header').textContent =
        dayNames[new Date(calYear, calMonth, day).getDay()] + ', ' + day + ' ' + MONTHS_ID[calMonth];
    if (!document.getElementById('slot-fade-kf')) {
        const s = document.createElement('style'); s.id = 'slot-fade-kf';
        s.textContent = '@keyframes slotFadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}';
        document.head.appendChild(s);
    }
    const slots = buildTimeSlots(slotIntervalMin);
    const grid  = document.getElementById('cal-slots-grid');
    grid.innerHTML = '';
    slots.forEach((slot, idx) => {
        const booked = RESERVATIONS[day] && RESERVATIONS[day].includes(Math.floor(slot.startHour));
        const el = document.createElement('div');
        el.className = 'cal-slot' + (booked ? ' slot-booked' : '');
        el.style.opacity = '0';
        el.style.animation = 'slotFadeIn .28s ' + (idx * 30) + 'ms forwards';
        const fmt = m => String(Math.floor(m/60)).padStart(2,'0') + ':' + String(m%60).padStart(2,'0');
        el.innerHTML = '<span style="font-size:12px;font-weight:700;font-family:\'JetBrains Mono\',monospace;color:inherit">' + fmt(slot.startMin) + '</span>'
                     + '<span style="font-size:9.5px;color:rgba(10,26,71,.38);font-family:\'Sora\',sans-serif;font-weight:500">s/d ' + fmt(slot.endMin) + '</span>'
                     + '<span style="font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;opacity:.7;font-family:\'Sora\',sans-serif">' + (booked ? 'Terpesan' : 'Tersedia') + '</span>';
        if (!booked) el.addEventListener('click', () => openSlotModal(day, slot));
        grid.appendChild(el);
    });
}

function resetSlots() {
    currentDay = null;
    document.getElementById('cal-slots-header').textContent = 'Pilih tanggal';
    document.getElementById('cal-slots-grid').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px 0;color:rgba(10,26,71,.4);font-size:12px;">← Pilih tanggal di sebelah kiri</div>';
}

let currentResType = 'computer', currentSharing = 'exclusive';

function selectResType(type) {
    currentResType = type;
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
    document.querySelector('.type-card[data-type="' + type + '"]').classList.add('active');
    const sharingRow = document.getElementById('modal-sharing-row');
    const computersSection = document.getElementById('modal-computers-section');
    if (type === 'room') {
        sharingRow.style.display = 'block'; computersSection.style.display = 'none';
    } else {
        sharingRow.style.display = 'none'; computersSection.style.display = 'block';
        document.getElementById('modal-computer-label').textContent =
            type === 'computer' ? 'Pilih unit komputer' : 'Unit komputer (semua termasuk)';
    }
}

function selectSharing(val) {
    currentSharing = val;
    document.querySelectorAll('.sharing-btn').forEach(o => o.classList.remove('active'));
    document.querySelector('.sharing-btn[data-val="' + val + '"]').classList.add('active');
}

function openSlotModal(day, slot) {
    const dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    document.getElementById('modal-date-text').textContent =
        dayNames[new Date(calYear, calMonth, day).getDay()] + ', ' + day + ' ' + MONTHS_ID[calMonth] + ' ' + calYear;
    const fmt = m => String(Math.floor(m/60)).padStart(2,'0') + ':' + String(m%60).padStart(2,'0');
    document.getElementById('modal-time-title').textContent = fmt(slot.startMin) + ' — ' + fmt(slot.endMin);
    currentResType = 'computer'; currentSharing = 'exclusive';
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
    document.querySelector('.type-card[data-type="computer"]').classList.add('active');
    document.querySelectorAll('.sharing-btn').forEach(o => o.classList.remove('active'));
    document.querySelector('.sharing-btn[data-val="exclusive"]').classList.add('active');
    document.getElementById('modal-sharing-row').style.display = 'none';
    document.getElementById('modal-computers-section').style.display = 'block';
    document.getElementById('modal-computer-label').textContent = 'Pilih unit komputer';
    const computers = document.getElementById('modal-computers');
    computers.innerHTML = '';
    const states = getComputerStates(day, slot.startHour);
    let avail = 0, booked = 0, maint = 0;
    states.forEach((state, i) => {
        const el = document.createElement('div');
        const cls = state === 0 ? 'slot-available' : state === 1 ? 'slot-booked-pc' : 'slot-maintenance-pc';
        el.className = 'computer-slot ' + cls;
        el.style.animationDelay = (i * 55) + 'ms';
        const lbl = state === 0 ? 'Tersedia' : state === 1 ? 'Terpakai' : 'Perawatan';
        el.innerHTML = '<div class="slot-monitor"></div><div class="slot-num">PC-' + String(i+1).padStart(2,'0') + '</div><div class="slot-status-text">' + lbl + '</div>';
        if (state === 0) avail++; else if (state === 1) booked++; else maint++;
        computers.appendChild(el);
    });
    document.getElementById('modal-summary').innerHTML =
        '<div class="flex items-center gap-1.5 flex-1"><div class="w-2 h-2 rounded-full shrink-0" style="background:#2eb8a0"></div><span class="font-bold text-ink-900">' + avail + '</span>&nbsp;<span class="text-ink-700/40">tersedia</span></div>' +
        '<div class="flex items-center gap-1.5 flex-1"><div class="w-2 h-2 rounded-full shrink-0" style="background:#F5B800"></div><span class="font-bold text-ink-900">' + booked + '</span>&nbsp;<span class="text-ink-700/40">terpakai</span></div>' +
        '<div class="flex items-center gap-1.5 flex-1"><div class="w-2 h-2 rounded-full shrink-0" style="background:rgba(15,36,96,.14)"></div><span class="font-bold text-ink-900">' + maint + '</span>&nbsp;<span class="text-ink-700/40">perawatan</span></div>';
    document.getElementById('slot-modal-overlay').classList.add('open');
}

function closeSlotModal(e, force) {
    if (force || (e && e.target === document.getElementById('slot-modal-overlay')))
        document.getElementById('slot-modal-overlay').classList.remove('open');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSlotModal(null, true); });

function switchBookingTab(el, mode) {
    document.querySelectorAll('.pill-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    const tbody = document.getElementById('bookings-tbody');
    if (mode === 'mendatang') {
        tbody.innerHTML = `<tr><td class="font-mono text-[12.5px] font-medium text-ink-700">LAB-0042</td><td><div class="text-sm font-semibold text-ink-900">12 Mei 2026</div><div class="font-mono text-[11.5px] text-ink-700/40 mt-0.5">09:00 — 12:00</div></td><td><div class="text-sm text-ink-700/70">Komputer + Ruang</div><div class="text-[11.5px] text-ink-700/40">5 unit</div></td><td><div class="flex items-center justify-end gap-2.5"><span class="badge-approved">Disetujui</span><button class="tbl-action-btn"><svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="12" cy="19" r="1.2"/></svg></button></div></td></tr>
        <tr><td class="font-mono text-[12.5px] font-medium text-ink-700">LAB-0044</td><td><div class="text-sm font-semibold text-ink-900">18 Mei 2026</div><div class="font-mono text-[11.5px] text-ink-700/40 mt-0.5">14:00 — 17:00</div></td><td><div class="text-sm text-ink-700/70">Ruang Saja</div><div class="text-[11.5px] text-ink-700/40">—</div></td><td><div class="flex items-center justify-end gap-2.5"><span class="badge-pending">Menunggu</span><button class="tbl-action-btn"><svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="12" cy="19" r="1.2"/></svg></button></div></td></tr>
        <tr><td class="font-mono text-[12.5px] font-medium text-ink-700">LAB-0036</td><td><div class="text-sm font-semibold text-ink-900">25 Mei 2026</div><div class="font-mono text-[11.5px] text-ink-700/40 mt-0.5">10:00 — 12:00</div></td><td><div class="text-sm text-ink-700/70">Komputer Saja</div><div class="text-[11.5px] text-ink-700/40">2 unit</div></td><td><div class="flex items-center justify-end gap-2.5"><span class="badge-approved">Disetujui</span><button class="tbl-action-btn"><svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="12" cy="19" r="1.2"/></svg></button></div></td></tr>`;
    } else {
        tbody.innerHTML = `<tr><td class="font-mono text-[12.5px] font-medium text-ink-700">LAB-0031</td><td><div class="text-sm font-semibold text-ink-900">2 Mei 2026</div><div class="font-mono text-[11.5px] text-ink-700/40 mt-0.5">08:00 — 10:00</div></td><td><div class="text-sm text-ink-700/70">Komputer + Ruang</div><div class="text-[11.5px] text-ink-700/40">9 unit</div></td><td><div class="flex items-center justify-end gap-2.5"><span class="badge-completed">Selesai</span><button class="tbl-action-btn"><svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="12" cy="19" r="1.2"/></svg></button></div></td></tr>
        <tr><td class="font-mono text-[12.5px] font-medium text-ink-700">LAB-0028</td><td><div class="text-sm font-semibold text-ink-900">28 Apr 2026</div><div class="font-mono text-[11.5px] text-ink-700/40 mt-0.5">13:00 — 16:00</div></td><td><div class="text-sm text-ink-700/70">Ruang Saja</div><div class="text-[11.5px] text-ink-700/40">—</div></td><td><div class="flex items-center justify-end gap-2.5"><span class="badge-completed">Selesai</span><button class="tbl-action-btn"><svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="12" cy="19" r="1.2"/></svg></button></div></td></tr>`;
    }
}

renderCalendar();
</script>
@endpush

</x-app-layout>
