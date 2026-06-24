<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\Usuario::find(11);
echo "Before: " . $user->nombre . "\n";
$user->nombre = 'Gabriel Modificado';
$saved = $user->save();
echo "Saved: " . ($saved ? 'Yes' : 'No') . "\n";

$user2 = App\Models\Usuario::find(11);
echo "After: " . $user2->nombre . "\n";
