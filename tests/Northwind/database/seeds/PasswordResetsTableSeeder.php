<?php

declare(strict_types=1);

use Illuminate\Database\Seeder;

class PasswordResetsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('password_resets')->delete();
    }
}
