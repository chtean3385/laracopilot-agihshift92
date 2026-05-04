<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleController extends Controller
{
    // Maps module slug → permission module label in permissions table.
    // When a module is enabled, all permissions in that group are granted to Admin.
    private const MODULE_PERMISSION_MAP = [
        'restaurant'      => 'Restaurant',
        'whatsapp'        => 'WhatsApp',
        'inventory'       => 'Inventory',
        'slot-search'     => 'Slot Search',
        'payment_links'   => 'Payment Links',
        'email-parser'    => 'OTA Email Parser',
    ];

    public function index()
    {
        $isSuperAdmin = session('crm_user_role') === 'Super Admin';
        $hasSaFilter  = session('crm_sa_hotel_filter');

        if ($isSuperAdmin && !$hasSaFilter) {
            $modules = Module::withoutGlobalScopes()
                ->where('slug', '!=', 'food-menu')
                ->orderBy('hotel_id')
                ->orderBy('id')
                ->get();

            $hotels = DB::table('hotels')->get()->keyBy('id');

            $groupedByHotel = $modules->groupBy('hotel_id');
            $showAllHotels  = true;
        } else {
            $modules        = Module::where('slug', '!=', 'food-menu')->orderBy('id')->get();
            $groupedByHotel = null;
            $showAllHotels  = false;
            $hotels         = collect();
        }

        return view('admin.settings.modules', compact('modules', 'groupedByHotel', 'showAllHotels', 'hotels'));
    }

    public function toggle(Module $module)
    {
        $newState = !$module->is_enabled;
        $module->update(['is_enabled' => $newState]);

        // When enabling a module, auto-grant its permissions to the hotel's Admin role.
        if ($newState && isset(self::MODULE_PERMISSION_MAP[$module->slug])) {
            $this->grantModulePermissionsToAdmin($module->hotel_id, self::MODULE_PERMISSION_MAP[$module->slug]);
        }

        $status = $newState ? 'enabled' : 'disabled';
        return back()->with('success', "{$module->name} module {$status}.");
    }

    private function grantModulePermissionsToAdmin(int $hotelId, string $permissionModule): void
    {
        $adminRoleId = DB::table('roles')
            ->where('hotel_id', $hotelId)
            ->where('name', 'Admin')
            ->value('id');

        if (!$adminRoleId) {
            return;
        }

        $permIds = DB::table('permissions')
            ->where('module', $permissionModule)
            ->pluck('id');

        foreach ($permIds as $permId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id'       => $adminRoleId,
                'permission_id' => $permId,
            ]);
        }
    }
}
