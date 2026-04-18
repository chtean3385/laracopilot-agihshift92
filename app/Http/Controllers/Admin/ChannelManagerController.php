<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ChannelBooking;
use App\Models\ChannelManagerConfig;
use App\Models\ChannelRoomMapping;
use App\Models\Customer;
use App\Models\Module;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChannelManagerController extends Controller
{
    private function requireModule()
    {
        if (!Module::isEnabled('channel_manager')) {
            abort(403, 'OTA Channel Manager module is not enabled.');
        }
    }

    public function index()
    {
        $this->requireModule();
        $config       = ChannelManagerConfig::current();
        $thisMonth    = now()->startOfMonth();
        $totalBookings = ChannelBooking::whereMonth('created_at', now()->month)->count();
        $pending       = ChannelBooking::where('status', 'pending')->count();
        $otaRevenue    = ChannelBooking::whereIn('status', ['confirmed', 'converted'])->sum('net_amount');
        $avgCommission = ChannelBooking::whereIn('status', ['confirmed', 'converted'])->avg('commission_pct') ?? 0;
        $recent        = ChannelBooking::with('room')->latest()->take(5)->get();
        return view('admin.channel_manager.index', compact(
            'config', 'totalBookings', 'pending', 'otaRevenue', 'avgCommission', 'recent'
        ));
    }

    public function config()
    {
        $this->requireModule();
        $config = ChannelManagerConfig::current();
        return view('admin.channel_manager.config', compact('config'));
    }

    public function configSave(Request $request)
    {
        $this->requireModule();
        $isActive = $request->boolean('is_active');
        $data = $request->validate([
            'provider'    => 'required|in:ezee,staah,siteminder,rategain',
            'api_key'     => $isActive ? 'required|string|min:8' : 'nullable|string',
            'api_secret'  => 'nullable|string',
            'hotel_code'  => $isActive ? 'required|string|min:2' : 'nullable|string',
            'property_id' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ], [
            'api_key.required'   => 'API Key / Access Token is required to enable the channel manager.',
            'api_key.min'        => 'API Key must be at least 8 characters.',
            'hotel_code.required'=> 'Hotel Code is required to enable the channel manager.',
            'hotel_code.min'     => 'Hotel Code must be at least 2 characters.',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        ChannelManagerConfig::updateOrCreate(['id' => 1], $data);
        return back()->with('success', 'Channel manager configuration saved successfully.');
    }

    public function configTest(Request $request)
    {
        $this->requireModule();
        $config = ChannelManagerConfig::current();
        $result = $this->testConnection($config);
        return response()->json($result);
    }

    public function rooms()
    {
        $this->requireModule();
        $rooms    = Room::orderBy('room_number')->get();
        $mappings = ChannelRoomMapping::all()->keyBy('room_id');
        return view('admin.channel_manager.rooms', compact('rooms', 'mappings'));
    }

    public function roomsSave(Request $request)
    {
        $this->requireModule();
        $rows = $request->input('rooms', []);
        foreach ($rows as $roomId => $data) {
            if (empty($data['channel_room_code'])) {
                ChannelRoomMapping::where('room_id', $roomId)->delete();
                continue;
            }
            ChannelRoomMapping::updateOrCreate(
                ['room_id' => $roomId],
                [
                    'channel_room_code' => $data['channel_room_code'],
                    'channel_rate_plan' => $data['channel_rate_plan'] ?? null,
                    'extra_bed_rate'    => $data['extra_bed_rate'] ?? 0,
                ]
            );
        }
        return back()->with('success', 'Room mappings saved successfully.');
    }

    public function availability()
    {
        $this->requireModule();
        $config   = ChannelManagerConfig::current();
        $rooms    = Room::with(['channelMapping'])->orderBy('room_number')->get();
        $start    = now()->startOfDay();
        $end      = now()->addDays(29)->endOfDay();
        $bookings = Booking::whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_out_date', '>=', $start)
            ->where('check_in_date', '<=', $end)
            ->get(['room_id', 'check_in_date', 'check_out_date', 'is_whole_hotel']);
        $blocked = [];
        foreach ($bookings as $b) {
            if ($b->is_whole_hotel) continue;
            $d = \Carbon\Carbon::parse($b->check_in_date);
            while ($d->lessThan(\Carbon\Carbon::parse($b->check_out_date))) {
                $blocked[$b->room_id][$d->format('Y-m-d')] = true;
                $d->addDay();
            }
        }
        $dates = [];
        for ($i = 0; $i < 30; $i++) {
            $dates[] = now()->addDays($i)->format('Y-m-d');
        }

        $whBookings = Booking::where('is_whole_hotel', true)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where('check_in_date', '<', $end)
            ->where('check_out_date', '>', $start)
            ->with('customer:id,name')
            ->get();
        $whDates = [];
        foreach ($whBookings as $wh) {
            $d = \Carbon\Carbon::parse($wh->check_in_date)->startOfDay();
            $out = \Carbon\Carbon::parse($wh->check_out_date)->startOfDay();
            while ($d->lessThan($out)) {
                $ds = $d->format('Y-m-d');
                if (!isset($whDates[$ds])) {
                    $whDates[$ds] = [
                        'booking_number' => $wh->booking_number,
                        'guest_name'     => $wh->customer->name ?? 'Guest',
                    ];
                }
                $d->addDay();
            }
        }

        return view('admin.channel_manager.availability', compact('config', 'rooms', 'blocked', 'dates', 'whDates'));
    }

    public function availabilitySync(Request $request)
    {
        $this->requireModule();
        $config = ChannelManagerConfig::current();
        if (!$config->is_active || !$config->api_key) {
            return back()->with('error', 'Channel manager is not configured. Please save your credentials first.');
        }
        $result = $this->pushAvailability($config);
        if ($result['success']) {
            $config->last_synced_at = now();
            $config->save();
            return back()->with('success', 'Availability synced to ' . $config->providerLabel() . ' successfully.');
        }
        return back()->with('error', 'Sync failed: ' . ($result['message'] ?? 'Unknown error'));
    }

    public function bookings(Request $request)
    {
        $this->requireModule();
        $query = ChannelBooking::with('room')->latest();
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('check_in_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('check_in_date', '<=', $request->date_to);
        }
        $bookings = $query->paginate(20)->withQueryString();
        $rooms    = Room::orderBy('room_number')->get();
        return view('admin.channel_manager.bookings', compact('bookings', 'rooms'));
    }

    public function bookingStore(Request $request)
    {
        $this->requireModule();
        $data = $request->validate([
            'channel'        => 'required|string',
            'ota_booking_id' => 'required|string|unique:channel_bookings,ota_booking_id',
            'guest_name'     => 'required|string|max:255',
            'guest_phone'    => 'nullable|string|max:20',
            'guest_email'    => 'nullable|email|max:255',
            'room_id'        => 'nullable|exists:rooms,id',
            'check_in_date'  => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'rate_per_night' => 'required|numeric|min:0',
            'commission_pct' => 'nullable|numeric|min:0|max:100',
            'notes'          => 'nullable|string',
        ]);
        $nights             = \Carbon\Carbon::parse($data['check_in_date'])->diffInDays($data['check_out_date']);
        $data['nights']     = $nights;
        $data['total_amount'] = $nights * $data['rate_per_night'];
        $data['commission_pct'] = $data['commission_pct'] ?? 0;
        $data['net_amount'] = $data['total_amount'] * (1 - $data['commission_pct'] / 100);
        $data['status']     = 'pending';
        ChannelBooking::create($data);
        return back()->with('success', 'OTA booking imported successfully.');
    }

    public function bookingConvert(int $id)
    {
        $this->requireModule();
        $cb = ChannelBooking::findOrFail($id);
        if ($cb->status === 'converted') {
            return back()->with('error', 'This booking has already been converted.');
        }
        DB::transaction(function () use ($cb) {
            $customer = Customer::firstOrCreate(
                ['phone' => $cb->guest_phone ?? 'OTA-' . $cb->id],
                [
                    'name'  => $cb->guest_name,
                    'email' => $cb->guest_email ?? null,
                    'phone' => $cb->guest_phone ?? 'OTA-' . $cb->id,
                ]
            );
            $nights = $cb->nights ?: \Carbon\Carbon::parse($cb->check_in_date)->diffInDays($cb->check_out_date);
            $bookingNum = 'OTA-' . strtoupper(substr(md5($cb->ota_booking_id), 0, 6));
            $booking = Booking::create([
                'booking_number'  => $bookingNum,
                'customer_id'     => $customer->id,
                'room_id'         => $cb->room_id,
                'check_in_date'   => $cb->check_in_date,
                'check_out_date'  => $cb->check_out_date,
                'nights'          => $nights,
                'adults'          => 1,
                'children'        => 0,
                'status'          => 'confirmed',
                'payment_status'  => 'unpaid',
                'total_amount'    => $cb->total_amount,
                'advance_payment' => 0,
                'balance_due'     => $cb->total_amount,
                'special_requests' => 'OTA: ' . $cb->channelLabel() . ' | Ref: ' . $cb->ota_booking_id,
            ]);
            $cb->update(['status' => 'converted', 'converted_booking_id' => $booking->id]);
        });
        $converted = ChannelBooking::find($id);
        return redirect()->route('bookings.show', $converted->converted_booking_id)
            ->with('success', 'OTA booking converted to CRM booking successfully.');
    }

    public function bookingCancel(int $id)
    {
        $this->requireModule();
        ChannelBooking::findOrFail($id)->update(['status' => 'cancelled']);
        return back()->with('success', 'OTA booking marked as cancelled.');
    }

    private function testConnection(ChannelManagerConfig $config): array
    {
        if (!$config->api_key) {
            return ['success' => false, 'message' => 'No API key configured.'];
        }
        try {
            return match ($config->provider) {
                'ezee'  => $this->testEzee($config),
                'staah' => $this->testStaah($config),
                default => ['success' => true, 'message' => 'Credentials saved. Live connection test is not available for ' . $config->providerLabel() . ' — please verify in your provider dashboard.'],
            };
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function testEzee(ChannelManagerConfig $config): array
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<GetHotelInfoRequest>
  <Authentication>
    <HotelCode>' . htmlspecialchars($config->hotel_code) . '</HotelCode>
    <AuthCode>' . htmlspecialchars($config->api_key) . '</AuthCode>
  </Authentication>
</GetHotelInfoRequest>';
        $response = Http::withHeaders(['Content-Type' => 'application/xml'])
            ->withBody($xml, 'application/xml')
            ->post('https://live.ezeetechnosys.com/eZeeChannelManager/StaticDataAPI.ashx');
        if ($response->successful() && str_contains($response->body(), 'HotelName')) {
            return ['success' => true, 'message' => 'Connected to eZee Centrix successfully.'];
        }
        return ['success' => false, 'message' => 'eZee connection failed. Check hotel code and API key.'];
    }

    private function testStaah(ChannelManagerConfig $config): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config->api_key,
            'Content-Type'  => 'application/json',
        ])->get('https://api.staah.net/v1/property/' . $config->property_id);
        if ($response->successful()) {
            return ['success' => true, 'message' => 'Connected to STAAH successfully.'];
        }
        return ['success' => false, 'message' => 'STAAH connection failed (HTTP ' . $response->status() . '). Check credentials.'];
    }

    private function pushAvailability(ChannelManagerConfig $config): array
    {
        return match ($config->provider) {
            'ezee'       => $this->pushEzeeAvailability($config),
            'staah'      => $this->pushStaahAvailability($config),
            default      => ['success' => true, 'message' => 'Manual sync required in ' . $config->providerLabel() . ' dashboard.'],
        };
    }

    private function pushEzeeAvailability(ChannelManagerConfig $config): array
    {
        $mappings = ChannelRoomMapping::with('room')->get();
        if ($mappings->isEmpty()) {
            return ['success' => false, 'message' => 'No rooms mapped. Please map rooms first.'];
        }
        $start  = now()->format('Y-m-d');
        $end    = now()->addDays(29)->format('Y-m-d');
        $blocks = Booking::whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_out_date', '>=', $start)->where('check_in_date', '<=', $end)
            ->get(['room_id', 'check_in_date', 'check_out_date']);
        $blocked = [];
        foreach ($blocks as $b) {
            $d = \Carbon\Carbon::parse($b->check_in_date);
            while ($d->lessThan(\Carbon\Carbon::parse($b->check_out_date))) {
                $blocked[$b->room_id][$d->format('Y-m-d')] = true;
                $d->addDay();
            }
        }
        $roomXml = '';
        foreach ($mappings as $m) {
            $avail = $blocked[$m->room_id] ?? [];
            $roomXml .= '<RoomType RoomTypeCode="' . htmlspecialchars($m->channel_room_code) . '">';
            $d = \Carbon\Carbon::parse($start);
            while ($d->format('Y-m-d') <= $end) {
                $avl = isset($avail[$d->format('Y-m-d')]) ? '0' : '1';
                $roomXml .= '<Inventory Date="' . $d->format('Y-m-d') . '" Availability="' . $avl . '"/>';
                $d->addDay();
            }
            $roomXml .= '</RoomType>';
        }
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<UpdateInventoryRequest>
  <Authentication>
    <HotelCode>' . htmlspecialchars($config->hotel_code) . '</HotelCode>
    <AuthCode>' . htmlspecialchars($config->api_key) . '</AuthCode>
  </Authentication>
  <RoomTypes>' . $roomXml . '</RoomTypes>
</UpdateInventoryRequest>';
        try {
            $response = Http::withHeaders(['Content-Type' => 'application/xml'])
                ->withBody($xml, 'application/xml')
                ->post('https://live.ezeetechnosys.com/eZeeChannelManager/InventoryUpdateAPI.ashx');
            if ($response->successful()) {
                return ['success' => true, 'message' => 'Availability pushed to eZee.'];
            }
            return ['success' => false, 'message' => 'eZee API error: ' . $response->body()];
        } catch (\Throwable $e) {
            Log::error('eZee sync error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function pushStaahAvailability(ChannelManagerConfig $config): array
    {
        $mappings = ChannelRoomMapping::with('room')->get();
        if ($mappings->isEmpty()) {
            return ['success' => false, 'message' => 'No rooms mapped. Please map rooms first.'];
        }
        $start  = now()->format('Y-m-d');
        $end    = now()->addDays(29)->format('Y-m-d');
        $blocks = Booking::whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_out_date', '>=', $start)->where('check_in_date', '<=', $end)
            ->get(['room_id', 'check_in_date', 'check_out_date']);
        $blocked = [];
        foreach ($blocks as $b) {
            $d = \Carbon\Carbon::parse($b->check_in_date);
            while ($d->lessThan(\Carbon\Carbon::parse($b->check_out_date))) {
                $blocked[$b->room_id][$d->format('Y-m-d')] = true;
                $d->addDay();
            }
        }
        $rooms = [];
        foreach ($mappings as $m) {
            $avail = $blocked[$m->room_id] ?? [];
            $dates = [];
            $d = \Carbon\Carbon::parse($start);
            while ($d->format('Y-m-d') <= $end) {
                $dates[] = ['date' => $d->format('Y-m-d'), 'availability' => isset($avail[$d->format('Y-m-d')]) ? 0 : 1];
                $d->addDay();
            }
            $rooms[] = ['room_type_code' => $m->channel_room_code, 'dates' => $dates];
        }
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config->api_key,
                'Content-Type'  => 'application/json',
            ])->post('https://api.staah.net/v1/property/' . $config->property_id . '/availability', [
                'rooms' => $rooms,
            ]);
            if ($response->successful()) {
                return ['success' => true, 'message' => 'Availability pushed to STAAH.'];
            }
            return ['success' => false, 'message' => 'STAAH API error (HTTP ' . $response->status() . '): ' . $response->body()];
        } catch (\Throwable $e) {
            Log::error('STAAH sync error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
