<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        $query = ActivityLog::query()->orderByDesc('created_at');

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%");
            });
        }

        if ($module = request('module')) {
            $query->where('module', $module);
        }

        if ($action = request('action')) {
            $query->where('action', $action);
        }

        if ($date = request('date')) {
            $query->whereDate('created_at', $date);
        }

        $logs    = $query->paginate(50)->withQueryString();
        $modules = ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module');
        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.activity_logs.index', compact('logs', 'modules', 'actions'));
    }
}
