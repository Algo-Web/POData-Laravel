<?php

use Illuminate\Database\Seeder;

class ShippersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('shippers')->delete();
        
        \DB::table('shippers')->insert(
            [
            0 =>
            [
                'id' => 1,
                'company' => 'Shipping Company A',
                'last_name' => null,
                'first_name' => null,
                'email_address' => null,
                'job_title' => null,
                'business_phone' => null,
                'home_phone' => null,
                'mobile_phone' => null,
                'fax_number' => null,
                'address' => '123 Any Street',
                'city' => 'Memphis',
                'state_province' => 'TN',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => null,
                'notes' => null,
                'attachments' => '',
            ],
            1 =>
            [
                'id' => 2,
                'company' => 'Shipping Company B',
                'last_name' => null,
                'first_name' => null,
                'email_address' => null,
                'job_title' => null,
                'business_phone' => null,
                'home_phone' => null,
                'mobile_phone' => null,
                'fax_number' => null,
                'address' => '123 Any Street',
                'city' => 'Memphis',
                'state_province' => 'TN',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => null,
                'notes' => null,
                'attachments' => '',
            ],
            2 =>
            [
                'id' => 3,
                'company' => 'Shipping Company C',
                'last_name' => null,
                'first_name' => null,
                'email_address' => null,
                'job_title' => null,
                'business_phone' => null,
                'home_phone' => null,
                'mobile_phone' => null,
                'fax_number' => null,
                'address' => '123 Any Street',
                'city' => 'Memphis',
                'state_province' => 'TN',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => null,
                'notes' => null,
                'attachments' => '',
            ],
            ]
        );
    }
}
