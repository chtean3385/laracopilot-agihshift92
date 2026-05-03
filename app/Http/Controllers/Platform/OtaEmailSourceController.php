<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\OtaEmailSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OtaEmailSourceController extends Controller
{
    public function index()
    {
        $sources = OtaEmailSource::with('hotel')->orderBy('id')->get();
        $hotels  = DB::table('hotels')->orderBy('name')->get(['id', 'name', 'status']);

        return view('platform.ota-email-sources.index', compact('sources', 'hotels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hotel_id'      => 'required|integer|exists:hotels,id',
            'inbound_email' => 'required|email|max:255|unique:ota_email_sources,inbound_email',
            'notes'         => 'nullable|string|max:500',
            'is_active'     => 'nullable',
        ]);

        $data['is_active'] = !empty($data['is_active']);

        // Only one email source per hotel
        if (OtaEmailSource::where('hotel_id', $data['hotel_id'])->exists()) {
            return redirect()->route('platform.ota-email-sources.index')
                ->with('error', 'This hotel already has an inbound email configured. Edit or delete the existing entry first.');
        }

        OtaEmailSource::create($data);

        return redirect()->route('platform.ota-email-sources.index')
            ->with('success', 'Inbound email source added.');
    }

    public function update(Request $request, OtaEmailSource $otaEmailSource)
    {
        $data = $request->validate([
            'inbound_email' => 'required|email|max:255|unique:ota_email_sources,inbound_email,' . $otaEmailSource->id,
            'notes'         => 'nullable|string|max:500',
            'is_active'     => 'nullable',
        ]);

        $data['is_active'] = !empty($data['is_active']);

        $otaEmailSource->update($data);

        return redirect()->route('platform.ota-email-sources.index')
            ->with('success', 'Inbound email source updated.');
    }

    public function destroy(OtaEmailSource $otaEmailSource)
    {
        $otaEmailSource->delete();

        return redirect()->route('platform.ota-email-sources.index')
            ->with('success', 'Inbound email source removed.');
    }

    public function toggle(OtaEmailSource $otaEmailSource)
    {
        $otaEmailSource->update(['is_active' => !$otaEmailSource->is_active]);

        return response()->json([
            'success' => true,
            'active'  => $otaEmailSource->is_active,
            'message' => $otaEmailSource->hotel->name . ' email source ' . ($otaEmailSource->is_active ? 'enabled' : 'disabled') . '.',
        ]);
    }
}
