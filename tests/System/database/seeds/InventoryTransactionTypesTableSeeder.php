<?php

use Illuminate\Database\Seeder;

class InventoryTransactionTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('inventory_transaction_types')->delete();
        
        \DB::table('inventory_transaction_types')->insert(
            [
            0 =>
            [
                'id' => 1,
                'type_name' => 'Purchased',
            ],
            1 =>
            [
                'id' => 2,
                'type_name' => 'Sold',
            ],
            2 =>
            [
                'id' => 3,
                'type_name' => 'On Hold',
            ],
            3 =>
            [
                'id' => 4,
                'type_name' => 'Waste',
            ],
            ]
        );
    }
}
