<?php

/**
 * Página a la que PlacetoPay redirige al comprador (returnUrl) al terminar
 * el flujo de pago. Consulta el estado final de la sesión en la API
 * (POST /api/session/{requestId}) y lo muestra de forma clara.
 */

session_start();
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/PlacetoPayClient.php';

$orden = $_SESSION['ultima_orden'] ?? null;

$estado = null;
$mensaje = '';
$error = null;

if ($orden && !empty($orden['requestId'])) {
    try {
        $client = new PlacetoPayClient(PTP_LOGIN, PTP_SECRET_KEY, PTP_BASE_URL);
        $consulta = $client->querySession((int) $orden['requestId']);
        $estado = $consulta['status']['status'] ?? 'DESCONOCIDO';
        $mensaje = $consulta['status']['message'] ?? '';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
} else {
    $error = 'No se encontró información de una orden reciente en esta sesión del navegador.';
}

$presentacion = [
    'APPROVED' => ['clase' => 'approved', 'icono' => '✓', 'titulo' => '¡Pago aprobado!'],
    'PENDING'  => ['clase' => 'pending', 'icono' => '↻', 'titulo' => 'Tu pago está pendiente'],
    'REJECTED' => ['clase' => 'rejected', 'icono' => '✕', 'titulo' => 'El pago fue rechazado'],
];
$p = $presentacion[$estado] ?? ['clase' => 'otro', 'icono' => 'i', 'titulo' => $estado ?? 'Sin información'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Resultado del pago — Tienda Demo</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="wrap">
    <header class="tienda">
      <div class="marca">
        <span class="eyebrow">Tienda demo</span>
        <h1>Buen Gusto &amp; Co.</h1>
      </div>
      <span class="badge">🔒 Pagos con PlacetoPay</span>
    </header>

    <div class="resultado <?= $error ? 'otro' : $p['clase'] ?>">
      <?php if ($error): ?>
        <div class="icono-estado"><span class="icono">!</span></div>
        <h2>No se pudo obtener el resultado</h2>
        <p class="mensaje"><?= htmlspecialchars($error) ?></p>
      <?php else: ?>
        <div class="icono-estado"><span class="icono"><?= $p['icono'] ?></span></div>
        <h2><?= htmlspecialchars($p['titulo']) ?></h2>
        <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
        <span class="chip-estado <?= $p['clase'] ?>"><?= htmlspecialchars($estado) ?></span>

        <div class="detalle">
          <dl>
            <dt>Referencia</dt><dd><?= htmlspecialchars($orden['referencia']) ?></dd>
            <dt>requestId</dt><dd><?= htmlspecialchars((string) $orden['requestId']) ?></dd>
            <dt>Total</dt><dd>$<?= number_format($orden['total'], 0, ',', '.') ?> COP</dd>
            <?php foreach ($orden['items'] as $item): ?>
              <dt>Artículo</dt><dd><?= htmlspecialchars($item['cantidad'] . 'x ' . $item['nombre']) ?></dd>
            <?php endforeach; ?>
          </dl>
        </div>
      <?php endif; ?>

      <a class="volver" href="index.php">&larr; Volver a la tienda</a>
    </div>
  </div>
</body>
</html>
