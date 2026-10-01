<?php

/**
 * Catálogo de productos de la tienda demo.
 *
 * Se mantiene como la única fuente de verdad de los precios: tanto
 * index.php (para mostrarlos) como checkout.php (para calcular el total
 * que se cobra) leen de aquí. checkout.php NUNCA confía en un precio que
 * venga del formulario — solo en la cantidad — para evitar que alguien
 * manipule el monto a pagar desde el navegador.
 */
function catalogoProductos(): array
{
    return [
        'camiseta' => [
            'nombre' => 'Camiseta',
            'descripcion' => 'Algodón peinado, corte unisex',
            'precio' => 45000,
            'emoji' => '👕',
        ],
        'taza' => [
            'nombre' => 'Taza',
            'descripcion' => 'Cerámica, 350 ml, apta microondas',
            'precio' => 25000,
            'emoji' => '☕',
        ],
        'gorra' => [
            'nombre' => 'Gorra',
            'descripcion' => 'Ajustable, bordado frontal',
            'precio' => 35000,
            'emoji' => '🧢',
        ],
    ];
}
