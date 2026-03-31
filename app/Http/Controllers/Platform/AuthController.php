<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('crm_logged_in') && session('crm_user_role') === 'Super Admin') {
            return redirect()->route('platform.dashboard');
        }

        return view('platform.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'platform-login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please wait {$seconds} seconds before trying again.",
            ]);
        }

        $user = DB::table('users')
            ->where('email', $data['email'])
            ->where('is_super_admin', 1)
            ->where('status', 'active')
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            return back()
                ->withErrors(['email' => 'Invalid credentials or this account does not have platform access.'])
                ->withInput(['email' => $data['email']]);
        }

        RateLimiter::clear($throttleKey);

        if ($user->totp_enabled) {
            $request->session()->put('platform_2fa_pending_user_id', $user->id);
            return redirect()->route('platform.login.2fa');
        }

        // No 2FA configured — complete login and prompt to set it up
        $this->completeLogin($request, $user);

        return redirect()->route('platform.settings.2fa')
            ->with('info', 'Your account is not yet protected with two-factor authentication. Set it up now to secure your platform access.');
    }

    public function show2faVerify(Request $request)
    {
        if (!$request->session()->has('platform_2fa_pending_user_id')) {
            return redirect()->route('platform.login');
        }

        return view('platform.auth.2fa-verify');
    }

    public function verify2fa(Request $request)
    {
        $userId = $request->session()->get('platform_2fa_pending_user_id');
        if (!$userId) {
            return redirect()->route('platform.login');
        }

        $request->validate(['one_time_password' => 'required|string']);

        $throttleKey = 'platform-2fa:' . $request->ip() . ':' . $userId;
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'one_time_password' => "Too many failed attempts. Please wait {$seconds} seconds.",
            ]);
        }

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user || !$user->totp_enabled || !$user->totp_secret) {
            $request->session()->forget('platform_2fa_pending_user_id');
            return redirect()->route('platform.login')
                ->withErrors(['email' => 'Invalid 2FA state. Please log in again.']);
        }

        try {
            $secret = Crypt::decryptString($user->totp_secret);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $request->session()->forget('platform_2fa_pending_user_id');
            return redirect()->route('platform.login')
                ->withErrors(['email' => '2FA configuration error. Please contact your administrator.']);
        }
        $google2fa = app('pragmarx.google2fa');

        if ($google2fa->verifyKey($secret, $request->one_time_password)) {
            RateLimiter::clear($throttleKey);
            $request->session()->forget('platform_2fa_pending_user_id');
            $this->completeLogin($request, $user);
            return redirect()->route('platform.dashboard');
        }

        // Recovery code fallback — allows login when authenticator device is lost
        if ($this->verifyRecoveryCode($user->id, $request->one_time_password)) {
            RateLimiter::clear($throttleKey);
            $request->session()->forget('platform_2fa_pending_user_id');
            $this->completeLogin($request, $user);
            // Flag that the session used a recovery code — enables TOTP-free 2FA reset
            session(['platform_2fa_recovery_login' => true]);
            return redirect()->route('platform.settings.2fa')
                ->with('warning', 'You logged in using a recovery code. Your authenticator app may need to be reconfigured — reset 2FA below to generate a fresh setup.');
        }

        RateLimiter::hit($throttleKey, 60);
        return back()->withErrors(['one_time_password' => 'Invalid code. Please try again, or enter a recovery code.']);
    }

    private function completeLogin(Request $request, object $user): void
    {
        $request->session()->regenerate();

        session([
            'crm_logged_in'               => true,
            'crm_user_id'                 => $user->id,
            'crm_user_name'               => $user->name,
            'crm_user_email'              => $user->email,
            'crm_user_role'               => 'Super Admin',
            'crm_is_super_admin'          => true,
            'platform_reminder_dismissed' => false,
            'crm_hotel_id'                => null,
            'crm_hotel_name'              => null,
            'crm_permissions'             => ['*'],
            'crm_user_avatar'             => null,
        ]);
    }

    public function show2faSetup(Request $request)
    {
        $userId = session('crm_user_id');
        $user   = DB::table('users')->where('id', $userId)->first();

        $google2fa = app('pragmarx.google2fa');
        $secretKey = $request->session()->get('platform_2fa_setup_secret');

        if (!$secretKey) {
            $secretKey = $google2fa->generateSecretKey();
            $request->session()->put('platform_2fa_setup_secret', $secretKey);
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name', 'Hotel CRM'),
            $user->email,
            $secretKey
        );

        $qrCodeSvg = $this->generateQrCodeSvg($qrCodeUrl);

        return view('platform.settings.2fa-setup', [
            'user'             => $user,
            'secretKey'        => $secretKey,
            'qrCodeSvg'        => $qrCodeSvg,
            'totpEnabled'      => (bool) $user->totp_enabled,
            'recoveryLogin'    => session('platform_2fa_recovery_login', false),
        ]);
    }

    public function enable2fa(Request $request)
    {
        $request->validate(['one_time_password' => 'required|string']);

        $secretKey = $request->session()->get('platform_2fa_setup_secret');
        if (!$secretKey) {
            return back()->withErrors(['one_time_password' => 'Session expired. Please refresh and try again.']);
        }

        $google2fa = app('pragmarx.google2fa');
        if (!$google2fa->verifyKey($secretKey, $request->one_time_password)) {
            return back()->withErrors(['one_time_password' => 'Invalid code. Make sure your authenticator app is synced and try again.']);
        }

        $userId        = session('crm_user_id');
        $recoveryCodes = $this->generateRecoveryCodes($userId);

        DB::table('users')->where('id', $userId)->update([
            'totp_secret'  => Crypt::encryptString($secretKey),
            'totp_enabled' => true,
            'updated_at'   => now(),
        ]);

        $request->session()->forget(['platform_2fa_setup_secret', 'platform_2fa_recovery_login']);
        $request->session()->flash('platform_2fa_new_codes', $recoveryCodes);

        return redirect()->route('platform.settings.2fa')
            ->with('success', '2FA has been enabled. Save your recovery codes below — they will not be shown again.');
    }

    public function disable2fa(Request $request)
    {
        $request->validate(['one_time_password' => 'required|string']);

        $userId = session('crm_user_id');
        $user   = DB::table('users')->where('id', $userId)->first();

        if (!$user->totp_enabled || !$user->totp_secret) {
            return back()->withErrors(['one_time_password' => '2FA is not currently enabled.']);
        }

        // If this session was established via a recovery code, allow reset without TOTP
        if (session('platform_2fa_recovery_login')) {
            if (!$this->verifyRecoveryCode($userId, $request->one_time_password)) {
                return back()->withErrors(['one_time_password' => 'Invalid recovery code.']);
            }
        } else {
            try {
                $secret = Crypt::decryptString($user->totp_secret);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return back()->withErrors(['one_time_password' => '2FA configuration error: stored secret is invalid. Please contact your administrator.']);
            }
            $google2fa = app('pragmarx.google2fa');
            if (!$google2fa->verifyKey($secret, $request->one_time_password)) {
                return back()->withErrors(['one_time_password' => 'Invalid authenticator code. 2FA was not disabled.']);
            }
        }

        DB::table('users')->where('id', $userId)->update([
            'totp_secret'  => null,
            'totp_enabled' => false,
            'updated_at'   => now(),
        ]);

        DB::table('platform_recovery_codes')->where('user_id', $userId)->delete();
        $request->session()->forget('platform_2fa_recovery_login');

        return redirect()->route('platform.settings.2fa')
            ->with('success', '2FA has been disabled. You can set it up again at any time.');
    }

    private function generateRecoveryCodes(int $userId): array
    {
        DB::table('platform_recovery_codes')->where('user_id', $userId)->delete();

        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $code    = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
            $codes[] = $code;
            DB::table('platform_recovery_codes')->insert([
                'user_id'    => $userId,
                'code_hash'  => hash('sha256', $code),
                'used'       => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $codes;
    }

    private function verifyRecoveryCode(int $userId, string $input): bool
    {
        $input = strtoupper(trim($input));
        $hash  = hash('sha256', $input);

        $row = DB::table('platform_recovery_codes')
            ->where('user_id', $userId)
            ->where('code_hash', $hash)
            ->where('used', false)
            ->first();

        if (!$row) {
            return false;
        }

        DB::table('platform_recovery_codes')
            ->where('id', $row->id)
            ->update(['used' => true, 'updated_at' => now()]);

        return true;
    }

    private function generateQrCodeSvg(string $url): string
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        return $writer->writeString($url);
    }
}
