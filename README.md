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

## Estructura del proyecto

```
├── README.md                 Este índice
├── SOLUCION_PUNTO_1.md         Solución del Punto 1: integración Web Checkout
├── SOLUCION_PUNTO_2.md         Solución del Punto 2: conceptos básicos
├── .env.example               Plantilla de credenciales
├── src/                        Cliente PHP (autenticación + consumo de la API)
├── postman/                    Colección y environment de Postman
└── evidencias/                 Capturas y respuestas JSON reales de cada escenario
    ├── aprobada/
    ├── pendiente/
    └── rechazada/
```

👉 Empieza por **[`SOLUCION_PUNTO_1.md`](SOLUCION_PUNTO_1.md)**.
