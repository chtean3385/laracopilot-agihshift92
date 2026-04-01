<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuestController extends Controller
{
    public function deleted(Request $request)
    {
        $hotelId = $request->hotel_id ? (int) $request->hotel_id : null;

        $query = Customer::withTrashed()
            ->whereNotNull('customers.deleted_at');

        if ($hotelId) {
            $query->where('customers.hotel_id', $hotelId);
        }

        $guests = $query
            ->join('hotels', 'hotels.id', '=', 'customers.hotel_id')
            ->select('customers.*', 'hotels.name as hotel_name')
            ->orderByDesc('customers.deleted_at')
            ->paginate(20)
            ->withQueryString();

        $hotels = DB::table('hotels')->orderBy('name')->get(['id', 'name']);

        return view('platform.guests.deleted', compact('guests', 'hotels', 'hotelId'));
    }

    public function restore(int $id)
    {
        $customer = Customer::withTrashed()->findOrFail($id);

        if (!$customer->trashed()) {
            return redirect()->route('platform.guests.deleted')
                ->with('error', 'Guest is not deleted.');
        }

        $name     = $customer->name;
        $phone    = $customer->phone;
        $hotelName = DB::table('hotels')->where('id', $customer->hotel_id)->value('name') ?? 'Unknown Hotel';

        $customer->restore();

        ActivityLogger::log(
            'Restored',
            'Guest',
            'Platform Admin restored soft-deleted guest: ' . $name . ' (' . $phone . ') at ' . $hotelName
        );

        return redirect()->back()
            ->with('success', "Guest \"{$name}\" has been restored successfully.");
    }
}
