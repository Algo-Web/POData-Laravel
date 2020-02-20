<?php

use Illuminate\Database\Seeder;

class OrderDetailsStatusTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('order_details_status')->delete();
        
        \DB::table('order_details_status')->insert(
            [
            0 =>
            [
                'id' => 0,
                'status_name' => 'None',
            ],
            1 =>
            [
                'id' => 1,
                'status_name' => 'Allocated',
            ],
            2 =>
            [
                'id' => 2,
                'status_name' => 'Invoiced',
            ],
            3 =>
            [
                'id' => 3,
                'status_name' => 'Shipped',
            ],
            4 =>
            [
                'id' => 4,
                'status_name' => 'On Order',
            ],
            5 =>
            [
                'id' => 5,
                'status_name' => 'No Stock',
            ],
            ]
        );
    }
}
