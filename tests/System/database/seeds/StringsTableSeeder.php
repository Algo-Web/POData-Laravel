<?php

use Illuminate\Database\Seeder;

class StringsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('strings')->delete();
        
        \DB::table('strings')->insert(
            [
            0 =>
            [
                'string_id' => 2,
                'string_data' => 'Northwind Traders',
            ],
            1 =>
            [
                'string_id' => 3,
                'string_data' => 'Cannot remove posted inventory!',
            ],
            2 =>
            [
                'string_id' => 4,
                'string_data' => 'Back ordered product filled for Order #|',
            ],
            3 =>
            [
                'string_id' => 5,
                'string_data' => 'Discounted price below cost!',
            ],
            4 =>
            [
                'string_id' => 6,
                'string_data' => 'Insufficient inventory.',
            ],
            5 =>
            [
                'string_id' => 7,
                'string_data' => 'Insufficient inventory. Do you want to create a purchase order?',
            ],
            6 =>
            [
                'string_id' => 8,
                'string_data' => 'Purchase orders were successfully created for | products',
            ],
            7 =>
            [
                'string_id' => 9,
                'string_data' => 'There are no products below their respective reorder levels',
            ],
            8 =>
            [
                'string_id' => 10,
                'string_data' => 'Must specify customer name!',
            ],
            9 =>
            [
                'string_id' => 11,
                'string_data' => 'Restocking will generate purchase orders for all products below desired'
                                 .' inventory levels.  Do you want to continue?',
            ],
            10 =>
            [
                'string_id' => 12,
                'string_data' => 'Cannot create purchase order.  No suppliers listed for specified product',
            ],
            11 =>
            [
                'string_id' => 13,
                'string_data' => 'Discounted price is below cost!',
            ],
            12 =>
            [
                'string_id' => 14,
                'string_data' => 'Do you want to continue?',
            ],
            13 =>
            [
                'string_id' => 15,
                'string_data' => 'Order is already invoiced. Do you want to print the invoice?',
            ],
            14 =>
            [
                'string_id' => 16,
                'string_data' => 'Order does not contain any line items',
            ],
            15 =>
            [
                'string_id' => 17,
                'string_data' => 'Cannot create invoice!  Inventory has not been allocated for each specified product.',
            ],
            16 =>
            [
                'string_id' => 18,
                'string_data' => 'Sorry, there are no sales in the specified time period',
            ],
            17 =>
            [
                'string_id' => 19,
                'string_data' => 'Product successfully restocked.',
            ],
            18 =>
            [
                'string_id' => 21,
                'string_data' => 'Product does not need restocking! Product is already at desired inventory level.',
            ],
            19 =>
            [
                'string_id' => 22,
                'string_data' => 'Product restocking failed!',
            ],
            20 =>
            [
                'string_id' => 23,
                'string_data' => 'Invalid login specified!',
            ],
            21 =>
            [
                'string_id' => 24,
                'string_data' => 'Must first select reported!',
            ],
            22 =>
            [
                'string_id' => 25,
                'string_data' => 'Changing supplier will remove purchase line items, continue?',
            ],
            23 =>
            [
                'string_id' => 26,
                'string_data' => 'Purchase orders were successfully submitted for | products.  Do you want'
                                 .' to view the restocking report?',
            ],
            24 =>
            [
                'string_id' => 27,
                'string_data' => 'There was an error attempting to restock inventory levels.',
            ],
            25 =>
            [
                'string_id' => 28,
            'string_data' => '| product(s) were successfully restocked.  Do you want to view the restocking report?',
            ],
            26 =>
            [
                'string_id' => 29,
                'string_data' => 'You cannot remove purchase line items already posted to inventory!',
            ],
            27 =>
            [
                'string_id' => 30,
                'string_data' => 'There was an error removing one or more purchase line items.',
            ],
            28 =>
            [
                'string_id' => 31,
                'string_data' => 'You cannot modify quantity for purchased product already received or'
                                 .' posted to inventory.',
            ],
            29 =>
            [
                'string_id' => 32,
                'string_data' => 'You cannot modify price for purchased product already received or'
                                 .' posted to inventory.',
            ],
            30 =>
            [
                'string_id' => 33,
                'string_data' => 'Product has been successfully posted to inventory.',
            ],
            31 =>
            [
                'string_id' => 34,
                'string_data' => 'Sorry, product cannot be successfully posted to inventory.',
            ],
            32 =>
            [
                'string_id' => 35,
                'string_data' => 'There are orders with this product on back order.  Would you like to fill them now?',
            ],
            33 =>
            [
                'string_id' => 36,
                'string_data' => 'Cannot post product to inventory without specifying received date!',
            ],
            34 =>
            [
                'string_id' => 37,
                'string_data' => 'Do you want to post received product to inventory?',
            ],
            35 =>
            [
                'string_id' => 38,
                'string_data' => 'Initialize purchase, orders, and inventory data?',
            ],
            36 =>
            [
                'string_id' => 39,
                'string_data' => 'Must first specify employee name!',
            ],
            37 =>
            [
                'string_id' => 40,
                'string_data' => 'Specified user must be logged in to approve purchase!',
            ],
            38 =>
            [
                'string_id' => 41,
                'string_data' => 'Purchase order must contain completed line items before it can be approved',
            ],
            39 =>
            [
                'string_id' => 42,
                'string_data' => 'Sorry, you do not have permission to approve purchases.',
            ],
            40 =>
            [
                'string_id' => 43,
                'string_data' => 'Purchase successfully approved',
            ],
            41 =>
            [
                'string_id' => 44,
                'string_data' => 'Purchase cannot be approved',
            ],
            42 =>
            [
                'string_id' => 45,
                'string_data' => 'Purchase successfully submitted for approval',
            ],
            43 =>
            [
                'string_id' => 46,
                'string_data' => 'Purchase cannot be submitted for approval',
            ],
            44 =>
            [
                'string_id' => 47,
                'string_data' => 'Sorry, purchase order does not contain line items',
            ],
            45 =>
            [
                'string_id' => 48,
                'string_data' => 'Do you want to cancel this order?',
            ],
            46 =>
            [
                'string_id' => 49,
                'string_data' => 'Canceling an order will permanently delete the order.'
                                 .'  Are you sure you want to cancel?',
            ],
            47 =>
            [
                'string_id' => 100,
                'string_data' => 'Your order was successfully canceled.',
            ],
            48 =>
            [
                'string_id' => 101,
                'string_data' => 'Cannot cancel an order that has items received and posted to inventory.',
            ],
            49 =>
            [
                'string_id' => 102,
                'string_data' => 'There was an error trying to cancel this order.',
            ],
            50 =>
            [
                'string_id' => 103,
                'string_data' => 'The invoice for this order has not yet been created.',
            ],
            51 =>
            [
                'string_id' => 104,
                'string_data' => 'Shipping information is not complete.  Please specify all'
                                 .' shipping information and try again.',
            ],
            52 =>
            [
                'string_id' => 105,
                'string_data' => 'Cannot mark as shipped.  Order must first be invoiced!',
            ],
            53 =>
            [
                'string_id' => 106,
                'string_data' => 'Cannot cancel an order that has already shipped!',
            ],
            54 =>
            [
                'string_id' => 107,
                'string_data' => 'Must first specify salesperson!',
            ],
            55 =>
            [
                'string_id' => 108,
                'string_data' => 'Order is now marked closed.',
            ],
            56 =>
            [
                'string_id' => 109,
                'string_data' => 'Order must first be marked shipped before closing.',
            ],
            57 =>
            [
                'string_id' => 110,
                'string_data' => 'Must first specify payment information!',
            ],
            58 =>
            [
                'string_id' => 111,
            'string_data' => 'There was an error attempting to restock inventory levels.  | product(s)'
                             .' were successfully restocked.',
            ],
            59 =>
            [
                'string_id' => 112,
                'string_data' => 'You must supply a Unit Cost.',
            ],
            60 =>
            [
                'string_id' => 113,
                'string_data' => 'Fill back ordered product, Order #|',
            ],
            61 =>
            [
                'string_id' => 114,
                'string_data' => 'Purchase generated based on Order #|',
            ],
            ]
        );
    }
}
