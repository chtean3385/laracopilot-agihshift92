<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\PathikConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PathikController extends Controller
{
    private function requireModule()
    {
        if (!Module::isEnabled('pathik')) {
            abort(403, 'Pathik module is not enabled.');
        }
    }

    public function index()
    {
        $this->requireModule();
        $config   = PathikConfig::current();
        $masked   = substr($config->api_token, 0, 6) . str_repeat('*', max(0, strlen($config->api_token) - 6));
        $fullToken = $config->api_token;
        return view('admin.pathik.index', compact('config', 'masked', 'fullToken'));
    }

    public function regenerateToken()
    {
        $this->requireModule();
        $config = PathikConfig::current();
        $config->update(['api_token' => Str::random(32)]);
        return back()->with('success', 'API token regenerated. Update your Chrome extension with the new token.');
    }

    public function pendingStore(Request $request)
    {
        $this->requireModule();
        $request->validate([
            'booking_id' => 'required|integer',
            'name'       => 'required|string',
            'phone'      => 'required|string',
        ]);

        $token = Str::random(24);
        $data  = $request->only([
            'booking_id', 'booking_number', 'name', 'email', 'phone',
            'address', 'city', 'state', 'country', 'nationality',
            'id_type', 'id_number', 'date_of_birth',
            'check_in_date', 'check_out_date', 'nights',
            'adults', 'children', 'room_number', 'room_type',
            'total_amount',
        ]);
        $data['_stored_at'] = now()->toISOString();

        Cache::put('pathik_pending_' . $token, $data, now()->addMinutes(60));

        return response()->json(['ok' => true, 'token' => $token]);
    }

    public function pendingFetch(Request $request)
    {
        $request->validate([
            'token'     => 'required|string',
            'api_token' => 'required|string',
        ]);

        $config = PathikConfig::current();
        if ($request->api_token !== $config->api_token) {
            return response()->json(['error' => 'Invalid API token.'], 401);
        }

        $data = Cache::get('pathik_pending_' . $request->token);
        if (!$data) {
            return response()->json(['error' => 'No pending data found or token expired.'], 404);
        }

        return response()->json(['ok' => true, 'guest' => $data]);
    }

    public function clearPending(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        Cache::forget('pathik_pending_' . $request->token);
        return response()->json(['ok' => true]);
    }
}
