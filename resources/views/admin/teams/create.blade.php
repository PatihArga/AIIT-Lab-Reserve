<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Pengguna & Tim"
            title="Buat Tim Baru">
            <x-slot:actions>
                <a href="{{ route('admin.users.index') }}" class="btn-ghost btn-sm">← Kembali</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-2xl mx-auto">
        <form method="POST" action="{{ route('admin.users.index') }}"
              x-data="{
                  members: [{ name: '', nim: '' }],
                  addMember() { this.members.push({ name: '', nim: '' }) },
                  removeMember(i) { if (this.members.length > 1) this.members.splice(i, 1) }
              }">
            @csrf

            <x-section label="Identitas Tim">
                <div class="space-y-5">

                    <div class="form-field">
                        <label class="form-label form-required">Nama Tim</label>
                        <input type="text" name="team_name" class="form-input"
                               placeholder="cth. Tim Alpha, Tim Riset Sensor" required>
                        <p class="form-hint">Nama tim akan muncul di semua permintaan reservasi.</p>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Email Tim</label>
                        <input type="email" name="email" class="form-input"
                               placeholder="cth. tim.alpha@ukrida.ac.id" required>
                        <p class="form-hint">Email login untuk akun tim ini.</p>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Program Studi</label>
                        <select name="study_program_id" class="form-select" required>
                            <option value="" disabled selected>Pilih program studi…</option>
                            <option value="1">Teknik Informatika</option>
                            <option value="2">Sistem Informasi</option>
                            <option value="3">Teknik Elektro</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">PIC (Dosen Penanggung Jawab)</label>
                        <select name="pic_user_id" class="form-select" required>
                            <option value="" disabled selected>Pilih dosen…</option>
                            <option value="1">Dr. Budi Santoso — Teknik Informatika</option>
                            <option value="2">Dr. Siti Hartati — Teknik Informatika</option>
                            <option value="3">Dr. Maria Lestari — Sistem Informasi</option>
                        </select>
                        <p class="form-hint">Dosen yang bertanggung jawab atas tim ini.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-field">
                            <label class="form-label form-required">Password</label>
                            <input type="password" name="password" class="form-input" placeholder="Minimal 8 karakter" required>
                        </div>
                        <div class="form-field">
                            <label class="form-label form-required">Konfirmasi</label>
                            <input type="password" name="password_confirmation" class="form-input" required>
                        </div>
                    </div>

                </div>
            </x-section>

            {{-- Team members --}}
            <x-section label="Anggota Tim (Mahasiswa)">
                <p class="text-sm text-ink-700/60 mb-4">
                    Tambahkan daftar mahasiswa yang tergabung dalam tim ini.
                </p>

                <div class="space-y-3">
                    <template x-for="(member, index) in members" :key="index">
                        <div class="flex items-start gap-3">
                            <div class="grid grid-cols-[1fr_160px] gap-3 flex-1">
                                <div class="form-field">
                                    <label class="form-label">Nama Mahasiswa</label>
                                    <input type="text" :name="'members['+index+'][name]'"
                                           x-model="member.name"
                                           class="form-input"
                                           :placeholder="'Mahasiswa ' + (index + 1)">
                                </div>
                                <div class="form-field">
                                    <label class="form-label">NIM</label>
                                    <input type="text" :name="'members['+index+'][nim]'"
                                           x-model="member.nim"
                                           class="form-input font-mono"
                                           placeholder="2024XXXXX">
                                </div>
                            </div>
                            <button type="button" @click="removeMember(index)"
                                    class="btn-icon mt-6 text-ink-700/40 hover:text-status-rejected shrink-0"
                                    :disabled="members.length === 1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <button type="button" @click="addMember()"
                        class="mt-4 flex items-center gap-2 text-sm font-medium text-ink-700/60 hover:text-ink-900 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Anggota
                </button>

            </x-section>

            <div class="flex items-center justify-between pt-6 border-t border-rule">
                <a href="{{ route('admin.users.index') }}" class="btn-ghost">Batal</a>
                <button type="submit" class="btn-mark">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Buat Tim
                </button>
            </div>

        </form>
    </div>

</x-app-layout>
