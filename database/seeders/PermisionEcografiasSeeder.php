<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermisionEcografiasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permisions = [
            [
                'name' => 'ecografias.index',
            ],
            [
                'name' => 'ecografias.admision',
            ],
            [
                'name' => 'ecografias.anular',
            ],
            [
                'name' => 'ecografias.leer',
            ],
            [
                'name' => 'ecografias.reporte',
            ]
        ];

        foreach ($permisions as $permision) {
            Permission::create($permision);
        }
    }
}
