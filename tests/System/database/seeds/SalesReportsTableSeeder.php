<?php

use Illuminate\Database\Seeder;

class SalesReportsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('sales_reports')->delete();
        
        \DB::table('sales_reports')->insert(
            [
            0 =>
            [
                'group_by' => 'Category',
                'display' => 'Category',
                'title' => 'Sales By Category',
                'filter_row_source' => 'SELECT DISTINCT [Category] FROM [products] ORDER BY [Category];',
                'default' => 0,
            ],
            1 =>
            [
                'group_by' => 'country_region',
                'display' => 'Country/Region',
                'title' => 'Sales By Country',
                'filter_row_source' => 'SELECT DISTINCT [country_region] FROM [customers Extended] ORDER BY [country_region];',
                'default' => 0,
            ],
            2 =>
            [
                'group_by' => 'Customer ID',
                'display' => 'Customer',
                'title' => 'Sales By Customer',
                'filter_row_source' => 'SELECT DISTINCT [Company] FROM [customers Extended] ORDER BY [Company];',
                'default' => 0,
            ],
            3 =>
            [
                'group_by' => 'employee_id',
                'display' => 'Employee',
                'title' => 'Sales By Employee',
                'filter_row_source' => 'SELECT DISTINCT [Employee Name] FROM [employees Extended] ORDER BY [Employee Name];',
                'default' => 0,
            ],
            4 =>
            [
                'group_by' => 'Product ID',
                'display' => 'Product',
                'title' => 'Sales by Product',
                'filter_row_source' => 'SELECT DISTINCT [Product Name] FROM [products] ORDER BY [Product Name];',
                'default' => 1,
            ],
            ]
        );
    }
}
