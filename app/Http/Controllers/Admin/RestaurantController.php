<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Models\RestaurantOrder;
use App\Models\RestaurantBill;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    private function hotelId(): int
    {
        return (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
    }

    // Table Map — main restaurant page
    public function index()
    {
        $tables = RestaurantTable::where('is_active', true)
            ->with('activeOrder')
            ->orderBy('name')
            ->get();

        return view('admin.restaurant.index', compact('tables'));
    }

    // Store new table
    public function tableStore(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:50',
            'capacity' => 'required|integer|min:1|max:50',
        ]);

        RestaurantTable::create([
            'hotel_id' => $this->hotelId(),
            'name'     => $request->name,
            'capacity' => $request->capacity,
            'status'   => 'free',
        ]);

        ActivityLogger::log('restaurant_table_created', 'Restaurant', "Table '{$request->name}' created");

        return back()->with('success', "Table '{$request->name}' added successfully.");
    }

    // Update table
    public function tableUpdate(Request $request, $id)
    {
        $request->validate([
            'name'     => 'required|string|max:50',
            'capacity' => 'required|integer|min:1|max:50',
            'status'   => 'sometimes|in:free,dirty,unavailable',
        ]);

        $table = RestaurantTable::findOrFail($id);

        $data = [
            'name'     => $request->name,
            'capacity' => $request->capacity,
        ];

        // Allow status change only when table is not occupied
        if ($request->filled('status') && $table->status !== 'occupied') {
            $data['status'] = $request->status;
        }

        $table->update($data);

        ActivityLogger::log('restaurant_table_updated', 'Restaurant', "Table '{$table->name}' updated");

        return back()->with('success', 'Table updated successfully.');
    }

    // Delete table
    public function tableDestroy($id)
    {
        $table = RestaurantTable::findOrFail($id);

        if ($table->status === 'occupied') {
            return back()->with('error', 'Cannot delete an occupied table. Close the order first.');
        }

        $table->delete();
        ActivityLogger::log('restaurant_table_deleted', 'Restaurant', "Table '{$table->name}' deleted");

        return back()->with('success', 'Table deleted successfully.');
    }

    // Update table status (free/unavailable)
    public function tableStatus(Request $request, $id)
    {
     $request->validate([
    'status' => 'required|in:free,dirty,unavailable',
]);

        $table = RestaurantTable::findOrFail($id);

        if ($table->status === 'occupied') {
            return response()->json(['error' => 'Cannot change status of an occupied table.'], 422);
        }

        $table->update(['status' => $request->status]);

        return response()->json(['success' => true, 'status' => $request->status]);
    }

    // Restaurant reports
    public function reports(Request $request)
    {
        $hotelId = $this->hotelId();
        $from    = $request->input('from', now()->startOfMonth()->toDateString());
        $to      = $request->input('to', now()->toDateString());

        $bills = RestaurantBill::where('hotel_id', $hotelId)
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->with('order.table')
            ->orderByDesc('created_at')
            ->get();

        $totalRevenue    = $bills->sum('total');
        $totalTax        = $bills->sum('tax_amount');
        $directBills     = $bills->where('bill_type', 'direct')->count();
        $roomBills       = $bills->where('bill_type', 'room')->count();

        $tblHeaders = ['Bill#', 'Date', 'Table', 'Type', 'Subtotal', 'Tax', 'Total'];
        $tblRows = $bills->map(fn($b) => [
            $b->bill_number ?? $b->id,
            $b->created_at?->format('d/m/Y H:i'),
            $b->order?->table?->name ?? '-',
            ucfirst($b->bill_type ?? ''),
            number_format((float)$b->subtotal, 2, '.', ''),
            number_format((float)$b->tax_amount, 2, '.', ''),
            number_format((float)$b->total, 2, '.', ''),
        ])->all();

        if ($request->export === 'csv') {
            $headers = [
                'Content-Type'        => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="restaurant-sales-' . $from . '-to-' . $to . '.csv"',
            ];
            return response()->stream(function () use ($tblHeaders, $tblRows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, $tblHeaders);
                foreach ($tblRows as $r) fputcsv($out, $r);
                fclose($out);
            }, 200, $headers);
        }

        if ($request->export === 'pdf') {
            $hotel  = \App\Models\Hotel::find($hotelId);
            $title  = 'Restaurant Sales Report';
            $period = \Carbon\Carbon::parse($from)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($to)->format('d M Y');
            $kpis = [
                'Total Revenue' => '₹' . number_format($totalRevenue, 2),
                'Total Tax'     => '₹' . number_format($totalTax, 2),
                'Direct Bills'  => $directBills,
                'Room Bills'    => $roomBills,
            ];
            $numeric    = [0,0,0,0,1,1,1];
            $totalsRow  = null;
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports._pdf',
                ['title'=>$title,'hotel'=>$hotel,'period'=>$period,'kpis'=>$kpis,
                 'headers'=>$tblHeaders,'rows'=>$tblRows,'numeric'=>$numeric,'totalsRow'=>$totalsRow])
                ->setPaper('a4', 'landscape');
            return $pdf->download('restaurant-sales-' . $from . '-to-' . $to . '.pdf');
        }

        return view('admin.restaurant.reports', compact(
            'bills', 'totalRevenue', 'totalTax', 'directBills', 'roomBills', 'from', 'to'
        ));
    }
}