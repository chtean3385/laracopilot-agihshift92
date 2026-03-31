<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleController extends Controller
{
    public function index()
    {
        $isSuperAdmin = session('crm_user_role') === 'Super Admin';
        $hasSaFilter  = session('crm_sa_hotel_filter');

        if ($isSuperAdmin && !$hasSaFilter) {
            $modules = Module::withoutGlobalScopes()
                ->orderBy('hotel_id')
                ->orderBy('id')
                ->get();

            $hotels = DB::table('hotels')->get()->keyBy('id');

            $groupedByHotel = $modules->groupBy('hotel_id');
            $showAllHotels  = true;
        } else {
            $modules        = Module::orderBy('id')->get();
            $groupedByHotel = null;
            $showAllHotels  = false;
            $hotels         = collect();
        }

        return view('admin.settings.modules', compact('modules', 'groupedByHotel', 'showAllHotels', 'hotels'));
    }

    public function toggle(Module $module)
    {
        $module->update(['is_enabled' => !$module->is_enabled]);
        $status = $module->is_enabled ? 'enabled' : 'disabled';
        return back()->with('success', "{$module->name} module {$status}.");
    }
}
