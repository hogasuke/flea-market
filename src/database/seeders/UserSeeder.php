<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        User::factory()->count(9)->create();
    }
}
