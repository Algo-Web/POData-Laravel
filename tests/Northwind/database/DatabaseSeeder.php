<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        foreach (glob(__DIR__ . '/seeds/*.php') as $filename) {
            require_once($filename);
        }
        // $this->call(UserTableSeeder::class);
        //DB::statement('SET FOREIGN_KEY_CHECKS=0;');
//        $this->call('CustomerTableSeeder');
        $this->call('PhotoTableSeeder');
//        $this->call('ProductTableSeeder');
//        $this->call('StaffTableSeeder');
//        $this->call('PasswordResetsTableSeeder');
//        $this->call('UsersTableSeeder');
        $this->call('CustomersTableSeeder');
        $this->call('EmployeesTableSeeder');
        $this->call('InventoryTransactionTypesTableSeeder');
        $this->call('OrderDetailsTableSeeder');
        $this->call('OrderDetailsStatusTableSeeder');
        $this->call('OrdersTableSeeder');
        $this->call('InvoicesTableSeeder');
        $this->call('OrdersTableSeeder');
        $this->call('OrderDetailsTableSeeder');
        $this->call('OrdersStatusTableSeeder');
        $this->call('OrderDetailsTableSeeder');

        $this->call('OrdersTaxStatusTableSeeder');
        $this->call('PrivilegesTableSeeder');
        $this->call('EmployeePrivilegesTableSeeder');
        $this->call('ProductsTableSeeder');
        $this->call('InventoryTransactionsTableSeeder');
        $this->call('PurchaseOrderDetailsTableSeeder');

        $this->call('PurchaseOrderStatusTableSeeder');
        $this->call('PurchaseOrdersTableSeeder');
        $this->call('SalesReportsTableSeeder');
        $this->call('ShippersTableSeeder');
        $this->call('StringsTableSeeder');
        $this->call('SuppliersTableSeeder');
        //DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Model::reguard();
    }
}
