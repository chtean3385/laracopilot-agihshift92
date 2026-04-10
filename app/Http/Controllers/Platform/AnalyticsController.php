<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformWhatsAppSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AnalyticsController extends Controller
{
    public function index()
    {
        return view('platform.analytics.index');
    }

    public function campaigns()
    {
        $hotels = DB::table('hotels')->orderBy('name')->get(['id', 'name', 'plan', 'status', 'email', 'phone']);

        $sentCampaigns = DB::table('platform_campaigns')
            ->orderByDesc('sent_at')
            ->limit(30)
            ->get();

        return view('platform.analytics.campaigns', compact('hotels', 'sentCampaigns'));
    }

    public function sendCampaign(Request $request)
    {
        $channel = $request->input('channel', 'email');
        $needsWa = in_array($channel, ['whatsapp', 'both']);

        $data = $request->validate([
            'template_key'   => 'required|string',
            'subject'        => 'required_unless:channel,whatsapp|nullable|string|max:200',
            'body'           => 'required_unless:channel,whatsapp|nullable|string',
            'channel'        => 'required|in:email,whatsapp,both',
            'wa_template_key'=> $needsWa ? 'required|string|in:crm_update,login_reminder' : 'nullable|string',
            'hotel_ids'      => 'nullable|array',
            'hotel_ids.*'    => 'integer',
        ]);

        $hotelIds         = $data['hotel_ids'] ?? null;
        $platformTemplates = HotelController::platformWaTemplates();

        $query = DB::table('hotels')->where('status', 'active');
        if (!empty($hotelIds)) {
            $query->whereIn('id', $hotelIds);
        }
        $hotels = $query->get(['id', 'name', 'email', 'phone']);

        $sentCount = 0;
        $platform  = PlatformWhatsAppSetting::instance();

        foreach ($hotels as $hotel) {
            // Email channel
            if (in_array($data['channel'], ['email', 'both']) && $hotel->email && !empty($data['body'])) {
                try {
                    Mail::raw($data['body'], function ($msg) use ($hotel, $data) {
                        $msg->to($hotel->email)
                            ->subject($data['subject'] ?? 'Message from Dreams Technology');
                    });
                    $sentCount++;
                } catch (\Throwable $e) {
                    Log::warning("Campaign email failed for hotel {$hotel->id}: " . $e->getMessage());
                }
            }

            // WhatsApp channel — uses approved Meta templates
            if (in_array($data['channel'], ['whatsapp', 'both']) && $hotel->phone
                && $platform?->saas_token && $platform?->saas_phone_number_id
                && !empty($data['wa_template_key'])) {
                try {
                    $phone = preg_replace('/[^0-9]/', '', $hotel->phone);
                    if (!str_starts_with($phone, '91') && strlen($phone) === 10) {
                        $phone = '91' . $phone;
                    }
                    $tpl = $platformTemplates[$data['wa_template_key']];
                    Http::timeout(15)->withToken($platform->saas_token)
                        ->post("https://graph.facebook.com/v19.0/{$platform->saas_phone_number_id}/messages", [
                            'messaging_product' => 'whatsapp',
                            'to'                => $phone,
                            'type'              => 'template',
                            'template'          => [
                                'name'       => $tpl['meta_name'],
                                'language'   => ['code' => $tpl['language']],
                                'components' => [[
                                    'type'       => 'body',
                                    'parameters' => [
                                        ['type' => 'text', 'text' => $hotel->name],
                                        ['type' => 'text', 'text' => config('app.url') . '/login'],
                                    ],
                                ]],
                            ],
                        ]);
                    $sentCount++;
                } catch (\Throwable $e) {
                    Log::warning("Campaign WA failed for hotel {$hotel->id}: " . $e->getMessage());
                }
            }
        }

        $waKey        = $data['wa_template_key'] ?? null;
        $waTplLabel   = $waKey ? ($platformTemplates[$waKey]['label'] ?? $waKey) : null;
        $bodyToStore  = $data['body'] ?? ($waTplLabel ? "[WA Template: {$waTplLabel}]" : '');
        $templateKey  = $data['template_key'] ?? ($waKey ?? 'platform_outreach');

        DB::table('platform_campaigns')->insert([
            'hotel_ids'      => json_encode($hotelIds ?? $hotels->pluck('id')->toArray()),
            'channel'        => $data['channel'],
            'template_key'   => $templateKey,
            'subject'        => $data['subject'] ?? ($waTplLabel ?? ''),
            'body'           => $bodyToStore,
            'sent_count'     => $sentCount,
            'delivered_count'=> $sentCount,
            'sent_by'        => session('platform_admin_email') ?? 'SaaS Admin',
            'sent_at'        => now(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('platform.analytics.campaigns')
            ->with('success', "Campaign sent to {$sentCount} hotel(s) successfully.");
    }
}
