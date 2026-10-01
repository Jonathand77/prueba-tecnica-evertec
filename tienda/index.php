<?php
require_once __DIR__ . '/productos.php';
$productos = catalogoProductos();
$error = $_GET['error'] ?? null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tienda Demo — PlacetoPay</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="wrap">
    <header class="tienda">
      <div class="marca">
        <span class="eyebrow">Tienda demo</span>
        <h1>Place to Pay</h1>
      </div>
      <span class="badge">🔒 Pagos con PlacetoPay</span>
    </header>

    <?php if ($error === 'carrito_vacio'): ?>
      <p class="aviso" style="margin-bottom:18px;color:var(--danger)">
        Agrega al menos un producto antes de pagar.
      </p>
    <?php endif; ?>

    <form action="checkout.php" method="post" id="carrito-form">
      <div class="productos">
        <?php foreach ($productos as $id => $p): ?>
          <div class="producto">
            <div class="icono"><?= $p['emoji'] ?></div>
            <div class="info">
              <div class="nombre"><?= htmlspecialchars($p['nombre']) ?></div>
              <div class="descripcion"><?= htmlspecialchars($p['descripcion']) ?></div>
              <div class="precio">$<?= number_format($p['precio'], 0, ',', '.') ?> COP</div>
            </div>
            <div class="stepper">
              <button type="button" aria-label="Quitar uno de <?= htmlspecialchars($p['nombre']) ?>">−</button>
              <input
                type="number"
                name="cantidad[<?= $id ?>]"
                data-precio="<?= $p['precio'] ?>"
                min="0"
                max="10"
                value="0"
                inputmode="numeric"
                aria-label="Cantidad de <?= htmlspecialchars($p['nombre']) ?>"
              >
              <button type="button" aria-label="Agregar uno de <?= htmlspecialchars($p['nombre']) ?>">+</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="carrito-bar">
        <div class="panel">
          <div class="resumen">
            <div class="total-label">Total del carrito</div>
            <div class="total-valor" id="total-valor">$0 COP</div>
          </div>
          <button type="submit" class="pagar" id="btn-pagar" disabled>Agrega productos al carrito</button>
        </div>
      </div>
    </form>

    <p class="aviso">Ambiente de pruebas — no se realiza ningún cobro real.</p>
  </div>

  <script src="assets/cart.js"></script>
</body>
</html>
