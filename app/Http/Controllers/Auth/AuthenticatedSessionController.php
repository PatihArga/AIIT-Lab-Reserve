<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginEmailRequest;
use App\Http\Requests\Auth\LoginAuthenticateRequest;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Step 1: Display the email entry form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Step 1 POST: Detect study program from email domain.
     * On success, redirect to Step 2 with study program + user list in session.
     */
    public function detectStudyProgram(LoginEmailRequest $request): RedirectResponse
    {
        $email = strtolower(trim($request->input('email')));

        $program = StudyProgram::where('email', $email)
            ->where('is_active', true)
            ->first();

        if (! $program) {
            throw ValidationException::withMessages([
                'email' => 'Gmail program studi tidak terdaftar.',
            ]);
        }

        $hasUsers = User::where('study_program_id', $program->id)
            ->where('is_active', true)
            ->where('role', '!=', 'admin')
            ->exists();

        if (! $hasUsers) {
            throw ValidationException::withMessages([
                'email' => 'Belum ada akun aktif pada program studi ini. Hubungi admin.',
            ]);
        }

        $request->session()->put('login.study_program_id', $program->id);
        $request->session()->put('login.email_attempted', $email);

        return redirect()->route('login.select');
    }

    /**
     * Step 2: Display the user dropdown + password form.
     */
    public function selectUser(Request $request): View|RedirectResponse
    {
        $programId = $request->session()->get('login.study_program_id');

        if (! $programId) {
            return redirect()->route('login');
        }

        $program = StudyProgram::find($programId);
        if (! $program) {
            $request->session()->forget(['login.study_program_id', 'login.email_attempted']);
            return redirect()->route('login');
        }

        $users = User::where('study_program_id', $programId)
            ->where('is_active', true)
            ->where('role', '!=', 'admin')
            ->orderByRaw("FIELD(role, 'lecturer', 'team')")
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('auth.select-user', compact('program', 'users'));
    }

    /**
     * Step 2 POST: Authenticate the selected user.
     */
    public function authenticate(LoginAuthenticateRequest $request): RedirectResponse
    {
        $programId = $request->session()->get('login.study_program_id');

        if (! $programId) {
            return redirect()->route('login');
        }

        $this->ensureIsNotRateLimited($request);

        $user = User::where('id', $request->input('user_id'))
            ->where('study_program_id', $programId)
            ->where('is_active', true)
            ->first();

        if (! $user || ! Auth::attempt(
            ['id' => $user->id, 'password' => $request->input('password')],
            $request->boolean('remember')
        )) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'password' => 'Kombinasi nama dan kata sandi tidak cocok.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $user->forceFill(['last_login_at' => now()])->save();

        $request->session()->regenerate();
        $request->session()->forget(['login.study_program_id', 'login.email_attempted']);

        return redirect()->intended($this->redirectPathByRole($user->role));
    }

    /**
     * Admin login POST: authenticate directly with email + password.
     */
    public function adminAuthenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'gmail'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'gmail.required'    => 'Gmail wajib diisi.',
            'gmail.email'       => 'Format Gmail tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $this->ensureIsNotRateLimitedByEmail($request);

        $admin = User::where('gmail', $request->input('gmail'))
            ->where('role', 'admin')
            ->first();

        if (! $admin || ! Hash::check($request->input('password'), $admin->password)) {
            RateLimiter::hit($this->throttleKeyByEmail($request));

            throw ValidationException::withMessages([
                'gmail' => 'Gmail atau kata sandi tidak cocok.',
            ]);
        }

        if (! $admin->is_active) {
            throw ValidationException::withMessages([
                'gmail' => 'Akun admin nonaktif.',
            ]);
        }

        RateLimiter::clear($this->throttleKeyByEmail($request));

        Auth::login($admin, $request->boolean('remember'));
        $admin->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function redirectPathByRole(string $role): string
    {
        return $role === 'admin'
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'password' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(
            $request->session()->get('login.email_attempted', '').'|'.$request->ip()
        );
    }

    protected function ensureIsNotRateLimitedByEmail(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKeyByEmail($request), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKeyByEmail($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKeyByEmail(Request $request): string
    {
        $key = $request->input('gmail') ?? $request->input('email', '');
        return Str::transliterate(Str::lower($key) . '|' . $request->ip());
    }
}
