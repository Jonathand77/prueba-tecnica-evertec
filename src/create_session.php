<?php

/**
 * Crea una sesion de pago de PlacetoPay Web Checkout para uno de los tres
 * escenarios de prueba solicitados: aprobada, pendiente o rechazada.
 *
 * Uso:
 *   php src/create_session.php aprobada
 *   php src/create_session.php pendiente
 *   php src/create_session.php rechazada
 *
 * El resultado (incluyendo el processUrl al que debe redirigirse el
 * comprador) se imprime en pantalla y se guarda como evidencia JSON en
 * evidencias/{escenario}_1_create_session.json
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PlacetoPayClient.php';

$scenario = $argv[1] ?? null;

$scenarios = [
    'aprobada' => [
        'reference' => 'PRUEBA-APROBADA-' . date('YmdHis'),
        'description' => 'Prueba tecnica Evertec - escenario APROBADO',
        'currency' => 'COP',
        'amount' => 55000,
    ],
    'pendiente' => [
        'reference' => 'PRUEBA-PENDIENTE-' . date('YmdHis'),
        'description' => 'Prueba tecnica Evertec - escenario PENDIENTE',
        'currency' => 'COP',
        'amount' => 55000,
    ],
    'rechazada' => [
        'reference' => 'PRUEBA-RECHAZADA-' . date('YmdHis'),
        'description' => 'Prueba tecnica Evertec - escenario RECHAZADO',
        'currency' => 'COP',
        'amount' => 55000,
    ],
];

if (!$scenario || !isset($scenarios[$scenario])) {
    fwrite(STDERR, "Uso: php src/create_session.php [aprobada|pendiente|rechazada]\n");
    exit(1);
}

$client = new PlacetoPayClient(PTP_LOGIN, PTP_SECRET_KEY, PTP_BASE_URL);

$returnUrl = 'https://www.google.com/?returnUrl-prueba-tecnica-evertec';

$response = $client->createSession($scenarios[$scenario], $returnUrl);

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

$evidenceDir = __DIR__ . '/../evidencias';
if (!is_dir($evidenceDir)) {
    mkdir($evidenceDir, 0777, true);
}

file_put_contents(
    $evidenceDir . "/{$scenario}_1_create_session.json",
    json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

if (isset($response['processUrl'])) {
    fwrite(STDERR, "\n>> processUrl para completar el pago en el navegador:\n");
    fwrite(STDERR, $response['processUrl'] . "\n");
}
if (isset($response['requestId'])) {
    fwrite(STDERR, "\n>> requestId (usalo luego con query_session.php): " . $response['requestId'] . "\n");
}
