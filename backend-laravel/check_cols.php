<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$colsBodegas = Illuminate\Support\Facades\DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'bodegas'");
echo "BODEGAS COLS: ";
foreach($colsBodegas as $c) echo $c->column_name . ", ";
echo "\n";

$colsProv = Illuminate\Support\Facades\DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'proveedores'");
echo "PROV COLS: ";
foreach($colsProv as $c) echo $c->column_name . ", ";
echo "\n";

$b = Illuminate\Support\Facades\DB::table('bodegas')->get();
echo "BODEGAS DATA COUNT: " . count($b) . "\n";
foreach($b as $x) print_r($x);

$p = Illuminate\Support\Facades\DB::table('proveedores')->get();
echo "PROVEEDORES DATA COUNT: " . count($p) . "\n";
foreach($p as $x) print_r($x);
