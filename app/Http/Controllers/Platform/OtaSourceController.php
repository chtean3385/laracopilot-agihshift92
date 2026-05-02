<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\OtaSource;
use Illuminate\Http\Request;

class OtaSourceController extends Controller
{
    public function index()
    {
        $sources = OtaSource::orderBy('name')->get();
        return view('platform.ota-sources.index', compact('sources'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:100',
            'sender_number'       => 'nullable|string|max:30',
            'waba_id'             => 'nullable|string|max:100',
            'message_pattern_key' => 'required|string|max:50',
            'notes'               => 'nullable|string|max:500',
            'is_active'           => 'nullable',
        ]);

        $data['is_active'] = !empty($data['is_active']);

        OtaSource::create($data);

        return redirect()->route('platform.ota-sources.index')->with('success', 'OTA source added.');
    }

    public function update(Request $request, OtaSource $otaSource)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:100',
            'sender_number'       => 'nullable|string|max:30',
            'waba_id'             => 'nullable|string|max:100',
            'message_pattern_key' => 'required|string|max:50',
            'notes'               => 'nullable|string|max:500',
            'is_active'           => 'nullable',
        ]);

        $data['is_active'] = !empty($data['is_active']);

        $otaSource->update($data);

        return redirect()->route('platform.ota-sources.index')->with('success', 'OTA source updated.');
    }

    public function destroy(OtaSource $otaSource)
    {
        $otaSource->delete();
        return redirect()->route('platform.ota-sources.index')->with('success', 'OTA source deleted.');
    }

    public function toggle(OtaSource $otaSource)
    {
        $otaSource->update(['is_active' => !$otaSource->is_active]);
        return response()->json([
            'success' => true,
            'active'  => $otaSource->is_active,
            'message' => $otaSource->name . ' ' . ($otaSource->is_active ? 'enabled' : 'disabled') . '.',
        ]);
    }
}
