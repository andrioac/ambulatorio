<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(CatalogoAutorizacaoSeeder::class);

        if (app()->environment('local')) {
            User::factory()->create([
                'name' => 'Administrador local',
                'email' => 'admin@ambulatorio.local',
                'password' => 'Halegria1234!',
                'ativo' => true,
            ]);
        }
    }
}
