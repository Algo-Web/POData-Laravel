<?php

use Illuminate\Database\Seeder;

class EmployeePrivilegesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('employee_privileges')->delete();
        
        \DB::table('employee_privileges')->insert(
            [
            0 =>
            [
                'employee_id' => 2,
                'privilege_id' => 2,
            ],
            ]
        );
    }
}
