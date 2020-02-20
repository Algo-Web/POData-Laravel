<?php

use Illuminate\Database\Seeder;

class OrdersTaxStatusTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('orders_tax_status')->delete();
        
        \DB::table('orders_tax_status')->insert(
            [
            0 =>
            [
                'id' => 0,
                'tax_status_name' => 'Tax Exempt',
            ],
            1 =>
            [
                'id' => 1,
                'tax_status_name' => 'Taxable',
            ],
            ]
        );
    }
}
