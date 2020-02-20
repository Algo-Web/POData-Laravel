<?php

use Illuminate\Database\Seeder;

class PurchaseOrderStatusTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('purchase_order_status')->delete();
        
        \DB::table('purchase_order_status')->insert(
            [
            0 =>
            [
                'id' => 0,
                'status' => 'New',
            ],
            1 =>
            [
                'id' => 1,
                'status' => 'Submitted',
            ],
            2 =>
            [
                'id' => 2,
                'status' => 'Approved',
            ],
            3 =>
            [
                'id' => 3,
                'status' => 'Closed',
            ],
            ]
        );
    }
}
