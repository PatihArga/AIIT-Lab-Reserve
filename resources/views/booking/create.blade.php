<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Reservasi Baru"
            title="Buat Reservasi">
            <x-slot:actions>
                <x-step-indicator
                    :steps="['Pilih Tipe', 'Jadwal', 'Informasi', 'Tinjau']"
                    :current="1" />
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-2xl mx-auto">

        <p class="text-sm text-ink-700/60 mb-8">
            Pilih jenis reservasi yang sesuai dengan kebutuhan Anda. Setiap jenis menentukan sumber daya yang akan dipesan.
        </p>

        {{-- x-data on the form so the button shares the same Alpine scope as the radios --}}
        <form action="{{ route('booking.schedule') }}" method="GET"
              x-data="{ selected: '' }"
              @submit.prevent="selected && $el.submit()">

            <div class="space-y-3 mb-10">

                {{-- Option 1: Komputer Saja --}}
                <label class="block cursor-pointer">
                    <input type="radio" name="type" value="computers_only" class="sr-only peer"
                           x-model="selected">
                    <div class="flex items-start gap-5 p-5 rounded-xl border-2 border-rule bg-white
                                peer-checked:border-ink-700 peer-checked:bg-ink-50/40
                                hover:border-ink-300 transition-all duration-150">
                        <div class="w-10 h-10 rounded-lg bg-ink-50 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-5 h-5 text-ink-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-ink-900">Komputer Saja</span>
                                <span class="text-[10px] uppercase tracking-label font-semibold text-ink-700/50 border border-rule rounded px-1.5 py-0.5">
                                    Pilih unit spesifik
                                </span>
                            </div>
                            <p class="text-sm text-ink-700/60 mt-1">
                                Pesan satu atau lebih unit komputer tertentu (PC-01 hingga PC-09) tanpa memonopoli seluruh ruangan.
                            </p>
                            <div class="mt-3" x-show="selected === 'computers_only'" x-transition>
                                <span class="text-[11px] text-ink-700/50">→ Anda akan memilih unit spesifik di langkah Jadwal</span>
                            </div>
                        </div>
                    </div>
                </label>

                {{-- Option 2: Ruang + Komputer --}}
                <label class="block cursor-pointer">
                    <input type="radio" name="type" value="full_room" class="sr-only peer"
                           x-model="selected">
                    <div class="flex items-start gap-5 p-5 rounded-xl border-2 border-rule bg-white
                                peer-checked:border-mark-500 peer-checked:bg-mark-50/60
                                hover:border-mark-300 transition-all duration-150">
                        <div class="w-10 h-10 rounded-lg bg-mark-50 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-5 h-5 text-mark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-ink-900">Ruang + Komputer</span>
                                <span class="text-[10px] uppercase tracking-label font-semibold text-mark-600/70 border border-mark-300/60 rounded px-1.5 py-0.5 bg-mark-50">
                                    Seluruh lab
                                </span>
                            </div>
                            <p class="text-sm text-ink-700/60 mt-1">
                                Pesan seluruh ruangan beserta semua 9 unit komputer yang tersedia. Cocok untuk praktikum kelas penuh.
                            </p>
                        </div>
                    </div>
                </label>

                {{-- Option 3: Ruang Saja --}}
                <label class="block cursor-pointer">
                    <input type="radio" name="type" value="room_only" class="sr-only peer"
                           x-model="selected">
                    <div class="flex items-start gap-5 p-5 rounded-xl border-2 border-rule bg-white
                                peer-checked:border-ink-700 peer-checked:bg-ink-50/40
                                hover:border-ink-300 transition-all duration-150">
                        <div class="w-10 h-10 rounded-lg bg-ink-50 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-5 h-5 text-ink-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span class="font-semibold text-ink-900">Ruang Saja</span>
                            <p class="text-sm text-ink-700/60 mt-1">
                                Pesan ruangan tanpa komputer. Pilihan berbagi ruang tersedia jika slot lain masih memungkinkan.
                            </p>
                            {{-- Sharing sub-options --}}
                            <div class="mt-4 space-y-2" x-show="selected === 'room_only'" x-transition>
                                <p class="text-xs font-semibold uppercase tracking-label text-ink-700/60 mb-2">Mode penggunaan</p>
                                <label class="flex items-center gap-3 p-3 rounded-lg border border-rule bg-white hover:bg-ink-50/60 cursor-pointer has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/40 transition-all">
                                    <input type="radio" name="room_sharing" value="exclusive" class="accent-ink-700">
                                    <div>
                                        <div class="text-sm font-medium text-ink-900">Eksklusif</div>
                                        <div class="text-xs text-ink-700/50">Ruangan hanya untuk Anda, tidak ada pengguna lain di slot yang sama</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-3 rounded-lg border border-rule bg-white hover:bg-ink-50/60 cursor-pointer has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/40 transition-all">
                                    <input type="radio" name="room_sharing" value="shared" class="accent-ink-700">
                                    <div>
                                        <div class="text-sm font-medium text-ink-900">Berbagi</div>
                                        <div class="text-xs text-ink-700/50">Ruangan dapat digunakan bersama dengan pemohon lain di slot yang sama</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </label>

            </div>

            <div class="flex items-center justify-between pt-6 border-t border-rule">
                <a href="{{ route('dashboard') }}" class="btn-ghost">
                    ← Batal
                </a>
                <button type="submit"
                        :disabled="!selected"
                        :class="selected ? 'btn-mark btn-lg' : 'btn-mark btn-lg opacity-40 cursor-not-allowed'">
                    Lanjut: Pilih Jadwal
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

        </form>
    </div>

</x-app-layout>
