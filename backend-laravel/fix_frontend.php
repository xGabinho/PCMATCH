<?php
$content = file_get_contents('Frontend/src/views/SuperAdminView.vue');

$replacements = [
    'await fetchProveedores(); await fetchHistorial()' => 'Promise.all([fetchProveedores(), fetchHistorial()])',
    'await fetchBodegas(); await fetchHistorial()' => 'Promise.all([fetchBodegas(), fetchHistorial()])',
    'await fetchUsuarios(); await fetchHistorial()' => 'Promise.all([fetchUsuarios(), fetchHistorial()])',
    'await fetchComponentes(); await fetchHistorial()' => 'Promise.all([fetchComponentes(), fetchHistorial()])'
];

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

file_put_contents('Frontend/src/views/SuperAdminView.vue', $content);
echo "Done\n";
