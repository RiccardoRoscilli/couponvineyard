<?php

/**
 * Script per fare il deploy degli aggiornamenti
 * Accedi a: https://erpcupon.intraweb.it/deploy_updates.php
 */

echo "<h1>Deploy Updates</h1>";
echo "<pre>";

// Cambia directory alla root del progetto
chdir(__DIR__);

echo "=== GIT PULL ===\n";
exec('git pull origin main 2>&1', $output, $return);
echo implode("\n", $output) . "\n";
if ($return !== 0) {
    echo "❌ Errore durante git pull\n";
} else {
    echo "✅ Git pull completato\n";
}
echo "\n";

echo "=== COMPOSER INSTALL ===\n";
exec('composer install --no-dev --optimize-autoloader 2>&1', $output, $return);
echo implode("\n", $output) . "\n";
if ($return !== 0) {
    echo "⚠️  Warning durante composer install\n";
} else {
    echo "✅ Composer install completato\n";
}
echo "\n";

echo "=== CLEAR CACHE ===\n";
exec('php artisan config:clear 2>&1', $output, $return);
echo "Config cache cleared\n";

exec('php artisan route:clear 2>&1', $output, $return);
echo "Route cache cleared\n";

exec('php artisan view:clear 2>&1', $output, $return);
echo "View cache cleared\n";

exec('php artisan cache:clear 2>&1', $output, $return);
echo "Application cache cleared\n";

echo "✅ Cache pulita\n\n";

echo "=== OPTIMIZE ===\n";
exec('php artisan config:cache 2>&1', $output, $return);
echo "Config cached\n";

exec('php artisan route:cache 2>&1', $output, $return);
echo "Routes cached\n";

exec('php artisan view:cache 2>&1', $output, $return);
echo "Views cached\n";

echo "✅ Ottimizzazione completata\n\n";

echo "=== VERIFICA ROUTE DEBUG ===\n";
exec('php artisan route:list | grep debug 2>&1', $output, $return);
if (count($output) > 0) {
    echo implode("\n", $output) . "\n";
    echo "✅ Route debug trovate\n";
} else {
    echo "⚠️  Route debug non trovate\n";
}

echo "\n=== DEPLOY COMPLETATO ===\n";
echo "Ora puoi accedere a: <a href='/debug/expiration'>/debug/expiration</a>\n";

echo "</pre>";
