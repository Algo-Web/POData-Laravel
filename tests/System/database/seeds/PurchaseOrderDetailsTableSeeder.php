<?php

use Illuminate\Database\Seeder;

class PurchaseOrderDetailsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('purchase_order_details')->delete();
        
        \DB::table('purchase_order_details')->insert(
            [
            0 =>
            [
                'id' => 238,
                'purchase_order_id' => 90,
                'product_id' => 1,
                'quantity' => '40.0000',
                'unit_cost' => '14.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 59,
            ],
            1 =>
            [
                'id' => 239,
                'purchase_order_id' => 91,
                'product_id' => 3,
                'quantity' => '100.0000',
                'unit_cost' => '8.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 54,
            ],
            2 =>
            [
                'id' => 240,
                'purchase_order_id' => 91,
                'product_id' => 4,
                'quantity' => '40.0000',
                'unit_cost' => '16.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 55,
            ],
            3 =>
            [
                'id' => 241,
                'purchase_order_id' => 91,
                'product_id' => 5,
                'quantity' => '40.0000',
                'unit_cost' => '16.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 56,
            ],
            4 =>
            [
                'id' => 242,
                'purchase_order_id' => 92,
                'product_id' => 6,
                'quantity' => '100.0000',
                'unit_cost' => '19.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 40,
            ],
            5 =>
            [
                'id' => 243,
                'purchase_order_id' => 92,
                'product_id' => 7,
                'quantity' => '40.0000',
                'unit_cost' => '22.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 41,
            ],
            6 =>
            [
                'id' => 244,
                'purchase_order_id' => 92,
                'product_id' => 8,
                'quantity' => '40.0000',
                'unit_cost' => '30.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 42,
            ],
            7 =>
            [
                'id' => 245,
                'purchase_order_id' => 92,
                'product_id' => 14,
                'quantity' => '40.0000',
                'unit_cost' => '17.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 43,
            ],
            8 =>
            [
                'id' => 246,
                'purchase_order_id' => 92,
                'product_id' => 17,
                'quantity' => '40.0000',
                'unit_cost' => '29.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 44,
            ],
            9 =>
            [
                'id' => 247,
                'purchase_order_id' => 92,
                'product_id' => 19,
                'quantity' => '20.0000',
                'unit_cost' => '7.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 45,
            ],
            10 =>
            [
                'id' => 248,
                'purchase_order_id' => 92,
                'product_id' => 20,
                'quantity' => '40.0000',
                'unit_cost' => '61.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 46,
            ],
            11 =>
            [
                'id' => 249,
                'purchase_order_id' => 92,
                'product_id' => 21,
                'quantity' => '20.0000',
                'unit_cost' => '8.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 47,
            ],
            12 =>
            [
                'id' => 250,
                'purchase_order_id' => 90,
                'product_id' => 34,
                'quantity' => '60.0000',
                'unit_cost' => '10.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 60,
            ],
            13 =>
            [
                'id' => 251,
                'purchase_order_id' => 92,
                'product_id' => 40,
                'quantity' => '120.0000',
                'unit_cost' => '14.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 48,
            ],
            14 =>
            [
                'id' => 252,
                'purchase_order_id' => 92,
                'product_id' => 41,
                'quantity' => '40.0000',
                'unit_cost' => '7.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 49,
            ],
            15 =>
            [
                'id' => 253,
                'purchase_order_id' => 90,
                'product_id' => 43,
                'quantity' => '100.0000',
                'unit_cost' => '34.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 61,
            ],
            16 =>
            [
                'id' => 254,
                'purchase_order_id' => 92,
                'product_id' => 48,
                'quantity' => '100.0000',
                'unit_cost' => '10.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 50,
            ],
            17 =>
            [
                'id' => 255,
                'purchase_order_id' => 92,
                'product_id' => 51,
                'quantity' => '40.0000',
                'unit_cost' => '40.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 51,
            ],
            18 =>
            [
                'id' => 256,
                'purchase_order_id' => 93,
                'product_id' => 52,
                'quantity' => '100.0000',
                'unit_cost' => '5.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 37,
            ],
            19 =>
            [
                'id' => 257,
                'purchase_order_id' => 93,
                'product_id' => 56,
                'quantity' => '120.0000',
                'unit_cost' => '28.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 38,
            ],
            20 =>
            [
                'id' => 258,
                'purchase_order_id' => 93,
                'product_id' => 57,
                'quantity' => '80.0000',
                'unit_cost' => '15.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 39,
            ],
            21 =>
            [
                'id' => 259,
                'purchase_order_id' => 91,
                'product_id' => 65,
                'quantity' => '40.0000',
                'unit_cost' => '16.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 57,
            ],
            22 =>
            [
                'id' => 260,
                'purchase_order_id' => 91,
                'product_id' => 66,
                'quantity' => '80.0000',
                'unit_cost' => '13.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 58,
            ],
            23 =>
            [
                'id' => 261,
                'purchase_order_id' => 94,
                'product_id' => 72,
                'quantity' => '40.0000',
                'unit_cost' => '26.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 36,
            ],
            24 =>
            [
                'id' => 262,
                'purchase_order_id' => 92,
                'product_id' => 74,
                'quantity' => '20.0000',
                'unit_cost' => '8.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 52,
            ],
            25 =>
            [
                'id' => 263,
                'purchase_order_id' => 92,
                'product_id' => 77,
                'quantity' => '60.0000',
                'unit_cost' => '10.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 53,
            ],
            26 =>
            [
                'id' => 264,
                'purchase_order_id' => 95,
                'product_id' => 80,
                'quantity' => '75.0000',
                'unit_cost' => '3.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 35,
            ],
            27 =>
            [
                'id' => 265,
                'purchase_order_id' => 90,
                'product_id' => 81,
                'quantity' => '125.0000',
                'unit_cost' => '2.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 62,
            ],
            28 =>
            [
                'id' => 266,
                'purchase_order_id' => 96,
                'product_id' => 34,
                'quantity' => '100.0000',
                'unit_cost' => '10.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 82,
            ],
            29 =>
            [
                'id' => 267,
                'purchase_order_id' => 97,
                'product_id' => 19,
                'quantity' => '30.0000',
                'unit_cost' => '7.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 80,
            ],
            30 =>
            [
                'id' => 268,
                'purchase_order_id' => 98,
                'product_id' => 41,
                'quantity' => '200.0000',
                'unit_cost' => '7.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 78,
            ],
            31 =>
            [
                'id' => 269,
                'purchase_order_id' => 99,
                'product_id' => 43,
                'quantity' => '300.0000',
                'unit_cost' => '34.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 76,
            ],
            32 =>
            [
                'id' => 270,
                'purchase_order_id' => 100,
                'product_id' => 48,
                'quantity' => '100.0000',
                'unit_cost' => '10.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 74,
            ],
            33 =>
            [
                'id' => 271,
                'purchase_order_id' => 101,
                'product_id' => 81,
                'quantity' => '200.0000',
                'unit_cost' => '2.0000',
                'date_received' => '2006-01-22 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 72,
            ],
            34 =>
            [
                'id' => 272,
                'purchase_order_id' => 102,
                'product_id' => 43,
                'quantity' => '300.0000',
                'unit_cost' => '34.0000',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            35 =>
            [
                'id' => 273,
                'purchase_order_id' => 103,
                'product_id' => 19,
                'quantity' => '10.0000',
                'unit_cost' => '7.0000',
                'date_received' => '2006-04-17 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 111,
            ],
            36 =>
            [
                'id' => 274,
                'purchase_order_id' => 104,
                'product_id' => 41,
                'quantity' => '50.0000',
                'unit_cost' => '7.0000',
                'date_received' => '2006-04-06 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 115,
            ],
            37 =>
            [
                'id' => 275,
                'purchase_order_id' => 105,
                'product_id' => 57,
                'quantity' => '100.0000',
                'unit_cost' => '15.0000',
                'date_received' => '2006-04-05 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 100,
            ],
            38 =>
            [
                'id' => 276,
                'purchase_order_id' => 106,
                'product_id' => 72,
                'quantity' => '50.0000',
                'unit_cost' => '26.0000',
                'date_received' => '2006-04-05 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 113,
            ],
            39 =>
            [
                'id' => 277,
                'purchase_order_id' => 107,
                'product_id' => 34,
                'quantity' => '300.0000',
                'unit_cost' => '10.0000',
                'date_received' => '2006-04-05 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 107,
            ],
            40 =>
            [
                'id' => 278,
                'purchase_order_id' => 108,
                'product_id' => 8,
                'quantity' => '25.0000',
                'unit_cost' => '30.0000',
                'date_received' => '2006-04-05 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 105,
            ],
            41 =>
            [
                'id' => 279,
                'purchase_order_id' => 109,
                'product_id' => 19,
                'quantity' => '25.0000',
                'unit_cost' => '7.0000',
                'date_received' => '2006-04-05 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 109,
            ],
            42 =>
            [
                'id' => 280,
                'purchase_order_id' => 110,
                'product_id' => 43,
                'quantity' => '250.0000',
                'unit_cost' => '34.0000',
                'date_received' => '2006-04-10 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 103,
            ],
            43 =>
            [
                'id' => 281,
                'purchase_order_id' => 90,
                'product_id' => 1,
                'quantity' => '40.0000',
                'unit_cost' => '14.0000',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            44 =>
            [
                'id' => 282,
                'purchase_order_id' => 92,
                'product_id' => 19,
                'quantity' => '20.0000',
                'unit_cost' => '7.0000',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            45 =>
            [
                'id' => 283,
                'purchase_order_id' => 111,
                'product_id' => 34,
                'quantity' => '50.0000',
                'unit_cost' => '10.0000',
                'date_received' => '2006-04-04 00:00:00',
                'posted_to_inventory' => 1,
                'inventory_id' => 102,
            ],
            46 =>
            [
                'id' => 285,
                'purchase_order_id' => 91,
                'product_id' => 3,
                'quantity' => '50.0000',
                'unit_cost' => '8.0000',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            47 =>
            [
                'id' => 286,
                'purchase_order_id' => 91,
                'product_id' => 4,
                'quantity' => '40.0000',
                'unit_cost' => '16.0000',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            48 =>
            [
                'id' => 288,
                'purchase_order_id' => 140,
                'product_id' => 85,
                'quantity' => '10.0000',
                'unit_cost' => '9.0000',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            49 =>
            [
                'id' => 289,
                'purchase_order_id' => 141,
                'product_id' => 6,
                'quantity' => '10.0000',
                'unit_cost' => '18.7500',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            50 =>
            [
                'id' => 290,
                'purchase_order_id' => 142,
                'product_id' => 1,
                'quantity' => '1.0000',
                'unit_cost' => '13.5000',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            51 =>
            [
                'id' => 292,
                'purchase_order_id' => 146,
                'product_id' => 20,
                'quantity' => '40.0000',
                'unit_cost' => '60.0000',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            52 =>
            [
                'id' => 293,
                'purchase_order_id' => 146,
                'product_id' => 51,
                'quantity' => '40.0000',
                'unit_cost' => '39.0000',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            53 =>
            [
                'id' => 294,
                'purchase_order_id' => 147,
                'product_id' => 40,
                'quantity' => '120.0000',
                'unit_cost' => '13.0000',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            54 =>
            [
                'id' => 295,
                'purchase_order_id' => 148,
                'product_id' => 72,
                'quantity' => '40.0000',
                'unit_cost' => '26.0000',
                'date_received' => null,
                'posted_to_inventory' => 0,
                'inventory_id' => null,
            ],
            ]
        );
    }
}
