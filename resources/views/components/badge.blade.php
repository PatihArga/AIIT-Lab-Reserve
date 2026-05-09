@props(['status' => 'draft'])

@php
    $classes = match($status) {
        'pending'      => 'badge-pending',
        'submitted'    => 'badge-submitted',
        'under_review' => 'badge-review',
        'approved'     => 'badge-approved',
        'rejected'     => 'badge-rejected',
        'cancelled'    => 'badge-cancelled',
        'completed'    => 'badge-completed',
        'draft'        => 'badge-draft',
        default        => 'badge-outline',
    };
    $label = match($status) {
        'pending'      => 'Menunggu',
        'submitted'    => 'Diajukan',
        'under_review' => 'Ditinjau',
        'approved'     => 'Disetujui',
        'rejected'     => 'Ditolak',
        'cancelled'    => 'Dibatalkan',
        'completed'    => 'Selesai',
        'draft'        => 'Draf',
        default        => $status,
    };
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @isset($slot)
        @if (trim($slot) !== '') {{ $slot }} @else {{ $label }} @endif
    @else
        {{ $label }}
    @endisset
</span>
