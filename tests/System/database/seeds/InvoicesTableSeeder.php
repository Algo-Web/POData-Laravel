<?php

use Illuminate\Database\Seeder;

class InvoicesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('invoices')->delete();
        
        \DB::table('invoices')->insert(
            [
            0 =>
            [
                'id' => 5,
                'order_id' => 31,
                'invoice_date' => '2006-03-22 16:08:59',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            1 =>
            [
                'id' => 6,
                'order_id' => 32,
                'invoice_date' => '2006-03-22 16:10:27',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            2 =>
            [
                'id' => 7,
                'order_id' => 40,
                'invoice_date' => '2006-03-24 10:41:41',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            3 =>
            [
                'id' => 8,
                'order_id' => 39,
                'invoice_date' => '2006-03-24 10:55:46',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            4 =>
            [
                'id' => 9,
                'order_id' => 38,
                'invoice_date' => '2006-03-24 10:56:57',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            5 =>
            [
                'id' => 10,
                'order_id' => 37,
                'invoice_date' => '2006-03-24 10:57:38',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            6 =>
            [
                'id' => 11,
                'order_id' => 36,
                'invoice_date' => '2006-03-24 10:58:40',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            7 =>
            [
                'id' => 12,
                'order_id' => 35,
                'invoice_date' => '2006-03-24 10:59:41',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            8 =>
            [
                'id' => 13,
                'order_id' => 34,
                'invoice_date' => '2006-03-24 11:00:55',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            9 =>
            [
                'id' => 14,
                'order_id' => 33,
                'invoice_date' => '2006-03-24 11:02:02',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            10 =>
            [
                'id' => 15,
                'order_id' => 30,
                'invoice_date' => '2006-03-24 11:03:00',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            11 =>
            [
                'id' => 16,
                'order_id' => 56,
                'invoice_date' => '2006-04-03 13:50:15',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            12 =>
            [
                'id' => 17,
                'order_id' => 55,
                'invoice_date' => '2006-04-04 11:05:04',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            13 =>
            [
                'id' => 18,
                'order_id' => 51,
                'invoice_date' => '2006-04-04 11:06:13',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            14 =>
            [
                'id' => 19,
                'order_id' => 50,
                'invoice_date' => '2006-04-04 11:06:56',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            15 =>
            [
                'id' => 20,
                'order_id' => 48,
                'invoice_date' => '2006-04-04 11:07:37',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            16 =>
            [
                'id' => 21,
                'order_id' => 47,
                'invoice_date' => '2006-04-04 11:08:14',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            17 =>
            [
                'id' => 22,
                'order_id' => 46,
                'invoice_date' => '2006-04-04 11:08:49',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            18 =>
            [
                'id' => 23,
                'order_id' => 45,
                'invoice_date' => '2006-04-04 11:09:24',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            19 =>
            [
                'id' => 24,
                'order_id' => 79,
                'invoice_date' => '2006-04-04 11:35:54',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            20 =>
            [
                'id' => 25,
                'order_id' => 78,
                'invoice_date' => '2006-04-04 11:36:21',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            21 =>
            [
                'id' => 26,
                'order_id' => 77,
                'invoice_date' => '2006-04-04 11:36:47',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            22 =>
            [
                'id' => 27,
                'order_id' => 76,
                'invoice_date' => '2006-04-04 11:37:09',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            23 =>
            [
                'id' => 28,
                'order_id' => 75,
                'invoice_date' => '2006-04-04 11:37:49',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            24 =>
            [
                'id' => 29,
                'order_id' => 74,
                'invoice_date' => '2006-04-04 11:38:11',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            25 =>
            [
                'id' => 30,
                'order_id' => 73,
                'invoice_date' => '2006-04-04 11:38:32',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            26 =>
            [
                'id' => 31,
                'order_id' => 72,
                'invoice_date' => '2006-04-04 11:38:53',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            27 =>
            [
                'id' => 32,
                'order_id' => 71,
                'invoice_date' => '2006-04-04 11:39:29',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            28 =>
            [
                'id' => 33,
                'order_id' => 70,
                'invoice_date' => '2006-04-04 11:39:53',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            29 =>
            [
                'id' => 34,
                'order_id' => 69,
                'invoice_date' => '2006-04-04 11:40:16',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            30 =>
            [
                'id' => 35,
                'order_id' => 67,
                'invoice_date' => '2006-04-04 11:40:38',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            31 =>
            [
                'id' => 36,
                'order_id' => 42,
                'invoice_date' => '2006-04-04 11:41:14',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            32 =>
            [
                'id' => 37,
                'order_id' => 60,
                'invoice_date' => '2006-04-04 11:41:45',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            33 =>
            [
                'id' => 38,
                'order_id' => 63,
                'invoice_date' => '2006-04-04 11:42:26',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            34 =>
            [
                'id' => 39,
                'order_id' => 58,
                'invoice_date' => '2006-04-04 11:43:08',
                'due_date' => null,
                'tax' => '0.0000',
                'shipping' => '0.0000',
                'amount_due' => '0.0000',
            ],
            ]
        );
    }
}
