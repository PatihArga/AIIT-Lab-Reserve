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

    @php
        // Repopulate dynamic member rows after validation error.
        $oldMembers = old('members', [['name' => '', 'nim' => '']]);
        if (empty($oldMembers)) {
            $oldMembers = [['name' => '', 'nim' => '']];
        }
    @endphp

    <div class="max-w-2xl mx-auto">
        <form method="POST" action="{{ route('admin.teams.store') }}"
              x-data="{
                  members: {{ \Illuminate\Support\Js::from($oldMembers) }},
                  addMember() { this.members.push({ name: '', nim: '' }) },
                  removeMember(i) { if (this.members.length > 1) this.members.splice(i, 1) }
              }">
            @csrf

            <x-section label="Identitas Tim">
                <div class="space-y-5">

                    <div class="form-field">
                        <label class="form-label form-required">Nama Tim</label>
                        <input type="text" name="team_name" value="{{ old('team_name') }}"
                               class="form-input @error('team_name') border-status-rejected @enderror"
                               placeholder="cth. Tim Alpha, Tim Riset Sensor" required>
                        <p class="form-hint">Nama tim akan muncul di semua permintaan reservasi.</p>
                        @error('team_name') <p class="text-xs text-status-rejected mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Email Tim</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="form-input @error('email') border-status-rejected @enderror"
                               placeholder="cth. tim.alpha@ukrida.ac.id" required>
                        <p class="form-hint">Email login untuk akun tim ini.</p>
                        @error('email') <p class="text-xs text-status-rejected mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Program Studi</label>
                        <select name="study_program_id" required
                                class="form-select @error('study_program_id') border-status-rejected @enderror">
                            <option value="" disabled @selected(! old('study_program_id'))>Pilih program studi…</option>
                            @foreach ($studyPrograms as $sp)
                                <option value="{{ $sp->id }}" @selected(old('study_program_id') == $sp->id)>{{ $sp->name }}</option>
                            @endforeach
                        </select>
                        @error('study_program_id') <p class="text-xs text-status-rejected mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">PIC (Dosen Penanggung Jawab)</label>
                        <select name="pic_user_id" required
                                class="form-select @error('pic_user_id') border-status-rejected @enderror">
                            <option value="" disabled @selected(! old('pic_user_id'))>Pilih dosen…</option>
                            @foreach ($lecturers as $l)
                                <option value="{{ $l->id }}" @selected(old('pic_user_id') == $l->id)>
                                    {{ $l->name }}{{ $l->studyProgram ? ' — ' . $l->studyProgram->name : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="form-hint">Dosen yang bertanggung jawab atas tim ini.</p>
                        @error('pic_user_id') <p class="text-xs text-status-rejected mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </x-section>

            {{-- Team members --}}
            <x-section label="Anggota Tim (Mahasiswa)">
                <p class="text-sm text-ink-700/60 mb-4">
                    Tambahkan daftar mahasiswa yang tergabung dalam tim ini. Baris kosong akan diabaikan.
                </p>

                @error('members.*.name') <p class="text-xs text-status-rejected mb-2">{{ $message }}</p> @enderror
                @error('members.*.nim') <p class="text-xs text-status-rejected mb-2">{{ $message }}</p> @enderror

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
