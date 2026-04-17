<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Probando conexión a la base de datos...\n";
    $count = App\Models\Tarifa::count();
    echo "Tarifas encontradas: " . $count . "\n";

    $tarifas = App\Models\Tarifa::all();
    echo "Listado:\n";
    foreach ($tarifas as $tarifa) {
        echo "ID: {$tarifa->id}, Nombre: {$tarifa->nombre}, Status: {$tarifa->status}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
