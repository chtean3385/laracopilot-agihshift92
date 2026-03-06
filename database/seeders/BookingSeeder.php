<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run()
    {
        DB::table('invoices')->delete();
        DB::table('payments')->delete();
        DB::table('bookings')->delete();

        $data = [
            ['cid'=>1,'rid'=>6,'offset'=>0, 'nights'=>3,'adults'=>2,'children'=>0,'status'=>'confirmed',  'advance'=>5500],
            ['cid'=>2,'rid'=>7,'offset'=>-1,'nights'=>4,'adults'=>2,'children'=>0,'status'=>'checked_in', 'advance'=>5500],
            ['cid'=>3,'rid'=>10,'offset'=>0,'nights'=>2,'adults'=>3,'children'=>1,'status'=>'confirmed',  'advance'=>9500],
            ['cid'=>4,'rid'=>1,'offset'=>-2,'nights'=>5,'adults'=>2,'children'=>0,'status'=>'checked_in', 'advance'=>7000],
            ['cid'=>5,'rid'=>13,'offset'=>-1,'nights'=>7,'adults'=>4,'children'=>2,'status'=>'checked_in','advance'=>36000],
            ['cid'=>6,'rid'=>3,'offset'=>1, 'nights'=>3,'adults'=>2,'children'=>0,'status'=>'confirmed',  'advance'=>4000],
            ['cid'=>7,'rid'=>12,'offset'=>-5,'nights'=>6,'adults'=>2,'children'=>0,'status'=>'checked_out','advance'=>12000],
            ['cid'=>8,'rid'=>2,'offset'=>-3,'nights'=>4,'adults'=>1,'children'=>0,'status'=>'checked_out','advance'=>3500],
            ['cid'=>9,'rid'=>15,'offset'=>2,'nights'=>5,'adults'=>6,'children'=>2,'status'=>'confirmed',  'advance'=>70000],
            ['cid'=>10,'rid'=>8,'offset'=>0,'nights'=>4,'adults'=>2,'children'=>0,'status'=>'confirmed',  'advance'=>6000],
            ['cid'=>11,'rid'=>14,'offset'=>-1,'nights'=>3,'adults'=>4,'children'=>2,'status'=>'checked_in','advance'=>22000],
            ['cid'=>12,'rid'=>4,'offset'=>3,'nights'=>2,'adults'=>2,'children'=>0,'status'=>'confirmed',  'advance'=>5800],
            ['cid'=>13,'rid'=>11,'offset'=>-7,'nights'=>8,'adults'=>2,'children'=>1,'status'=>'checked_out','advance'=>14000],
            ['cid'=>14,'rid'=>5,'offset'=>-2,'nights'=>3,'adults'=>2,'children'=>0,'status'=>'checked_in','advance'=>3500],
            ['cid'=>15,'rid'=>9,'offset'=>5,'nights'=>10,'adults'=>4,'children'=>0,'status'=>'confirmed', 'advance'=>19000],
        ];

        $methods = ['cash','card','upi'];

        foreach ($data as $d) {
            $room = DB::table('rooms')->find($d['rid']);
            if (!$room) continue;

            $checkIn    = Carbon::today()->addDays($d['offset']);
            $checkOut   = $checkIn->copy()->addDays($d['nights']);
            $total      = $d['nights'] * $room->price_per_night;
            $advance    = min($d['advance'], $total);
            $balance    = $total - $advance;
            $payStatus  = $balance == 0 ? 'paid' : ($advance > 0 ? 'partial' : 'pending');

            $bookingId = DB::table('bookings')->insertGetId([
                'booking_number'    => 'BK' . strtoupper(substr(uniqid(), -6)),
                'customer_id'       => $d['cid'],
                'room_id'           => $d['rid'],
                'check_in_date'     => $checkIn->format('Y-m-d'),
                'check_out_date'    => $checkOut->format('Y-m-d'),
                'actual_checkin_at' => in_array($d['status'],['checked_in','checked_out']) ? $checkIn->copy()->setTime(14,0)->format('Y-m-d H:i:s') : null,
                'actual_checkout_at'=> $d['status'] === 'checked_out' ? $checkOut->copy()->setTime(11,0)->format('Y-m-d H:i:s') : null,
                'nights'            => $d['nights'],
                'adults'            => $d['adults'],
                'children'          => $d['children'],
                'total_amount'      => $total,
                'advance_payment'   => $advance,
                'balance_due'       => $balance,
                'status'            => $d['status'],
                'payment_status'    => $payStatus,
                'special_requests'  => null,
                'checkin_notes'     => null,
                'checkout_notes'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            DB::table('payments')->insert([
                'booking_id'     => $bookingId,
                'customer_id'    => $d['cid'],
                'amount'         => $advance,
                'payment_method' => $methods[array_rand($methods)],
                'payment_type'   => 'advance',
                'status'         => 'completed',
                'transaction_id' => 'TXN' . strtoupper(substr(uniqid(), -8)),
                'notes'          => null,
                'created_at'     => $checkIn->format('Y-m-d H:i:s'),
                'updated_at'     => $checkIn->format('Y-m-d H:i:s'),
            ]);

            if ($d['status'] === 'checked_out' && $balance > 0) {
                DB::table('payments')->insert([
                    'booking_id'     => $bookingId,
                    'customer_id'    => $d['cid'],
                    'amount'         => $balance,
                    'payment_method' => 'cash',
                    'payment_type'   => 'final',
                    'status'         => 'completed',
                    'transaction_id' => 'TXN' . strtoupper(substr(uniqid(), -8)),
                    'notes'          => null,
                    'created_at'     => $checkOut->format('Y-m-d H:i:s'),
                    'updated_at'     => $checkOut->format('Y-m-d H:i:s'),
                ]);

                DB::table('invoices')->insert([
                    'invoice_number' => 'INV' . strtoupper(substr(uniqid(), -6)),
                    'booking_id'     => $bookingId,
                    'customer_id'    => $d['cid'],
                    'total_amount'   => $total,
                    'paid_amount'    => $total,
                    'balance'        => 0,
                    'status'         => 'paid',
                    'issued_at'      => $checkOut->format('Y-m-d H:i:s'),
                    'created_at'     => $checkOut->format('Y-m-d H:i:s'),
                    'updated_at'     => $checkOut->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}