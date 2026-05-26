<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Dashboard\DashBoardController;

$controller = app()->make(DashBoardController::class);

$user = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Administrador'))->first();
if ($user) auth()->login($user);
echo "Logged in: " . ($user->name ?? 'NONE') . "\n\n";

// Profile individual methods using reflection
$ref = new ReflectionClass($controller);

$days = 30;
$scope = ['medico_id' => null, 'sede_id' => null];

$methods = ['getIndicadores', 'getOperacion', 'getSeries', 'getCalidad', 'getEstudiosMasSolicitados', 'getTablas', 'getProductividad', 'getSla'];

foreach ($methods as $method) {
    $m = $ref->getMethod($method);
    $m->setAccessible(true);

    $start = microtime(true);
    $params = $method === 'getOperacion' ? [$scope] : ($method === 'getTablas' ? [$scope] : [$scope, $days]);
    $result = $m->invokeArgs($controller, $params);
    $elapsed = round((microtime(true) - $start) * 1000, 1);

    $desc = match($method) {
        'getIndicadores' => 'estudiosAsignados=' . ($result['estudiosAsignados'] ?? '?'),
        'getOperacion' => 'pendLectura=' . ($result['pendientesLectura'] ?? '?'),
        'getSeries' => 'asignados=' . count($result['asignadosPorDia'] ?? []) . 'd, ' . count($result['porHoraHoy']['asignados'] ?? []) . 'h',
        'getCalidad' => 'agingLectura=' . count($result['agingLectura'] ?? []) . ' items',
        'getEstudiosMasSolicitados' => count($result) . ' items',
        'getTablas' => 'pendLecturaTop=' . count($result['pendientesLecturaTop'] ?? []) . ' items',
        'getProductividad' => 'ranking=' . count($result['rankingMedicosTranscritos'] ?? []) . ' items',
        'getSla' => 'lectura=' . count($result['lectura'] ?? []) . ' items',
    };

    printf("  %-30s %8.1fms  %s\n", $method, $elapsed, $desc);
}
echo "\n";

// Full dashboard test (cached)
$start = microtime(true);
$request = new Request();
$request->merge(['days' => 30]);
$response = $controller->dashboard($request);
$data = $response->getData(true);
$elapsed_total = round(microtime(true) - $start, 2);
echo "Full dashboard (cache-enabled): {$elapsed_total}s\n";

// Clear cache and test without cache
Cache::flush();
$start = microtime(true);
$response = $controller->dashboard($request);
$elapsed_nocache = round(microtime(true) - $start, 2);
echo "Full dashboard (no cache):      {$elapsed_nocache}s\n";

echo "\nDONE\n";
