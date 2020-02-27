<?php declare(strict_types=1);

use Illuminate\Database\Seeder;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Customer;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Employee;

class PhotoTableSeeder extends Seeder
{

    /**
     * Auto generated seed file.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('photos')->delete();

        \DB::table('photos')->insert(array(
            0 =>
                array(
                    'id' => 1,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Employee::class,
                    'rel_id' => 1,
                ),
            1 =>
                array(
                    'id' => 2,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => 'Staff',
                    'rel_id' => 2,
                ),
            2 =>
                array(
                    'id' => 3,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Employee::class,
                    'rel_id' => 3,
                ),
            3 =>
                array(
                    'id' => 4,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Employee::class,
                    'rel_id' => 4,
                ),
            4 =>
                array(
                    'id' => 5,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 1,
                ),
            5 =>
                array(
                    'id' => 6,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 2,
                ),
            6 =>
                array(
                    'id' => 7,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 3,
                ),
            7 =>
                array(
                    'id' => 8,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 4,
                ),
            8 =>
                array(
                    'id' => 9,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 5,
                ),
            9 =>
                array(
                    'id' => 10,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 6,
                ),
            10 =>
                array(
                    'id' => 11,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 7,
                ),
            11 =>
                array(
                    'id' => 12,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 8,
                ),
            12 =>
                array(
                    'id' => 13,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 9,
                ),
            13 =>
                array(
                    'id' => 14,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 10,
                ),
            14 =>
                array(
                    'id' => 15,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 11,
                ),
            15 =>
                array(
                    'id' => 16,
                    'content' => file_get_contents(__DIR__ . '/test_image.jpg'),
                    'rel_type' => Customer::class,
                    'rel_id' => 12,
                ),
        ));
    }
}
