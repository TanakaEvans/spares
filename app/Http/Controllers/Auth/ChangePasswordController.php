<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ChangePasswordController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function update(Request $request, SettingsService $settings)
    {
        // Build the password rule from the configured security policy (rule 6).
        $rule = Password::min((int) $settings->get('security.password_min_length'))->letters();
        if ($settings->get('security.password_require_mixed_case')) {
            $rule->mixedCase();
        }
        if ($settings->get('security.password_require_number')) {
            $rule->numbers();
        }

        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', $rule],
        ]);

        $expiryDays = (int) $settings->get('security.password_expiry_days');

        $request->user()->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'password_expires_at' => $expiryDays > 0 ? now()->addDays($expiryDays) : null,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Password changed successfully!');
    }
}
