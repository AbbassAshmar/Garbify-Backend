<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateAnonymousUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anonymousUser = User::create([
            'name'=>'anonymous',
            'email'=>'anonymousUserEmail4320@gmail.com',
            'password'=>env('ANONYMOUS_USER_PASS')
        ]);
        $anonymousUser->assignRole('anonymous');    
    }
}
