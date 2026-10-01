// Interacción del carrito: stepper +/-, total en vivo y micro-animaciones.
// El monto mostrado aquí es solo informativo — checkout.php SIEMPRE
// recalcula el total real en el servidor a partir del catálogo.
(function () {
  const productos = document.querySelectorAll('.producto');
  const totalEl = document.getElementById('total-valor');
  const btnPagar = document.getElementById('btn-pagar');
  const formatoCOP = new Intl.NumberFormat('es-CO');

  function leerCantidad(input) {
    return Math.max(0, Math.min(10, parseInt(input.value, 10) || 0));
  }

  function actualizarTotal() {
    let total = 0;
    let items = 0;

    productos.forEach((producto) => {
      const input = producto.querySelector('.stepper input');
      const cantidad = leerCantidad(input);
      const precio = parseInt(input.dataset.precio, 10);
      total += precio * cantidad;
      items += cantidad;
      producto.classList.toggle('en-carrito', cantidad > 0);
    });

    totalEl.textContent = '$' + formatoCOP.format(total) + ' COP';
    totalEl.classList.remove('pulso');
    // Forzar reflow para poder reiniciar la animación en clics seguidos.
    void totalEl.offsetWidth;
    totalEl.classList.add('pulso');

    btnPagar.disabled = total <= 0;
    btnPagar.textContent = items > 0
      ? `Pagar con PlacetoPay · ${items} artículo${items === 1 ? '' : 's'}`
      : 'Agrega productos al carrito';
  }

  productos.forEach((producto) => {
    const input = producto.querySelector('.stepper input');
    const [btnMenos, btnMas] = producto.querySelectorAll('.stepper button');

    btnMenos.addEventListener('click', () => {
      input.value = leerCantidad(input) - 1;
      actualizarTotal();
    });
    btnMas.addEventListener('click', () => {
      input.value = leerCantidad(input) + 1;
      actualizarTotal();
    });
    input.addEventListener('input', actualizarTotal);
  });

  actualizarTotal();
})();
