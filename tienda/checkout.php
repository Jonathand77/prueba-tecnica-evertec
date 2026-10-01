<?php

/**
 * Recibe el carrito enviado desde index.php, calcula el total en el
 * servidor (nunca confiando en datos del navegador), crea la sesión de
 * pago en PlacetoPay y redirige al comprador al "processUrl" devuelto.
 */

session_start();
require_once __DIR__ . '/productos.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/PlacetoPayClient.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$catalogo = catalogoProductos();
$cantidades = $_POST['cantidad'] ?? [];

$total = 0;
$itemsCarrito = [];

foreach ($cantidades as $id => $cantidad) {
    $cantidad = (int) $cantidad;
    if ($cantidad <= 0 || !isset($catalogo[$id])) {
        continue;
    }
    // El precio SIEMPRE sale del catálogo del servidor, no del formulario.
    $precioUnitario = $catalogo[$id]['precio'];
    $subtotal = $precioUnitario * $cantidad;
    $total += $subtotal;

    $itemsCarrito[] = [
        'nombre' => $catalogo[$id]['nombre'],
        'cantidad' => $cantidad,
        'subtotal' => $subtotal,
    ];
}

if ($total <= 0) {
    header('Location: index.php?error=carrito_vacio');
    exit;
}

$descripcion = implode(', ', array_map(
    fn($item) => "{$item['cantidad']}x {$item['nombre']}",
    $itemsCarrito
));

$referencia = 'TIENDA-' . date('YmdHis') . '-' . random_int(100, 999);

$client = new PlacetoPayClient(PTP_LOGIN, PTP_SECRET_KEY, PTP_BASE_URL);

// La URL a la que PlacetoPay redirige al comprador al terminar el pago.
$esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$returnUrl = $esquema . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/resultado.php';

try {
    $response = $client->createSession([
        'reference' => $referencia,
        'description' => $descripcion,
        'currency' => 'COP',
        'amount' => $total,
        'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'TiendaDemo/1.0',
    ], $returnUrl);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error al crear la sesión de pago: ' . htmlspecialchars($e->getMessage());
    exit;
}

// Se guarda el requestId y el resumen del carrito para mostrarlos en
// resultado.php cuando el comprador vuelva de PlacetoPay.
$_SESSION['ultima_orden'] = [
    'referencia' => $referencia,
    'items' => $itemsCarrito,
    'total' => $total,
    'requestId' => $response['requestId'] ?? null,
];

if (!empty($response['processUrl'])) {
    header('Location: ' . $response['processUrl']);
    exit;
}

http_response_code(502);
echo 'PlacetoPay no devolvió una URL de pago. Respuesta: ' . htmlspecialchars(json_encode($response));
