<?php

use Illuminate\Database\Seeder;

class ProductTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('product')->delete();
        
        \DB::table('product')->insert(
            [
            0 =>
            [
                'id' => 6,
                'added_at' => '2013-05-07 00:00:00',
                'name' => 'Kedi',
                'weight' => '2.9200',
                'code' => 'Ked-25',
            ],
            1 =>
            [
                'id' => 9,
                'added_at' => '2009-08-05 00:00:00',
                'name' => 'Kedi',
                'weight' => '10.9100',
                'code' => 'Ked-51',
            ],
            2 =>
            [
                'id' => 13,
                'added_at' => '2003-02-27 00:00:00',
                'name' => 'Kedi',
                'weight' => '11.7300',
                'code' => 'Ked-17',
            ],
            3 =>
            [
                'id' => 29,
                'added_at' => '2014-12-19 00:00:00',
                'name' => 'Kedi',
                'weight' => '7.6100',
                'code' => 'Ked-29',
            ],
            4 =>
            [
                'id' => 33,
                'added_at' => '2003-07-05 00:00:00',
                'name' => 'Kedi',
                'weight' => '11.8700',
                'code' => 'Ked-99',
            ],
            5 =>
            [
                'id' => 36,
                'added_at' => '2015-09-15 00:00:00',
                'name' => 'Kedi',
                'weight' => '11.0000',
                'code' => 'Ked-89',
            ],
            6 =>
            [
                'id' => 40,
                'added_at' => '2004-01-25 00:00:00',
                'name' => 'Kedi',
                'weight' => '14.8800',
                'code' => 'Ked-83',
            ],
            7 =>
            [
                'id' => 47,
                'added_at' => '2006-04-23 00:00:00',
                'name' => 'Kedi',
                'weight' => '1.2100',
                'code' => 'Ked-62',
            ],
            8 =>
            [
                'id' => 51,
                'added_at' => '2012-12-08 00:00:00',
                'name' => 'Kedi',
                'weight' => '12.4000',
                'code' => 'Ked-86',
            ],
            9 =>
            [
                'id' => 54,
                'added_at' => '2010-06-09 00:00:00',
                'name' => 'Kedi',
                'weight' => '6.3800',
                'code' => 'Ked-61',
            ],
            10 =>
            [
                'id' => 58,
                'added_at' => '2010-04-25 00:00:00',
                'name' => 'Kedi',
                'weight' => '8.8900',
                'code' => 'Ked-74',
            ],
            11 =>
            [
                'id' => 106,
                'added_at' => '2004-04-11 00:00:00',
                'name' => 'Kedi',
                'weight' => '6.7100',
                'code' => 'Ked-44',
            ],
            12 =>
            [
                'id' => 134,
                'added_at' => '2001-02-07 00:00:00',
                'name' => 'Kedi',
                'weight' => '2.3200',
                'code' => 'Ked-29',
            ],
            13 =>
            [
                'id' => 153,
                'added_at' => '2002-01-13 00:00:00',
                'name' => 'Kedi',
                'weight' => '7.3300',
                'code' => 'Ked-80',
            ],
            14 =>
            [
                'id' => 156,
                'added_at' => '2014-03-20 00:00:00',
                'name' => 'Kedi',
                'weight' => '10.9600',
                'code' => 'Ked-30',
            ],
            15 =>
            [
                'id' => 165,
                'added_at' => '2003-07-11 00:00:00',
                'name' => 'Kedi',
                'weight' => '2.5300',
                'code' => 'Ked-90',
            ],
            16 =>
            [
                'id' => 176,
                'added_at' => '2010-09-26 00:00:00',
                'name' => 'Kedi',
                'weight' => '7.0100',
                'code' => 'Ked-38',
            ],
            17 =>
            [
                'id' => 182,
                'added_at' => '2007-05-07 00:00:00',
                'name' => 'Kedi',
                'weight' => '3.8900',
                'code' => 'Ked-6',
            ],
            18 =>
            [
                'id' => 194,
                'added_at' => '2004-03-21 00:00:00',
                'name' => 'Kedi',
                'weight' => '3.1000',
                'code' => 'Ked-20',
            ],
            19 =>
            [
                'id' => 205,
                'added_at' => '2000-06-02 00:00:00',
                'name' => 'Kedi',
                'weight' => '12.9500',
                'code' => 'Ked-20',
            ],
            20 =>
            [
                'id' => 212,
                'added_at' => '2002-02-20 00:00:00',
                'name' => 'Kedi',
                'weight' => '2.5300',
                'code' => 'Ked-62',
            ],
            21 =>
            [
                'id' => 220,
                'added_at' => '2000-10-19 00:00:00',
                'name' => 'Kedi',
                'weight' => '8.4000',
                'code' => 'Ked-31',
            ],
            ]
        );
    }
}
