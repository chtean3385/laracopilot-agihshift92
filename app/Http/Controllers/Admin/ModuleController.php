<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index()
    {
        $modules = Module::orderBy('id')->get();
        return view('admin.settings.modules', compact('modules'));
    }

    public function toggle(Module $module)
    {
        $module->update(['is_enabled' => !$module->is_enabled]);
        $status = $module->is_enabled ? 'enabled' : 'disabled';
        return back()->with('success', "{$module->name} module {$status}.");
    }
}
