<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformFirebaseSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FcmTokenController extends Controller
{
    /**
     * Register or refresh an FCM token for the authenticated hotel user.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'token'     => 'required|string',
            'platform'  => 'nullable|in:web,android,ios',
            'device_id' => 'nullable|string|max:200',
        ]);

        // Hotel CRM uses custom session keys (not standard Laravel Auth guard)
        $userId  = $request->session()->get('crm_user_id');
        $hotelId = $request->session()->get('crm_hotel_id');

        // Fallback: find hotel from hotel_users if session missing
        if (!$hotelId && $userId) {
            $hotelId = DB::table('hotel_users')->where('user_id', $userId)->value('hotel_id');
        }

        if (!$userId) {
            return response()->json(['ok' => false, 'error' => 'Not authenticated'], 401);
        }

        // Upsert by user + device_id or token
        $existing = DB::table('fcm_tokens')
            ->where('user_id', $userId)
            ->where(function ($q) use ($data) {
                $q->where('token', $data['token'])
                  ->orWhere(function ($q2) use ($data) {
                      if (!empty($data['device_id'])) {
                          $q2->where('device_id', $data['device_id']);
                      }
                  });
            })
            ->first();

        if ($existing) {
            DB::table('fcm_tokens')->where('id', $existing->id)->update([
                'token'        => $data['token'],
                'hotel_id'     => $hotelId,
                'platform'     => $data['platform'] ?? 'web',
                'device_id'    => $data['device_id'] ?? null,
                'last_seen_at' => now(),
                'updated_at'   => now(),
            ]);
        } else {
            DB::table('fcm_tokens')->insert([
                'user_id'      => $userId,
                'hotel_id'     => $hotelId,
                'token'        => $data['token'],
                'platform'     => $data['platform'] ?? 'web',
                'device_id'    => $data['device_id'] ?? null,
                'last_seen_at' => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Remove FCM token on logout / permission revoked.
     */
    public function destroy(Request $request)
    {
        $userId = $request->session()->get('crm_user_id');
        $token  = $request->input('token');
        if ($token && $userId) {
            DB::table('fcm_tokens')
                ->where('user_id', $userId)
                ->where('token', $token)
                ->delete();
        }
        return response()->json(['ok' => true]);
    }

    /**
     * Poll for unread in-app notifications (no FCM dependency).
     */
    public function unread(Request $request)
    {
        $userId  = $request->session()->get('crm_user_id');
        $hotelId = $request->session()->get('crm_hotel_id');

        $query = DB::table('platform_notification_deliveries as d')
            ->join('platform_notifications as n', 'n.id', '=', 'd.notification_id')
            ->where('d.user_id', $userId)
            ->where('d.is_read', false)
            ->select('d.id as delivery_id', 'n.id', 'n.title', 'n.body', 'n.action_url', 'n.sent_at')
            ->orderByDesc('n.sent_at')
            ->limit(10);

        if ($hotelId) {
            $query->where('d.hotel_id', $hotelId);
        }

        return response()->json($query->get());
    }

    /**
     * Mark a notification delivery as read.
     */
    public function markRead(Request $request, int $deliveryId)
    {
        $userId = $request->session()->get('crm_user_id');
        DB::table('platform_notification_deliveries')
            ->where('id', $deliveryId)
            ->where('user_id', $userId)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * Firebase config for JS (public values only).
     */
    public function firebaseConfig()
    {
        $cfg = PlatformFirebaseSetting::instance();

        if (!$cfg->push_enabled || !$cfg->firebase_api_key) {
            return response()->json(['enabled' => false]);
        }

        return response()->json([
            'enabled'          => true,
            'apiKey'           => $cfg->firebase_api_key,
            'projectId'        => $cfg->firebase_project_id,
            'messagingSenderId'=> $cfg->firebase_messaging_sender_id,
            'appId'            => $cfg->firebase_app_id,
            'vapidKey'         => $cfg->firebase_vapid_key,
        ]);
    }
}
