<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        DB::table('customers')->delete();

        $customers = [
            ['name'=>'Rajesh Sharma','email'=>'rajesh.sharma@email.com','phone'=>'+91 98765 43210','address'=>'12 MG Road','city'=>'Mumbai','state'=>'Maharashtra','country'=>'India','id_type'=>'aadhaar','id_number'=>'1234 5678 9012','date_of_birth'=>'1985-06-15','nationality'=>'Indian','notes'=>null],
            ['name'=>'Priya Patel','email'=>'priya.patel@email.com','phone'=>'+91 87654 32109','address'=>'45 Ring Road','city'=>'Ahmedabad','state'=>'Gujarat','country'=>'India','id_type'=>'passport','id_number'=>'J8234561','date_of_birth'=>'1990-03-22','nationality'=>'Indian','notes'=>null],
            ['name'=>'Amit Verma','email'=>'amit.verma@email.com','phone'=>'+91 76543 21098','address'=>'78 Civil Lines','city'=>'Delhi','state'=>'Delhi','country'=>'India','id_type'=>'driving_license','id_number'=>'DL0120199903456','date_of_birth'=>'1982-11-10','nationality'=>'Indian','notes'=>null],
            ['name'=>'Sunita Reddy','email'=>'sunita.reddy@email.com','phone'=>'+91 65432 10987','address'=>'23 Jubilee Hills','city'=>'Hyderabad','state'=>'Telangana','country'=>'India','id_type'=>'aadhaar','id_number'=>'9876 5432 1098','date_of_birth'=>'1995-07-08','nationality'=>'Indian','notes'=>null],
            ['name'=>'Vijay Kumar','email'=>'vijay.kumar@email.com','phone'=>'+91 54321 09876','address'=>'56 Koramangala','city'=>'Bangalore','state'=>'Karnataka','country'=>'India','id_type'=>'voter_id','id_number'=>'KAR0412345','date_of_birth'=>'1978-12-25','nationality'=>'Indian','notes'=>null],
            ['name'=>'Meera Nair','email'=>'meera.nair@email.com','phone'=>'+91 43210 98765','address'=>'89 Marine Drive','city'=>'Kochi','state'=>'Kerala','country'=>'India','id_type'=>'passport','id_number'=>'Z9876543','date_of_birth'=>'1992-04-18','nationality'=>'Indian','notes'=>null],
            ['name'=>'Arjun Singh','email'=>'arjun.singh@email.com','phone'=>'+91 32109 87654','address'=>'34 Park Street','city'=>'Kolkata','state'=>'West Bengal','country'=>'India','id_type'=>'aadhaar','id_number'=>'5678 9012 3456','date_of_birth'=>'1988-09-30','nationality'=>'Indian','notes'=>null],
            ['name'=>'Kavitha Iyer','email'=>'kavitha.iyer@email.com','phone'=>'+91 21098 76543','address'=>'67 Anna Nagar','city'=>'Chennai','state'=>'Tamil Nadu','country'=>'India','id_type'=>'driving_license','id_number'=>'TN0220187654','date_of_birth'=>'1993-02-14','nationality'=>'Indian','notes'=>null],
            ['name'=>'Robert Wilson','email'=>'robert.wilson@email.com','phone'=>'+44 7700 900123','address'=>'10 Oxford Street','city'=>'London','state'=>'England','country'=>'UK','id_type'=>'passport','id_number'=>'GB1234567','date_of_birth'=>'1975-05-20','nationality'=>'British','notes'=>null],
            ['name'=>'Sofia Martinez','email'=>'sofia.martinez@email.com','phone'=>'+34 600 123456','address'=>'25 Gran Via','city'=>'Madrid','state'=>'Madrid','country'=>'Spain','id_type'=>'passport','id_number'=>'ES9876543','date_of_birth'=>'1987-08-12','nationality'=>'Spanish','notes'=>null],
            ['name'=>'Deepak Joshi','email'=>'deepak.joshi@email.com','phone'=>'+91 91234 56789','address'=>'15 Sector 18','city'=>'Noida','state'=>'UP','country'=>'India','id_type'=>'aadhaar','id_number'=>'2345 6789 0123','date_of_birth'=>'1983-01-07','nationality'=>'Indian','notes'=>null],
            ['name'=>'Ananya Das','email'=>'ananya.das@email.com','phone'=>'+91 81234 56789','address'=>'42 Salt Lake','city'=>'Kolkata','state'=>'West Bengal','country'=>'India','id_type'=>'passport','id_number'=>'M1234567','date_of_birth'=>'1997-10-05','nationality'=>'Indian','notes'=>null],
            ['name'=>'Suresh Pillai','email'=>'suresh.pillai@email.com','phone'=>'+91 71234 56789','address'=>'88 Nungambakkam','city'=>'Chennai','state'=>'Tamil Nadu','country'=>'India','id_type'=>'voter_id','id_number'=>'TN0523456','date_of_birth'=>'1980-03-28','nationality'=>'Indian','notes'=>null],
            ['name'=>'Ritu Agarwal','email'=>'ritu.agarwal@email.com','phone'=>'+91 61234 56789','address'=>'33 Lalbagh','city'=>'Lucknow','state'=>'UP','country'=>'India','id_type'=>'aadhaar','id_number'=>'6789 0123 4567','date_of_birth'=>'1991-06-17','nationality'=>'Indian','notes'=>null],
            ['name'=>'James Thompson','email'=>'james.thompson@email.com','phone'=>'+1 555 234 5678','address'=>'500 Fifth Avenue','city'=>'New York','state'=>'NY','country'=>'USA','id_type'=>'passport','id_number'=>'US1234567','date_of_birth'=>'1970-11-02','nationality'=>'American','notes'=>null],
        ];

        foreach ($customers as $c) {
            DB::table('customers')->insert(array_merge($c, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}