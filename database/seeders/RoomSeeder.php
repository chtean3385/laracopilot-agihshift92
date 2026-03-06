<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run()
    {
        DB::table('rooms')->delete();

        $rooms = [
            ['room_number'=>'101','type'=>'standard','capacity'=>2,'price_per_night'=>3500,'floor'=>1,'view'=>'Garden View','amenities'=>'AC, TV, WiFi, Hot Water','status'=>'available'],
            ['room_number'=>'102','type'=>'standard','capacity'=>2,'price_per_night'=>3500,'floor'=>1,'view'=>'Garden View','amenities'=>'AC, TV, WiFi, Hot Water','status'=>'available'],
            ['room_number'=>'103','type'=>'standard','capacity'=>3,'price_per_night'=>4000,'floor'=>1,'view'=>'Pool View','amenities'=>'AC, TV, WiFi, Mini Fridge','status'=>'available'],
            ['room_number'=>'104','type'=>'standard','capacity'=>2,'price_per_night'=>3500,'floor'=>1,'view'=>'Garden View','amenities'=>'AC, TV, WiFi, Hot Water','status'=>'available'],
            ['room_number'=>'105','type'=>'standard','capacity'=>2,'price_per_night'=>3500,'floor'=>1,'view'=>'Pool View','amenities'=>'AC, TV, WiFi, Hot Water','status'=>'available'],
            ['room_number'=>'201','type'=>'deluxe','capacity'=>2,'price_per_night'=>5500,'floor'=>2,'view'=>'Sea View','amenities'=>'AC, Smart TV, WiFi, Mini Bar, Balcony','status'=>'available'],
            ['room_number'=>'202','type'=>'deluxe','capacity'=>2,'price_per_night'=>5500,'floor'=>2,'view'=>'Sea View','amenities'=>'AC, Smart TV, WiFi, Mini Bar, Balcony','status'=>'occupied'],
            ['room_number'=>'203','type'=>'deluxe','capacity'=>3,'price_per_night'=>6000,'floor'=>2,'view'=>'Pool and Sea View','amenities'=>'AC, Smart TV, WiFi, Mini Bar, Balcony, Jacuzzi','status'=>'available'],
            ['room_number'=>'204','type'=>'deluxe','capacity'=>2,'price_per_night'=>5800,'floor'=>2,'view'=>'Sea View','amenities'=>'AC, Smart TV, WiFi, Mini Bar, Balcony','status'=>'available'],
            ['room_number'=>'301','type'=>'suite','capacity'=>4,'price_per_night'=>9500,'floor'=>3,'view'=>'Panoramic Sea View','amenities'=>'AC, Smart TV x2, WiFi, Jacuzzi, Mini Bar, Living Area','status'=>'available'],
            ['room_number'=>'302','type'=>'suite','capacity'=>4,'price_per_night'=>9500,'floor'=>3,'view'=>'Panoramic Sea View','amenities'=>'AC, Smart TV x2, WiFi, Jacuzzi, Mini Bar, Living Area','status'=>'maintenance'],
            ['room_number'=>'401','type'=>'suite','capacity'=>6,'price_per_night'=>12000,'floor'=>4,'view'=>'360 Ocean View','amenities'=>'AC, Smart TV x3, WiFi, Hot Tub, Full Bar, 2 Balconies','status'=>'available'],
            ['room_number'=>'V01','type'=>'villa','capacity'=>6,'price_per_night'=>18000,'floor'=>1,'view'=>'Private Beach','amenities'=>'AC, Smart TV x4, WiFi, Private Pool, Chef, Butler, BBQ','status'=>'available'],
            ['room_number'=>'V02','type'=>'villa','capacity'=>8,'price_per_night'=>22000,'floor'=>1,'view'=>'Private Beach and Garden','amenities'=>'AC, Smart TV x5, WiFi, Private Pool, Chef, Butler, Spa','status'=>'available'],
            ['room_number'=>'PH01','type'=>'penthouse','capacity'=>8,'price_per_night'=>35000,'floor'=>5,'view'=>'Full Panoramic Sea View','amenities'=>'AC, Smart TV x6, WiFi, Private Rooftop Pool, Full Bar, Chef','status'=>'available'],
        ];

        foreach ($rooms as $room) {
            DB::table('rooms')->insert(array_merge($room, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}