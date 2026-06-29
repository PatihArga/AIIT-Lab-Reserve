# PDF Export — Reports & Audit Log

**Project:** UKRIDA / AIIT Lab Reserve System
**Scope:** Admin → Reports (`/admin/reports`) and Admin → Audit Log (`/admin/audit-log`)
**Date:** 2026-06-23

---

## 1. Overview

Both the **Reports** and **Audit Log** admin pages provide a "**Cetak PDF**" (Print PDF) button that lets the administrator save the current view as a PDF document.

The feature is implemented using the **browser's native print engine** (`window.print()`) combined with print-specific CSS (`@media print`), rather than a server-side PDF library such as dompdf, Snappy, or mPDF. This is a deliberate, dependency-free approach:

- **No extra PHP packages** are required (no `composer require`).
- **What you see is what you print** — the PDF reflects exactly the filtered/period-scoped data currently on screen.
- The user gets the browser's standard print dialog, where they can either send to a physical printer or choose **"Save as PDF"** as the destination.

> **Terminology note:** This is technically a *print-to-PDF* feature. The application does not generate a `.pdf` file on the server; the conversion to PDF happens in the browser's print dialog. The button is labelled "Cetak PDF" because saving as PDF is the expected use.

---

## 2. Usage Guide

### 2.1 Reports page

1. Log in as **admin** and open **Laporan & Analitik** (`/admin/reports`).
2. Choose the reporting period — either a preset (**Minggu Ini / Bulan Ini / 3 Bulan / Tahun Ini**) or a custom date range via the **from**/**to** date pickers, then click **Terapkan**.
3. Wait for the page to finish rendering all charts (KPI band, weekly usage, category donut, top users, per-computer usage).
4. Click the **Cetak PDF** button (top-right of the controls bar).
5. In the browser print dialog, set **Destination** to **"Save as PDF"** (Chrome/Edge) or **"Microsoft Print to PDF"**, then **Save**.

### 2.2 Audit Log page

1. Open **Audit Log** (`/admin/audit-log`).
2. Apply any filters you want reflected in the export — action type, user, date range, or search term. The printed document captures **only the rows currently displayed** (the active filter + current pagination page).
3. Click the **Cetak PDF** button (in the page header).
4. Choose **"Save as PDF"** in the print dialog and save.

### 2.3 What gets hidden / revealed when printing

To produce a clean document, the print stylesheet automatically adjusts the layout:

| Element | Behaviour on print |
|---|---|
| Filter forms, preset buttons, date pickers, the "Cetak PDF" button itself | **Hidden** (`print:hidden`) |
| Pagination controls and page footer | **Hidden** (`print:hidden`) |
| "Lihat perubahan" (view-changes) toggle buttons (Audit Log) | **Hidden** (`print:hidden`) |
| Collapsed before/after diff panels (Audit Log) | **Force-shown** so every change is visible on paper, regardless of toggle state |
| Brand colors, donut strokes, accent dots, hairline dividers | **Forced to print** via `print-color-adjust: exact` |
| Card shadows | **Flattened** for clean paper output |
| KPI bands, sections, table rows, audit entries | **Kept together** (`break-inside: avoid`) so they don't split across pages |
| Page margins | Set to **14 mm** via `@page` |

### 2.4 Tips & limitations

- **Render charts first.** The donut and bar charts are inline SVG; make sure the page has fully loaded before printing.
- **Enable background graphics.** If colors look missing in the preview, ensure the print dialog's *"Background graphics"* option is enabled (the CSS requests it, but some browsers expose a manual toggle).
- **One filter/period per export.** To export a different period or filter set, change the on-screen selection and print again.
- **Pagination.** The Audit Log prints the current page of results only. To include more entries in a single PDF, widen the date range/filter so fewer pages are needed, or print each page.
- **Best results in Chrome/Edge.** `print-color-adjust` is best supported in Chromium-based browsers.

---

## 3. Implementation

The entire feature lives in the two Blade views — there is no controller method, route, or PHP package dedicated to PDF generation. It consists of two parts per page: (a) a print stylesheet pushed into the layout's `@push('styles')` stack, and (b) a button wired to `window.print()`, plus `print:hidden` utilities scattered on non-content elements.

### 3.1 Reports page

**File:** [resources/views/admin/reports/index.blade.php](../resources/views/admin/reports/index.blade.php)

**Print stylesheet** (top of the file):

```blade
@push('styles')
<style>
    @media print {
        /* Force brand colors, donut strokes and KPI hairlines to print */
        *, *::before, *::after {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body { background: #fff !important; }
        @page { margin: 14mm; }
        /* Keep panels and table rows from splitting across pages */
        section, .print-kpis { break-inside: avoid; }
        tr { break-inside: avoid; }
        /* Flatten shadows for clean paper output */
        .shadow-card { box-shadow: none !important; }
    }
</style>
@endpush
```

**The "Cetak PDF" button** (inside the controls bar, which is itself `print:hidden`):

```blade
<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-7 print:hidden">
    {{-- ...period preset form and custom date-range form... --}}

    <button type="button" onclick="window.print()" class="btn-secondary btn-sm"
            title="Cetak atau simpan sebagai PDF">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"/>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8"/>
        </svg>
        Cetak PDF
    </button>
</div>
```

The KPI band carries the `print-kpis` class so the `break-inside: avoid` rule keeps it intact:

```blade
<div class="print-kpis grid grid-cols-2 lg:grid-cols-4 gap-px bg-rule border border-rule
            rounded-xl shadow-card overflow-hidden mb-6">
    {{-- KPI cells --}}
</div>
```

### 3.2 Audit Log page

**File:** [resources/views/admin/audit-log/index.blade.php](../resources/views/admin/audit-log/index.blade.php)

**Print stylesheet** (top of the file):

```blade
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
```

**The "Cetak PDF" button** (in the page header `<x-slot:actions>`):

```blade
<button type="button" onclick="window.print()" class="btn-secondary btn-sm print:hidden"
        title="Cetak atau simpan sebagai PDF">
    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 6 2 18 2 18 9"/>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
        <rect x="6" y="14" width="12" height="8"/>
    </svg>
    Cetak PDF
</button>
```

**Force-revealing collapsed diffs.** Each logbook-change entry has an Alpine.js toggle that hides its before/after diff (`x-show="open"`). On screen the diff is collapsed by default; the `.audit-diff { display: block !important; }` print rule overrides Alpine's inline `display:none` so **all** diffs appear in the PDF:

```blade
{{-- Expandable before/after diff (logbook edits) --}}
@if (! empty($log['changes']))
    <div x-data="{ open: false }">
        <button type="button" @click="open = !open" :aria-expanded="open"
                class="print:hidden mt-2 inline-flex items-center gap-1.5 ...">
            <span x-text="open ? 'Sembunyikan perubahan' : 'Lihat perubahan'"></span>
        </button>
        <div x-show="open" style="display:none"
             class="audit-diff mt-2.5 border border-rule rounded-lg overflow-hidden font-mono text-xs">
            @foreach ($log['changes'] as $ch)
                {{-- before (struck-through) → after --}}
            @endforeach
        </div>
    </div>
@endif
```

**Elements hidden from print** use the Tailwind `print:hidden` utility — the stats band keeps `print-keep` so it survives:

```blade
{{-- Kept in the printout --}}
<div class="print-keep grid grid-cols-2 sm:grid-cols-4 ...">...</div>

{{-- Removed from the printout --}}
<form method="GET" action="{{ route('admin.audit-log.index') }}" class="... print:hidden">...</form>
<div class="flex items-center justify-between mt-8 text-xs ... print:hidden">{{-- pagination/footer --}}</div>
```

---

## 4. How it works (summary)

1. The user clicks **Cetak PDF** → the inline `onclick="window.print()"` opens the browser's native print dialog.
2. The browser applies the `@media print` rules:
   - `print:hidden` elements (controls, buttons, pagination) are removed.
   - `print-color-adjust: exact` forces the brand palette and chart colors to render.
   - `break-inside: avoid` prevents cards and table rows from splitting awkwardly across pages.
   - On the Audit Log, all `.audit-diff` panels are expanded so no change history is lost.
3. The user picks **"Save as PDF"** as the print destination and saves the file.

Because the export is driven entirely by CSS + the browser, it requires **no server resources, no queue, and no additional dependencies**, and it always stays in sync with whatever the page renders.

---

## 5. Key reference points

| Concern | Location |
|---|---|
| Reports print CSS | [resources/views/admin/reports/index.blade.php](../resources/views/admin/reports/index.blade.php) — `@push('styles')` block (top) |
| Reports print button | same file — controls bar, `onclick="window.print()"` |
| Audit Log print CSS | [resources/views/admin/audit-log/index.blade.php](../resources/views/admin/audit-log/index.blade.php) — `@push('styles')` block (top) |
| Audit Log print button | same file — page header `<x-slot:actions>` |
| Diff force-reveal rule | Audit Log CSS — `.audit-diff { display: block !important; }` |
| Hidden-on-print utility | Tailwind `print:hidden` class (built into the compiled CSS) |
