<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformFirebaseSetting;
use App\Models\PlatformNotification;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushNotificationsController extends Controller
{
    public function settings()
    {
        $config = PlatformFirebaseSetting::instance();
        return view('platform.notifications.settings', compact('config'));
    }

    public function settingsSave(Request $request)
    {
        $data = $request->validate([
            'firebase_project_id'          => 'nullable|string|max:200',
            'firebase_api_key'             => 'nullable|string|max:500',
            'firebase_messaging_sender_id' => 'nullable|string|max:200',
            'firebase_app_id'              => 'nullable|string|max:300',
            'firebase_vapid_key'           => 'nullable|string|max:500',
            'fcm_server_key'               => 'nullable|string|max:500',
            'push_enabled'                 => 'nullable|boolean',
        ]);

        $config = PlatformFirebaseSetting::firstOrNew(['id' => 1]);
        $config->fill([
            'firebase_project_id'          => $data['firebase_project_id'] ?? null,
            'firebase_api_key'             => $data['firebase_api_key'] ?? null,
            'firebase_messaging_sender_id' => $data['firebase_messaging_sender_id'] ?? null,
            'firebase_app_id'              => $data['firebase_app_id'] ?? null,
            'firebase_vapid_key'           => $data['firebase_vapid_key'] ?? null,
            'fcm_server_key'               => $data['fcm_server_key'] ?? null,
            'push_enabled'                 => isset($data['push_enabled']),
        ]);
        $config->save();

        return redirect()->route('platform.notifications.settings')
            ->with('success', 'Firebase settings saved.');
    }

    public function send()
    {
        $hotels = DB::table('hotels')->orderBy('name')->get(['id', 'name', 'plan', 'status']);
        $plans  = DB::table('hotels')->distinct()->pluck('plan')->sort()->values();
        return view('platform.notifications.send', compact('hotels', 'plans'));
    }

    public function sendPost(Request $request)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:200',
            'body'       => 'required|string|max:1000',
            'action_url' => 'nullable|url',
            'target'     => 'required|in:all,hotel,plan',
            'target_ids' => 'nullable|array',
        ]);

        $fcm = app(FcmService::class);

        if (!$fcm->isEnabled()) {
            return back()->withErrors(['push' => 'Firebase is not enabled or server key is missing.'])->withInput();
        }

        $tokens    = [];
        $targetIds = $data['target_ids'] ?? null;

        if ($data['target'] === 'all') {
            $tokens = $fcm->getAllTokens();
        } elseif ($data['target'] === 'hotel' && !empty($targetIds)) {
            foreach ($targetIds as $hotelId) {
                $tokens = array_merge($tokens, $fcm->getTokensForHotel((int) $hotelId));
            }
        } elseif ($data['target'] === 'plan' && !empty($targetIds)) {
            foreach ($targetIds as $plan) {
                $tokens = array_merge($tokens, $fcm->getTokensForPlan($plan));
            }
        }

        $tokens = array_unique(array_filter($tokens));

        $notif = PlatformNotification::create([
            'title'      => $data['title'],
            'body'       => $data['body'],
            'action_url' => $data['action_url'] ?? null,
            'target'     => $data['target'],
            'target_ids' => $targetIds,
            'sent_by'    => session('platform_admin_email') ?? 'SaaS Admin',
            'sent_at'    => now(),
        ]);

        $result = $fcm->sendToTokens($tokens, $data['title'], $data['body'], [
            'url'      => $data['action_url'] ?? '/',
            'notif_id' => $notif->id,
        ]);

        $notif->update([
            'sent_count'      => count($tokens),
            'delivered_count' => $result['success'],
        ]);

        // Save delivery records for in-app bell
        if (!empty($tokens)) {
            $userRows = DB::table('fcm_tokens')
                ->whereIn('token', $tokens)
                ->select('user_id', 'hotel_id')
                ->distinct()
                ->get();

            $rows = $userRows->map(fn($u) => [
                'notification_id' => $notif->id,
                'user_id'         => $u->user_id,
                'hotel_id'        => $u->hotel_id,
                'delivered_at'    => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ])->toArray();

            if (!empty($rows)) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('platform_notification_deliveries')->insert($chunk);
                }
            }
        }

        return redirect()->route('platform.notifications.history')
            ->with('success', "Notification sent to {$result['success']} device(s).");
    }

    public function history()
    {
        $notifications = PlatformNotification::orderByDesc('sent_at')->paginate(20);
        return view('platform.notifications.history', compact('notifications'));
    }
}
