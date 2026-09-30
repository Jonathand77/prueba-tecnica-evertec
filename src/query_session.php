<?php

/**
 * Consulta el estado final de una sesion de PlacetoPay Web Checkout.
 *
 * Uso:
 *   php src/query_session.php {requestId} {nombre_escenario}
 *
 * Ejemplo:
 *   php src/query_session.php 12345 aprobada
 *
 * El resultado se imprime en pantalla y se guarda como evidencia JSON en
 * evidencias/{escenario}_2_query_session.json
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PlacetoPayClient.php';

$requestId = $argv[1] ?? null;
$scenario = $argv[2] ?? 'resultado';

if (!$requestId || !ctype_digit((string) $requestId)) {
    fwrite(STDERR, "Uso: php src/query_session.php {requestId} {escenario}\n");
    exit(1);
}

$client = new PlacetoPayClient(PTP_LOGIN, PTP_SECRET_KEY, PTP_BASE_URL);

$response = $client->querySession((int) $requestId);

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

$evidenceDir = __DIR__ . '/../evidencias';
if (!is_dir($evidenceDir)) {
    mkdir($evidenceDir, 0777, true);
}

file_put_contents(
    $evidenceDir . "/{$scenario}_2_query_session.json",
    json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

$status = $response['status']['status'] ?? 'DESCONOCIDO';
fwrite(STDERR, "\n>> Estado final de la sesion: {$status}\n");
