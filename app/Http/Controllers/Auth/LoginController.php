<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request, SettingsService $settings, AuditLogService $audit)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->login;
        $maxAttempts = (int) $settings->get('security.max_failed_attempts');
        $lockoutMinutes = (int) $settings->get('security.lockout_minutes');

        $user = User::where('email', $login)->orWhere('username', $login)->first();

        // A temporary lock expires on its own after the configured window.
        if ($user && $user->locked_at) {
            $unlockAt = $user->locked_at->copy()->addMinutes($lockoutMinutes);
            if ($unlockAt->isFuture()) {
                $mins = (int) ceil(now()->diffInMinutes($unlockAt, false));

                return back()->with('error', "This account is locked after too many failed attempts. Try again in {$mins} minute(s) or ask an administrator.");
            }
            $user->update(['locked_at' => null, 'failed_login_attempts' => 0]);
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            if ($user) {
                $user->increment('failed_login_attempts');

                if ($user->failed_login_attempts >= $maxAttempts) {
                    $user->update(['locked_at' => now()]);
                    $audit->record('user.locked', $user, null, ['failed_attempts' => $user->failed_login_attempts], 'Locked after too many failed logins', $user->id);

                    return back()->with('error', "This account is now locked for {$lockoutMinutes} minute(s) after {$maxAttempts} failed attempts.");
                }
            }

            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.',
            ]);
        }

        if ($user->status !== 'active') {
            return back()->with('error', 'This account is inactive. Please contact an administrator.');
        }

        $user->update(['failed_login_attempts' => 0, 'locked_at' => null]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
