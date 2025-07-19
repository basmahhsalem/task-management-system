<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            ['username' => 'Manager1', 'role_id' => 1, 'password' => bcrypt('123')],
            ['username' => 'Employee1', 'role_id' => 2, 'password' => bcrypt('123')],
            ['username' => 'Employee2', 'role_id' => 2, 'password' => bcrypt('123')],
            ['username' => 'Employee3', 'role_id' => 2, 'password' => bcrypt('123')],

        ]);
    }
}
