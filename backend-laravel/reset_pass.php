<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = Illuminate\Support\Facades\DB::table('usuarios')->where('rol', 'superadmin')->first();
if(!$user) {
    echo "NO SUPERADMIN FOUND\n";
} else {
    echo "SUPERADMIN FOUND: " . $user->correo . "\n";
    Illuminate\Support\Facades\DB::table('usuarios')->where('id', $user->id)->update(['password' => password_hash('12345678', PASSWORD_BCRYPT)]);
    echo "PASSWORD SET TO 12345678\n";
}
