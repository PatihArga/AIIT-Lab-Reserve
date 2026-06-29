# Booking History Page

**Project:** UKRIDA / AIIT Lab Reserve System
**Scope:** User → Riwayat Reservasi (`/booking/history`)
**Audience:** Lecturer and Team roles
**Date:** 2026-06-23

---

## 1. Overview

The **Booking History** page (*Riwayat Reservasi*) lists every reservation the currently
logged-in user has ever submitted — regardless of status — in one filterable, paginated view.
It is the user-facing record of their own activity and the entry point to each booking's
detail page.

Key characteristics:

- **Scoped to the current user.** The query always starts from `auth()->user()->bookings()`, so a user can only ever see their own reservations (no other user's data is reachable from here).
- **Filterable** by status, booking code (search), and date.
- **Responsive** — a card layout on mobile, a data table on desktop, both rendering the same data.
- **Paginated** at 15 rows per page, with filters preserved across page links.
- **Pending requests are visually emphasised** with a left accent bar / highlighted row.

---

## 2. Usage Guide

### 2.1 Opening the page

Log in as a Lecturer or Team account and navigate to **Riwayat Reservasi**
(`/booking/history`). The page header also offers a **"Buat Reservasi"** button that links
to the calendar for creating a new booking.

### 2.2 Filtering

The filter bar at the top combines three controls; all of them submit as GET query
parameters, so any filtered view is a shareable/bookmarkable URL.

| Control | Parameter | Behaviour |
|---|---|---|
| **Status chips** | `status` | Click a chip to show only that status. Options: **Semua** (all), **Diajukan** (submitted), **Tinjauan** (under review), **Disetujui** (approved), **Selesai** (completed), **Ditolak** (rejected), **Dibatalkan** (cancelled). "Semua" clears the filter. |
| **Search by code** | `q` | Type part of a booking code (e.g. `LAB-00`) to match codes containing that text. |
| **Date** | `date` | Pick a calendar date to show only bookings on that day. |

Notes on behaviour:

- The status chips are **links** that merge into the current query string (preserving `q` and `date`) while resetting pagination — so changing status never strands you on a now-empty page 3.
- The **Filter** button submits the search box and the date picker together.
- The active status chip is highlighted (dark background) so the current filter is always visible.

### 2.3 Reading the list

Each row / card shows:

- **Kode** — the unique booking code (e.g. `LAB-0007`).
- **Jenis** — the booking type, localized: *Ruang + Komputer* (`full_room`), *Komputer Saja* (`computers_only`), *Ruang Saja* (`room_only`). For computer-only bookings the unit count is appended, e.g. *Komputer Saja (3 unit)*.
- **Tanggal & Waktu** — the date (e.g. `7 Jun 2026`) and the time range (e.g. `09:00 – 11:00`).
- **Kategori** — the logbook category (*Penelitian*, *Project Akademik*, *Praktikum*, *Tugas Akhir / Skripsi*, *Lainnya*), or `—` if no logbook is attached yet.
- **Status** — a colored status badge.
- **Lihat** — a link to that booking's detail page (`/booking/{id}`).

Reservations still awaiting a decision (**submitted** or **under_review**) are drawn with a
left accent bar (mobile cards) or a highlighted row (`row-mark`, desktop table) to draw the
eye to items that may need follow-up.

### 2.4 Empty state & pagination

- If the user has no bookings matching the current filter, an **empty state** is shown: *"Belum ada reservasi"*.
- When there are more than 15 results, pagination links appear at the bottom. Filters are carried across pages via `withQueryString()`.

---

## 3. Implementation

The page is one controller method plus one Blade view. There is no dedicated service —
filtering is done directly on the Eloquent query.

### 3.1 Route

The route is registered in the authenticated (`auth` + `active`) group:

```php
// routes/web.php
Route::get('/booking/history', [BookingController::class, 'history'])->name('booking.history');
```

### 3.2 Controller

**File:** [app/Http/Controllers/BookingController.php](../app/Http/Controllers/BookingController.php)

```php
public function history(Request $request): View
{
    $query = auth()->user()->bookings()
        ->with(['computers', 'logbook'])      // eager-load to avoid N+1 in the list
        ->latest('date')->latest('start_time'); // newest first, then by start time

    // Status filter — "all" (or absent) means no constraint.
    if ($request->filled('status') && $request->status !== 'all') {
        $query->where('status', $request->status);
    }

    // Exact-day filter.
    if ($request->filled('date')) {
        $query->whereDate('date', $request->date);
    }

    // Partial booking-code search.
    if ($request->filled('q')) {
        $query->where('booking_code', 'like', '%' . $request->q . '%');
    }

    // 15 per page; withQueryString() keeps active filters on page links.
    $bookings = $query->paginate(15)->withQueryString();

    return view('booking.history', compact('bookings'));
}
```

**What each part does:**

- `auth()->user()->bookings()` — restricts the result set to the logged-in user's own bookings (ownership is enforced by the relationship, not a `where user_id` the controller could forget).
- `->with(['computers', 'logbook'])` — eager-loads the two relations the view reads per row (`$b->computers->count()` and `$b->logbook->category`), preventing N+1 queries.
- `->latest('date')->latest('start_time')` — orders newest reservation date first, breaking ties by start time.
- The three `if ($request->filled(...))` blocks apply the **status**, **date**, and **code search** filters only when present.
- `->paginate(15)->withQueryString()` — paginates and re-appends the current query string so filter selections survive page navigation.

### 3.3 View

**File:** [resources/views/booking/history.blade.php](../resources/views/booking/history.blade.php)

**Local lookup maps** (top of the view) translate enum values to Indonesian labels and
provide a short-month array for date formatting:

```blade
@php
    $typeLabelMap = [
        'full_room'      => 'Ruang + Komputer',
        'computers_only' => 'Komputer Saja',
        'room_only'      => 'Ruang Saja',
    ];
    $categoryMap = [
        'penelitian'       => 'Penelitian',
        'project_akademik' => 'Project Akademik',
        'praktikum'        => 'Praktikum',
        'tugas_akhir'      => 'Tugas Akhir / Skripsi',
        'lainnya'          => 'Lainnya',
    ];
    $monthShort = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $activeStatus = request('status', 'all');
@endphp
```

**Filter bar** — status chips (links) plus the search/date form:

```blade
<form method="GET" action="{{ route('booking.history') }}" class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
    {{-- Status chips --}}
    <div class="flex flex-wrap gap-2">
        @foreach (['all' => 'Semua', 'submitted' => 'Diajukan', 'under_review' => 'Tinjauan', 'approved' => 'Disetujui', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'] as $val => $label)
            <a href="{{ route('booking.history', array_merge(request()->except('status', 'page'), $val === 'all' ? [] : ['status' => $val])) }}"
               class="{{ $activeStatus === $val ? 'bg-ink-900 text-white border-ink-900' : 'bg-white text-ink-700/70 border-rule hover:border-ink-300' }} px-3 sm:px-3.5 py-1.5 text-[11px] sm:text-xs font-semibold uppercase tracking-label border rounded-md transition-all whitespace-nowrap inline-block">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Search + date --}}
    <div class="flex gap-2 sm:ml-auto">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode…"
               class="form-input py-1.5 text-xs flex-1 sm:w-44 sm:flex-none">
        <input type="date" name="date" value="{{ request('date') }}"
               class="form-input py-1.5 text-xs flex-1 sm:w-36 sm:flex-none">
        <button type="submit" class="btn-ghost btn-sm">Filter</button>
    </div>
</form>
```

> The chips use `array_merge(request()->except('status', 'page'), …)` so clicking a status
> preserves the search term and date while dropping the old `page` number. Selecting **Semua**
> omits the `status` key entirely.

**Empty state** when there are no results:

```blade
@if ($bookings->isEmpty())
    <x-empty-state
        title="Belum ada reservasi"
        desc="Reservasi yang Anda buat akan muncul di sini." />
@else
    {{-- cards + table --}}
@endif
```

**Per-row formatting** (used identically by the mobile cards and the desktop table):

```blade
@php
    $typeLabel = $typeLabelMap[$b->booking_type] ?? $b->booking_type;
    if ($b->booking_type === 'computers_only') {
        $typeLabel .= ' (' . $b->computers->count() . ' unit)';
    }
    $dateObj = $b->date;
    $dateStr = $dateObj->day . ' ' . $monthShort[$dateObj->month - 1] . ' ' . $dateObj->year;
    $time    = substr($b->start_time, 0, 5) . ' – ' . substr($b->end_time, 0, 5);
@endphp
```

**Mobile cards** (`sm:hidden`) — each card is a link to the detail page, with a left accent
bar for pending requests:

```blade
<div class="sm:hidden space-y-3">
    @foreach ($bookings as $b)
        {{-- ...formatting @php block... --}}
        <a href="{{ route('booking.show', $b) }}"
           class="block bg-white border border-rule rounded-xl shadow-card p-4
                  hover:shadow-md active:scale-[0.99] transition-all
                  {{ in_array($b->status, ['submitted','under_review']) ? 'border-l-[3px] border-l-mark-500' : '' }}">
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="font-mono text-sm font-semibold text-ink-900">{{ $b->booking_code }}</span>
                <x-badge :status="$b->status" />
            </div>
            <div class="text-sm font-medium text-ink-900 mb-1">{{ $typeLabel }}</div>
            <div class="flex items-center gap-2 text-xs text-ink-700/60 font-mono">
                <span>{{ $dateStr }}</span>
                <span class="text-ink-700/30">·</span>
                <span>{{ $time }}</span>
            </div>
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-rule">
                <span class="text-xs text-ink-700/70">{{ $categoryMap[$b->logbook->category ?? null] ?? '—' }}</span>
                <span class="text-xs font-semibold text-ink-700 inline-flex items-center gap-1">Lihat …</span>
            </div>
        </a>
    @endforeach
</div>
```

**Desktop table** (`hidden sm:block`) — same data as columns; pending rows get the
`row-mark` highlight class:

```blade
<div class="hidden sm:block bg-white border border-rule rounded-xl shadow-card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Jenis</th>
                <th>Tanggal & Waktu</th>
                <th>Kategori</th>
                <th>Status</th>
                <th class="text-right"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bookings as $b)
                {{-- ...formatting @php block... --}}
                <tr class="{{ in_array($b->status, ['submitted','under_review']) ? 'row-mark' : '' }}">
                    <td class="mono-data">{{ $b->booking_code }}</td>
                    <td class="text-ink-700/80">{{ $typeLabel }}</td>
                    <td>
                        <div class="mono-data text-ink-900 text-sm">{{ $dateStr }}</div>
                        <div class="mono-code">{{ $time }}</div>
                    </td>
                    <td class="text-ink-700/70 text-sm">{{ $categoryMap[$b->logbook->category ?? null] ?? '—' }}</td>
                    <td><x-badge :status="$b->status" /></td>
                    <td class="text-right">
                        <a href="{{ route('booking.show', $b) }}" class="btn-ghost btn-sm">Lihat</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

**Pagination** — Laravel's built-in paginator links (filters already carried by
`withQueryString()` in the controller):

```blade
<div class="mt-4">
    {{ $bookings->links() }}
</div>
```

---

## 4. How it works (summary)

1. The user opens `/booking/history`; the request hits `BookingController@history`.
2. The controller builds a query scoped to the user's own bookings, eager-loads `computers` and `logbook`, and orders newest-first.
3. Any present `status`, `date`, or `q` (code) request parameters narrow the query.
4. Results are paginated at 15/page with the query string preserved, then passed to the view.
5. The view renders a localized, responsive list — cards on mobile, a table on desktop — with pending requests highlighted and each row linking to the booking detail page.

---

## 5. Key reference points

| Concern | Location |
|---|---|
| Controller method | [app/Http/Controllers/BookingController.php](../app/Http/Controllers/BookingController.php) → `history()` |
| Blade view | [resources/views/booking/history.blade.php](../resources/views/booking/history.blade.php) |
| Route definition | `routes/web.php` → `booking.history` |
| Status badge component | `<x-badge :status="…" />` |
| Empty-state component | `<x-empty-state />` |
| Detail page (linked from each row) | `BookingController@show` → `booking.show` |
