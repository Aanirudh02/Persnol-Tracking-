<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($request->input('login'));
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Check whether loginInput is email or phone
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $loginInput)->first();

        if ($user && Hash::check($password, $user->password)) {
            if (! $user->is_active) {
                return back()->withErrors(['login' => 'Your account has been deactivated. Please contact an administrator.'])->withInput();
            }

            Auth::login($user, $remember);
            $user->update(['last_login_at' => Carbon::now()]);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('login', 'remember'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return back()->with('status', 'If an account exists with that email, a password reset link has been sent.');
        }

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => Carbon::now()]
        );

        // For local / production email: in development we also provide the reset URL directly if mailer is log
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $request->email]);

        // Attempt sending email via configured mailer
        try {
            Mail::raw("Reset your password by visiting: {$resetUrl}", function ($message) use ($user) {
                $message->to($user->email)->subject('Password Reset Request - LifeTracker');
            });
        } catch (\Exception $e) {
            // Silently handle if SMTP is not configured yet in local
        }

        return back()->with([
            'status' => 'If an account exists with that email, a password reset link has been sent.',
            'dev_reset_url' => app()->environment('local') ? $resetUrl : null,
        ]);
    }

    public function showResetPassword(Request $request, $token = null)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Invalid or expired password reset token.']);
        }

        // Check expiry (60 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return back()->withErrors(['email' => 'Password reset token has expired.']);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->update(['password' => Hash::make($request->password)]);
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return redirect()->route('login')->with('success', 'Your password has been reset! You can now log in.');
        }

        return back()->withErrors(['email' => 'User not found.']);
    }
}
