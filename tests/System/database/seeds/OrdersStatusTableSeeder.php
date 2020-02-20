<?php

use Illuminate\Database\Seeder;

class OrdersStatusTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('orders_status')->delete();
        
        \DB::table('orders_status')->insert(
            [
            0 =>
            [
                'id' => 0,
                'status_name' => 'New',
            ],
            1 =>
            [
                'id' => 1,
                'status_name' => 'Invoiced',
            ],
            2 =>
            [
                'id' => 2,
                'status_name' => 'Shipped',
            ],
            3 =>
            [
                'id' => 3,
                'status_name' => 'Closed',
            ],
            ]
        );
    }
}
