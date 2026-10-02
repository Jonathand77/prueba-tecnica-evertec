# Prueba Técnica — Evertec / PlacetoPay

**Cargo:** Analista de Implementación

Este repositorio contiene la solución de la prueba técnica. Cada punto del
enunciado tiene su propio documento con el detalle completo (contexto,
código, parámetros y evidencia).

## Contenido

| Punto | Documento | Descripción |
|---|---|---|
| 1 | [`SOLUCION_PUNTO_1.md`](SOLUCION_PUNTO_1.md) | Integración con **PlacetoPay Web Checkout**: flujo de pago básico, autenticación, parámetros mínimos y evidencia real de los 3 resultados transaccionales (aprobado, pendiente, rechazado). |
| 2 | [`SOLUCION_PUNTO_2.md`](SOLUCION_PUNTO_2.md) | Conceptos básicos: `requestId`, estados de una transacción, preautorización, diferencias entre cobro por suscripción (token) y por recurrencia (AutoPay), API Gateway vs Web Checkout, dispersión y notificación (webhook). |
| 3 | [`SOLUCION_PUNTO_3.md`](SOLUCION_PUNTO_3.md) | Gestión de 4 casos de comercios: diagnóstico, gestión interna y respuesta al cliente para errores de autenticación (102 y "mal formada") y para un comercio con dificultades de comprensión y alta insatisfacción. |
| 4 | [`SOLUCION_PUNTO_4.md`](SOLUCION_PUNTO_4.md) | Diagramas de flujo del proceso de pago (Usuario final – Comercio – PlacetoPay) para **Web Checkout** y **API Gateway**, con una comparación rápida entre ambos. |

## Estructura del proyecto

```
├── README.md                 Este índice
├── SOLUCION_PUNTO_1.md         Solución del Punto 1: integración Web Checkout
├── SOLUCION_PUNTO_2.md         Solución del Punto 2: conceptos básicos
├── SOLUCION_PUNTO_3.md         Solución del Punto 3: gestión de casos de comercios
├── SOLUCION_PUNTO_4.md         Solución del Punto 4: diagramas de flujo del pago
├── .env.example               Plantilla de credenciales
├── tienda/                     Tienda demo del Punto 1: catálogo + carrito + pago
├── src/                        Cliente PHP (autenticación + consumo de la API)
├── postman/                    Colección y environment de Postman
└── evidencias/                 Capturas y respuestas JSON reales de cada escenario
    ├── aprobada/
    ├── pendiente/
    └── rechazada/
```

👉 Empieza por **[`SOLUCION_PUNTO_1.md`](SOLUCION_PUNTO_1.md)**.
