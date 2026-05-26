<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Add missing composite indexes
echo "Adding indexes...\n";

try {
    Illuminate\Support\Facades\DB::statement("CREATE INDEX idx_ep_activo_trascrito ON estudios_productos (activo, fechor_trascrito)");
    echo "  idx_ep_activo_trascrito: OK\n";
} catch (Throwable $e) {
    echo "  idx_ep_activo_trascrito: " . $e->getMessage() . "\n";
}

try {
    Illuminate\Support\Facades\DB::statement("CREATE INDEX idx_ep_activo_lectura ON estudios_productos (activo, fechor_lectura)");
    echo "  idx_ep_activo_lectura: OK\n";
} catch (Throwable $e) {
    echo "  idx_ep_activo_lectura: " . $e->getMessage() . "\n";
}

try {
    Illuminate\Support\Facades\DB::statement("CREATE INDEX idx_ep_activo_estudio ON estudios_productos (activo, estudio_id)");
    echo "  idx_ep_activo_estudio: OK\n";
} catch (Throwable $e) {
    echo "  idx_ep_activo_estudio: " . $e->getMessage() . "\n";
}

echo "Done!\n";
