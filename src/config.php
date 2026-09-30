<?php

/**
 * Carga las variables de entorno desde el archivo .env ubicado en la raiz
 * del proyecto. Implementacion simple sin dependencias externas.
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        fwrite(STDERR, "No se encontro el archivo .env en: {$path}\n");
        fwrite(STDERR, "Copia .env.example como .env y completa las credenciales.\n");
        exit(1);
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        putenv(trim($key) . '=' . trim($value));
    }
}

loadEnv(__DIR__ . '/../.env');

define('PTP_LOGIN', getenv('PLACETOPAY_LOGIN'));
define('PTP_SECRET_KEY', getenv('PLACETOPAY_SECRET_KEY'));
define('PTP_BASE_URL', getenv('PLACETOPAY_BASE_URL') ?: 'https://checkout-test.placetopay.com');
