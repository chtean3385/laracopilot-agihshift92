<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelEmailConfig;
use App\Models\Module;
use App\Models\OtaBookingConflict;
use App\Models\ParsedEmail;
use App\Services\EmailParser\EmailFetcherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailParserController extends Controller
{
    private function hotelId(): ?int
    {
        return (int) session('crm_sa_hotel_filter') ?: ((int) session('crm_hotel_id') ?: null);
    }

    private function ensureModule(): ?int
    {
        if (!session('crm_logged_in')) {
            abort(403, 'Authentication required.');
        }

        $role = session('crm_user_role');
        if (!in_array($role, ['Admin', 'Super Admin'], true)) {
            abort(403, 'Only the hotel administrator can manage the OTA Email Parser.');
        }

        $hotelId = $this->hotelId();
        if (!$hotelId) abort(403, 'No hotel context.');
        if (!Module::isEnabledForHotel('email-parser', $hotelId)) {
            abort(403, 'OTA Email Parser module is not enabled for this hotel.');
        }
        return $hotelId;
    }

    public function index()
    {
        $hotelId = $this->ensureModule();

        $config = HotelEmailConfig::where('hotel_id', $hotelId)->first();

        $latestEmails = ParsedEmail::where('hotel_id', $hotelId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $unresolvedConflicts = OtaBookingConflict::unresolvedCountForHotel($hotelId);

        return view('admin.email_parser.config', compact('config', 'latestEmails', 'unresolvedConflicts'));
    }

    public function saveConfig(Request $request)
    {
        $hotelId = $this->ensureModule();

        $existing = HotelEmailConfig::where('hotel_id', $hotelId)->first();

        $rules = [
            'email_address'   => 'required|email|max:200',
            'imap_host'       => 'required|string|max:200',
            'imap_port'       => 'required|integer|min:1|max:65535',
            'encryption'      => 'required|in:ssl,tls,none',
            'folder_to_watch' => 'nullable|string|max:100',
            'is_active'       => 'nullable|boolean',
        ];
        // Password required only on create; optional on update (keep existing).
        $rules['email_password'] = $existing ? 'nullable|string|max:200' : 'required|string|max:200';

        $data = $request->validate($rules);

        $payload = [
            'hotel_id'        => $hotelId,
            'email_address'   => $data['email_address'],
            'imap_host'       => $data['imap_host'],
            'imap_port'       => (int) $data['imap_port'],
            'encryption'      => $data['encryption'],
            'folder_to_watch' => $data['folder_to_watch'] ?: 'INBOX',
            'is_active'       => $request->boolean('is_active'),
        ];

        if (!empty($data['email_password'])) {
            $payload['email_password'] = $data['email_password']; // mutator encrypts
        }

        if ($existing) {
            $existing->fill($payload)->save();
        } else {
            HotelEmailConfig::create($payload);
        }

        return redirect()->route('email-parser.config')->with('success', 'Email configuration saved.');
    }

    public function testConnection(Request $request, EmailFetcherService $fetcher)
    {
        $hotelId = $this->ensureModule();

        // Test live form values (so the user can test before saving).
        $v = Validator::make($request->all(), [
            'email_address'   => 'required|email',
            'imap_host'       => 'required|string',
            'imap_port'       => 'required|integer',
            'encryption'      => 'required|in:ssl,tls,none',
            'folder_to_watch' => 'nullable|string',
            'email_password'  => 'nullable|string',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()]);
        }

        // Build a transient config object (don't persist).
        $config           = new HotelEmailConfig();
        $config->hotel_id = $hotelId;
        $config->email_address   = $request->input('email_address');
        $config->imap_host       = $request->input('imap_host');
        $config->imap_port       = (int) $request->input('imap_port');
        $config->encryption      = $request->input('encryption');
        $config->folder_to_watch = $request->input('folder_to_watch') ?: 'INBOX';

        $password = $request->input('email_password');
        if (!$password) {
            // Fall back to stored password if user didn't re-enter it.
            $stored = HotelEmailConfig::where('hotel_id', $hotelId)->first();
            $password = $stored?->getDecryptedPassword();
        }
        if (!$password) {
            return response()->json(['ok' => false, 'message' => 'Password is required to test the connection.']);
        }
        // Bypass mutator (encryption) by setting attribute then re-decrypting.
        $config->email_password = $password;

        try {
            $conn = $fetcher->connect($config);
            @imap_close($conn);
            return response()->json(['ok' => true, 'message' => 'Connected successfully to ' . $config->imap_host . '.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function toggleActive(Request $request)
    {
        $hotelId = $this->ensureModule();
        $config  = HotelEmailConfig::where('hotel_id', $hotelId)->first();
        if (!$config) {
            return back()->with('error', 'No config to toggle.');
        }
        $config->is_active = !$config->is_active;
        $config->save();
        return back()->with('success', $config->is_active ? 'Email sync resumed.' : 'Email sync paused.');
    }

    public function logs(Request $request)
    {
        $hotelId = $this->ensureModule();

        $status = $request->query('status');

        $rows = ParsedEmail::where('hotel_id', $hotelId)
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.email_parser.logs', compact('rows', 'status'));
    }

    public function conflicts()
    {
        $hotelId = $this->ensureModule();

        $items = OtaBookingConflict::with('booking.customer')
            ->where('hotel_id', $hotelId)
            ->where('resolved', false)
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.email_parser.conflicts', compact('items'));
    }

    public function resolveConflict(Request $request, $id)
    {
        $hotelId  = $this->ensureModule();
        $conflict = OtaBookingConflict::where('hotel_id', $hotelId)->findOrFail($id);

        $conflict->update([
            'resolved'    => true,
            'resolved_by' => (int) session('crm_user_id'),
            'resolved_at' => now(),
        ]);

        // Clear the booking's conflict flag too (it's been handled).
        if ($conflict->booking) {
            $conflict->booking->update(['ota_conflict' => false]);
        }

        return back()->with('success', 'Conflict marked resolved.');
    }
}
