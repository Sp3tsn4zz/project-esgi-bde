<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@bde.fr'],
            [
                'name' => 'Admin BDE',
                'password' => Hash::make('changeme123'),
                'role' => 'admin',
            ]
        );

        $this->command->info('Compte BDE créé : admin@bde.fr / changeme123 (à changer après la première connexion)');
    }
}
