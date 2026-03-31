<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

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

        $user = DB::table('users')
            ->where('email', $data['email'])
            ->where('is_super_admin', 1)
            ->where('status', 'active')
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Invalid credentials or this account does not have platform access.'])
                ->withInput(['email' => $data['email']]);
        }

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

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user || !$user->totp_enabled || !$user->totp_secret) {
            $request->session()->forget('platform_2fa_pending_user_id');
            return redirect()->route('platform.login')
                ->withErrors(['email' => 'Invalid 2FA state. Please log in again.']);
        }

        $secret = Crypt::decryptString($user->totp_secret);
        $google2fa = app('pragmarx.google2fa');

        // Check regular TOTP code
        if ($google2fa->verifyKey($secret, $request->one_time_password)) {
            $request->session()->forget('platform_2fa_pending_user_id');
            $this->completeLogin($request, $user);
            return redirect()->route('platform.dashboard');
        }

        // Check recovery codes
        if ($this->verifyRecoveryCode($user->id, $request->one_time_password)) {
            $request->session()->forget('platform_2fa_pending_user_id');
            $this->completeLogin($request, $user);
            return redirect()->route('platform.settings.2fa')
                ->with('warning', 'You logged in using a recovery code. That code has been used up — please generate new ones.');
        }

        return back()->withErrors(['one_time_password' => 'Invalid code. Please try again, or use one of your recovery codes.']);
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
            'user'        => $user,
            'secretKey'   => $secretKey,
            'qrCodeSvg'   => $qrCodeSvg,
            'totpEnabled' => (bool) $user->totp_enabled,
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

        $userId = session('crm_user_id');

        // Generate 8 one-time recovery codes
        $recoveryCodes = $this->generateRecoveryCodes($userId);

        DB::table('users')->where('id', $userId)->update([
            'totp_secret'  => Crypt::encryptString($secretKey),
            'totp_enabled' => true,
            'updated_at'   => now(),
        ]);

        $request->session()->forget('platform_2fa_setup_secret');

        // Flash recovery codes once — they will not be stored in plaintext after this
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

        $secret    = Crypt::decryptString($user->totp_secret);
        $google2fa = app('pragmarx.google2fa');

        if (!$google2fa->verifyKey($secret, $request->one_time_password)) {
            return back()->withErrors(['one_time_password' => 'Invalid code. 2FA was not disabled.']);
        }

        DB::table('users')->where('id', $userId)->update([
            'totp_secret'  => null,
            'totp_enabled' => false,
            'updated_at'   => now(),
        ]);

        // Delete all recovery codes
        DB::table('platform_recovery_codes')->where('user_id', $userId)->delete();

        return redirect()->route('platform.settings.2fa')
            ->with('success', '2FA has been disabled.');
    }

    // Recovery code helpers

    private function generateRecoveryCodes(int $userId): array
    {
        DB::table('platform_recovery_codes')->where('user_id', $userId)->delete();

        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
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
