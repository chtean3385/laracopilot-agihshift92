<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    public function index()
    {
        $plans = DB::table('platform_plans')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('platform.plans.index', compact('plans'));
    }

    public function edit(int $id)
    {
        $plan = DB::table('platform_plans')->where('id', $id)->first();

        if (!$plan) {
            return redirect()->route('platform.plans.index')->with('error', 'Plan not found.');
        }

        $plan->features = is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []);

        return view('platform.plans.edit', compact('plan'));
    }

    public function update(Request $request, int $id)
    {
        $plan = DB::table('platform_plans')->where('id', $id)->first();

        if (!$plan) {
            return redirect()->route('platform.plans.index')->with('error', 'Plan not found.');
        }

        $data = $request->validate([
            'label'         => 'required|string|max:100',
            'color'         => 'required|string|max:20',
            'monthly_price' => 'required|integer|min:0',
            'yearly_price'  => 'required|integer|min:0',
            'max_rooms'     => 'required|integer|min:1',
            'max_users'     => 'required|integer|min:1',
            'is_active'     => 'nullable|boolean',
            'features'      => 'nullable|string',
            'sort_order'    => 'nullable|integer|min:0',
        ]);

        $featuresRaw = $request->input('features', '');
        $featuresArr = array_values(array_filter(array_map('trim', explode("\n", $featuresRaw))));

        DB::table('platform_plans')->where('id', $id)->update([
            'label'         => $data['label'],
            'color'         => $data['color'],
            'monthly_price' => (int) $data['monthly_price'],
            'yearly_price'  => (int) $data['yearly_price'],
            'max_rooms'     => (int) $data['max_rooms'],
            'max_users'     => (int) $data['max_users'],
            'features'      => json_encode($featuresArr),
            'is_active'     => $request->boolean('is_active') ? 1 : 0,
            'sort_order'    => (int) ($data['sort_order'] ?? 0),
            'updated_at'    => now(),
        ]);

        return redirect()->route('platform.plans.index')
            ->with('success', "Plan \"{$data['label']}\" updated successfully.");
    }
}
