<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class AuthController extends Controller
{
    // ── Show Login ────────────────────────────────────────────────────────────

    public function showLogin()
    {
        if (session('crm_logged_in') && session('crm_user_role') === 'Super Admin') {
            return redirect()->route('platform.dashboard');
        }

        return view('platform.auth.login');
    }

    // ── Login (step 1: password check) ────────────────────────────────────────

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

        // If 2FA is enabled, go to verification step (do NOT create full session yet)
        if ($user->totp_enabled) {
            $request->session()->put('platform_2fa_pending_user_id', $user->id);
            return redirect()->route('platform.login.2fa');
        }

        // No 2FA — complete login immediately
        $this->completeLogin($request, $user);
        return redirect()->route('platform.dashboard');
    }

    // ── 2FA Verify Page ───────────────────────────────────────────────────────

    public function show2faVerify(Request $request)
    {
        if (!$request->session()->has('platform_2fa_pending_user_id')) {
            return redirect()->route('platform.login');
        }

        return view('platform.auth.2fa-verify');
    }

    // ── 2FA Verify POST ───────────────────────────────────────────────────────

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
            return redirect()->route('platform.login')->withErrors(['email' => 'Invalid 2FA state. Please log in again.']);
        }

        $secret = Crypt::decryptString($user->totp_secret);
        $google2fa = app('pragmarx.google2fa');
        $valid = $google2fa->verifyKey($secret, $request->one_time_password);

        if (!$valid) {
            return back()->withErrors(['one_time_password' => 'Invalid code. Please try again.']);
        }

        $request->session()->forget('platform_2fa_pending_user_id');
        $this->completeLogin($request, $user);
        return redirect()->route('platform.dashboard');
    }

    // ── Complete Login (shared) ───────────────────────────────────────────────

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

    // ── 2FA Setup Page ────────────────────────────────────────────────────────

    public function show2faSetup(Request $request)
    {
        $userId = session('crm_user_id');
        $user   = DB::table('users')->where('id', $userId)->first();

        $google2fa  = app('pragmarx.google2fa');
        $secretKey  = $request->session()->get('platform_2fa_setup_secret');

        // Generate a fresh secret if none in session
        if (!$secretKey) {
            $secretKey = $google2fa->generateSecretKey();
            $request->session()->put('platform_2fa_setup_secret', $secretKey);
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name', 'Hotel CRM'),
            $user->email,
            $secretKey
        );

        // Generate inline QR code as base64 SVG via BaconQrCode
        $qrCodeSvg = $this->generateQrCodeSvg($qrCodeUrl);

        return view('platform.settings.2fa-setup', [
            'user'       => $user,
            'secretKey'  => $secretKey,
            'qrCodeSvg'  => $qrCodeSvg,
            'totpEnabled'=> (bool) $user->totp_enabled,
        ]);
    }

    // ── 2FA Enable (confirm setup with code) ──────────────────────────────────

    public function enable2fa(Request $request)
    {
        $request->validate(['one_time_password' => 'required|string']);

        $secretKey = $request->session()->get('platform_2fa_setup_secret');
        if (!$secretKey) {
            return back()->withErrors(['one_time_password' => 'Session expired. Please refresh and try again.']);
        }

        $google2fa = app('pragmarx.google2fa');
        $valid = $google2fa->verifyKey($secretKey, $request->one_time_password);

        if (!$valid) {
            return back()->withErrors(['one_time_password' => 'Invalid code. Make sure your authenticator app is synced and try again.']);
        }

        $userId = session('crm_user_id');
        DB::table('users')->where('id', $userId)->update([
            'totp_secret'  => Crypt::encryptString($secretKey),
            'totp_enabled' => true,
            'updated_at'   => now(),
        ]);

        $request->session()->forget('platform_2fa_setup_secret');

        return redirect()->route('platform.settings.2fa')
            ->with('success', '2FA has been enabled. Your account is now protected with two-factor authentication.');
    }

    // ── 2FA Disable ───────────────────────────────────────────────────────────

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
        $valid     = $google2fa->verifyKey($secret, $request->one_time_password);

        if (!$valid) {
            return back()->withErrors(['one_time_password' => 'Invalid code. 2FA was not disabled.']);
        }

        DB::table('users')->where('id', $userId)->update([
            'totp_secret'  => null,
            'totp_enabled' => false,
            'updated_at'   => now(),
        ]);

        return redirect()->route('platform.settings.2fa')
            ->with('success', '2FA has been disabled.');
    }

    // ── QR Code Helper ────────────────────────────────────────────────────────

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
