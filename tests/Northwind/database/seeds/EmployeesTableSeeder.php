<?php

declare(strict_types=1);

use Illuminate\Database\Seeder;

class EmployeesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('employees')->delete();

        \DB::table('employees')->insert(array(
            0 =>
            array(
                'id' => 1,
                'company' => 'Northwind Traders',
                'last_name' => 'Freehafer',
                'first_name' => 'Nancy',
                'email_address' => 'nancy@northwindtraders.com',
                'job_title' => 'Sales Representative',
                'business_phone' => '(123)555-0100',
                'home_phone' => '(123)555-0102',
                'mobile_phone' => null,
                'fax_number' => '(123)555-0103',
                'address' => '123 1st Avenue',
                'city' => 'Seattle',
                'state_province' => 'WA',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => '#http://northwindtraders.com#',
                'notes' => null,
                'attachments' => '',
            ),
            1 =>
            array(
                'id' => 2,
                'company' => 'Northwind Traders',
                'last_name' => 'Cencini',
                'first_name' => 'Andrew',
                'email_address' => 'andrew@northwindtraders.com',
                'job_title' => 'Vice President, Sales',
                'business_phone' => '(123)555-0100',
                'home_phone' => '(123)555-0102',
                'mobile_phone' => null,
                'fax_number' => '(123)555-0103',
                'address' => '123 2nd Avenue',
                'city' => 'Bellevue',
                'state_province' => 'WA',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => 'http://northwindtraders.com#http://northwindtraders.com/#',
                'notes' => 'Joined the company as a sales representative, was promoted to sales manager and was'
                           .' then named vice president of sales.',
                'attachments' => '',
            ),
            2 =>
            array(
                'id' => 3,
                'company' => 'Northwind Traders',
                'last_name' => 'Kotas',
                'first_name' => 'Jan',
                'email_address' => 'jan@northwindtraders.com',
                'job_title' => 'Sales Representative',
                'business_phone' => '(123)555-0100',
                'home_phone' => '(123)555-0102',
                'mobile_phone' => null,
                'fax_number' => '(123)555-0103',
                'address' => '123 3rd Avenue',
                'city' => 'Redmond',
                'state_province' => 'WA',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => 'http://northwindtraders.com#http://northwindtraders.com/#',
                'notes' => 'Was hired as a sales associate and was promoted to sales representative.',
                'attachments' => '',
            ),
            3 =>
            array(
                'id' => 4,
                'company' => 'Northwind Traders',
                'last_name' => 'Sergienko',
                'first_name' => 'Mariya',
                'email_address' => 'mariya@northwindtraders.com',
                'job_title' => 'Sales Representative',
                'business_phone' => '(123)555-0100',
                'home_phone' => '(123)555-0102',
                'mobile_phone' => null,
                'fax_number' => '(123)555-0103',
                'address' => '123 4th Avenue',
                'city' => 'Kirkland',
                'state_province' => 'WA',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => 'http://northwindtraders.com#http://northwindtraders.com/#',
                'notes' => null,
                'attachments' => '',
            ),
            4 =>
            array(
                'id' => 5,
                'company' => 'Northwind Traders',
                'last_name' => 'Thorpe',
                'first_name' => 'Steven',
                'email_address' => 'steven@northwindtraders.com',
                'job_title' => 'Sales Manager',
                'business_phone' => '(123)555-0100',
                'home_phone' => '(123)555-0102',
                'mobile_phone' => null,
                'fax_number' => '(123)555-0103',
                'address' => '123 5th Avenue',
                'city' => 'Seattle',
                'state_province' => 'WA',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => 'http://northwindtraders.com#http://northwindtraders.com/#',
                'notes' => 'Joined the company as a sales representative and was promoted to sales manager.'
                           .'  Fluent in French.',
                'attachments' => '',
            ),
            5 =>
            array(
                'id' => 6,
                'company' => 'Northwind Traders',
                'last_name' => 'Neipper',
                'first_name' => 'Michael',
                'email_address' => 'michael@northwindtraders.com',
                'job_title' => 'Sales Representative',
                'business_phone' => '(123)555-0100',
                'home_phone' => '(123)555-0102',
                'mobile_phone' => null,
                'fax_number' => '(123)555-0103',
                'address' => '123 6th Avenue',
                'city' => 'Redmond',
                'state_province' => 'WA',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => 'http://northwindtraders.com#http://northwindtraders.com/#',
                'notes' => 'Fluent in Japanese and can read and write French, Portuguese, and Spanish.',
                'attachments' => '',
            ),
            6 =>
            array(
                'id' => 7,
                'company' => 'Northwind Traders',
                'last_name' => 'Zare',
                'first_name' => 'Robert',
                'email_address' => 'robert@northwindtraders.com',
                'job_title' => 'Sales Representative',
                'business_phone' => '(123)555-0100',
                'home_phone' => '(123)555-0102',
                'mobile_phone' => null,
                'fax_number' => '(123)555-0103',
                'address' => '123 7th Avenue',
                'city' => 'Seattle',
                'state_province' => 'WA',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => 'http://northwindtraders.com#http://northwindtraders.com/#',
                'notes' => null,
                'attachments' => '',
            ),
            7 =>
            array(
                'id' => 8,
                'company' => 'Northwind Traders',
                'last_name' => 'Giussani',
                'first_name' => 'Laura',
                'email_address' => 'laura@northwindtraders.com',
                'job_title' => 'Sales Coordinator',
                'business_phone' => '(123)555-0100',
                'home_phone' => '(123)555-0102',
                'mobile_phone' => null,
                'fax_number' => '(123)555-0103',
                'address' => '123 8th Avenue',
                'city' => 'Redmond',
                'state_province' => 'WA',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => 'http://northwindtraders.com#http://northwindtraders.com/#',
                'notes' => 'Reads and writes French.',
                'attachments' => '',
            ),
            8 =>
            array(
                'id' => 9,
                'company' => 'Northwind Traders',
                'last_name' => 'Hellung-Larsen',
                'first_name' => 'Anne',
                'email_address' => 'anne@northwindtraders.com',
                'job_title' => 'Sales Representative',
                'business_phone' => '(123)555-0100',
                'home_phone' => '(123)555-0102',
                'mobile_phone' => null,
                'fax_number' => '(123)555-0103',
                'address' => '123 9th Avenue',
                'city' => 'Seattle',
                'state_province' => 'WA',
                'zip_postal_code' => '99999',
                'country_region' => 'USA',
                'web_page' => 'http://northwindtraders.com#http://northwindtraders.com/#',
                'notes' => 'Fluent in French and German.',
                'attachments' => '',
            ),
        ));
    }
}
