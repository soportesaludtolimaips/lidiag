<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Spatie\Permission\Models\Permission;

$actions = ['index', 'create', 'edit', 'destroy'];
foreach ($actions as $action) {
    Permission::create(['name' => 'config.plantillas.tipos.'.$action, 'guard_name' => 'web']);
    Permission::create(['name' => 'config.recomendaciones.'.$action, 'guard_name' => 'web']);
}
echo "Permissions created successfully\n";
