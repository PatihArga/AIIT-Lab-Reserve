{{--
    Logbook form partial — included in booking/show.blade.php
    Only rendered when booking status is 'approved' or 'completed'
--}}
<form method="POST" action="#" class="bg-white border border-rule rounded-xl shadow-card p-6 space-y-5">
    @csrf
    @method('PUT')

    <div class="form-field">
        <label class="form-label form-required">Checkpoint / Progress Kegiatan</label>
        <textarea name="checkpoint_progress" class="form-textarea" rows="4"
                  placeholder="Jelaskan tahap kegiatan yang sudah diselesaikan dalam sesi ini…"
                  required></textarea>
        <p class="form-hint">Deskripsikan secara singkat apa yang berhasil diselesaikan.</p>
    </div>

    <div class="form-field">
        <label class="form-label">Target Sesi</label>
        <textarea name="session_target" class="form-textarea" rows="3"
                  placeholder="Apa yang ingin dicapai di akhir sesi ini?"></textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="form-field">
            <label class="form-label">Nama Pembimbing</label>
            <input type="text" name="supervisor_name" class="form-input"
                   placeholder="cth. Dr. Budi Santoso">
        </div>
        <div class="form-field">
            <label class="form-label">Mata Kuliah Terkait</label>
            <input type="text" name="related_course" class="form-input"
                   placeholder="cth. Kecerdasan Buatan">
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2 border-t border-rule">
        <button type="submit" class="btn-mark">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Logbook
        </button>
    </div>
</form>
