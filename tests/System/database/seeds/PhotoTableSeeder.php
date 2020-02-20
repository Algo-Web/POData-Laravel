<?php

use Illuminate\Database\Seeder;

class PhotoTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('photo')->delete();

        \DB::table('photo')->insert(array(
            0 =>
                array(
                    'id' => 1,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Staff',
                    'rel_id' => 1,
                ),
            1 =>
                array(
                    'id' => 2,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Staff',
                    'rel_id' => 2,
                ),
            2 =>
                array(
                    'id' => 3,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Staff',
                    'rel_id' => 3,
                ),
            3 =>
                array(
                    'id' => 4,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Staff',
                    'rel_id' => 4,
                ),
            4 =>
                array(
                    'id' => 5,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 1,
                ),
            5 =>
                array(
                    'id' => 6,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 2,
                ),
            6 =>
                array(
                    'id' => 7,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 3,
                ),
            7 =>
                array(
                    'id' => 8,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 4,
                ),
            8 =>
                array(
                    'id' => 9,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 5,
                ),
            9 =>
                array(
                    'id' => 10,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 6,
                ),
            10 =>
                array(
                    'id' => 11,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 7,
                ),
            11 =>
                array(
                    'id' => 12,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 8,
                ),
            12 =>
                array(
                    'id' => 13,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 9,
                ),
            13 =>
                array(
                    'id' => 14,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 10,
                ),
            14 =>
                array(
                    'id' => 15,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 11,
                ),
            15 =>
                array(
                    'id' => 16,
                    'content' => file_get_contents("test_image.jpg"),
                    'rel_type' => 'Customers',
                    'rel_id' => 12,
                ),
        ));
    }
}
