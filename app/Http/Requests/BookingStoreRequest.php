<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Final POST has no body fields — all data lives in session('booking_draft').
     * Session presence is checked in BookingController::store().
     */
    public function rules(): array
    {
        return [];
    }
}
