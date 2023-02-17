<?php

namespace Database\Seeders\Seguridad;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'num_docu' => 'Administrator',
            'name' => 'Administrator',
            'tipo_user' => 'Administrador',
            'email' => 'administrador@administrador.com',
            'password' => bcrypt('123456')
        ])->assignRole('Admintrador', 'Medico', 'Administrativo', 'Transcriptor', 'Estaditico');

        User::create([
            'num_docu' => 'Medico 1',
            'name' => 'Medico 1',
            'tipo_user' => 'Medico',
            'email' => 'medico1@medico1.com',
            'password' => bcrypt('123456')
        ])->assignRole('Medico');

        User::create([
            'num_docu' => 'Medico 2',
            'name' => 'Medico 2',
            'tipo_user' => 'Medico',
            'email' => 'medico2@medico2.com',
            'password' => bcrypt('123456')
        ])->assignRole('Medico');

        User::create([
            'num_docu' => 'Medico 3',
            'name' => 'Medico 3',
            'tipo_user' => 'Medico',
            'email' => 'medico3@medico3.com',
            'password' => bcrypt('123456')
        ])->assignRole('Medico');
    }
}
