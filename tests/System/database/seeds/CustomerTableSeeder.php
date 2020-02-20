<?php

use Illuminate\Database\Seeder;

class CustomerTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('customer')->delete();
        
        \DB::table('customer')->insert(array (
            0 =>
            array (
                'id' => 1,
                'name' => 'Bilbo Baggins',
                'staff_id' => 1,
                'photo_id' => 5,
            ),
            1 =>
            array (
                'id' => 2,
                'name' => 'Oin',
                'staff_id' => 1,
                'photo_id' => 6,
            ),
            2 =>
            array (
                'id' => 3,
                'name' => 'Azaghal',
                'staff_id' => 1,
                'photo_id' => 7,
            ),
            3 =>
            array (
                'id' => 4,
                'name' => 'Gimli',
                'staff_id' => 1,
                'photo_id' => 8,
            ),
            4 =>
            array (
                'id' => 5,
                'name' => 'Catelyn Stark',
                'staff_id' => 2,
                'photo_id' => 9,
            ),
            5 =>
            array (
                'id' => 6,
                'name' => 'Ygritte',
                'staff_id' => 2,
                'photo_id' => 10,
            ),
            6 =>
            array (
                'id' => 7,
                'name' => 'Melisandre',
                'staff_id' => 2,
                'photo_id' => 11,
            ),
            7 =>
            array (
                'id' => 8,
                'name' => 'The Doctor',
                'staff_id' => 3,
                'photo_id' => 12,
            ),
            8 =>
            array (
                'id' => 9,
                'name' => 'Susan Foreman',
                'staff_id' => 3,
                'photo_id' => 13,
            ),
            9 =>
            array (
                'id' => 10,
                'name' => 'Death',
                'staff_id' => 4,
                'photo_id' => 14,
            ),
            10 =>
            array (
                'id' => 11,
                'name' => 'The Hogfather',
                'staff_id' => 4,
                'photo_id' => 15,
            ),
            11 =>
            array (
                'id' => 12,
                'name' => 'Moist von Lipwig',
                'staff_id' => 4,
                'photo_id' => 16,
            ),
        ));
    }
}
