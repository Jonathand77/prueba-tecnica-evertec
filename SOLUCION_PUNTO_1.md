# Punto 1 — Integración con PlacetoPay Web Checkout

## Resumen del resultado

Se integró y probó de **extremo a extremo, contra el ambiente real de
pruebas** (`https://checkout-test.placetopay.com`), usando tres herramientas
distintas: un **cliente PHP**, una **tienda demo** (catálogo + carrito +
pago) y **Postman**. Se crearon sesiones de pago reales, se pagaron con
tarjetas de prueba oficiales de PlacetoPay, y se consultó el resultado final
de la API. **Los 3 escenarios pedidos quedaron confirmados con evidencia
real**: `APPROVED`, `PENDING` y `REJECTED`.

🔗 **Recurso de la tienda en GitHub:** https://github.com/Jonathand77/prueba-tecnica-evertec/tree/main/tienda

| Escenario | requestId | Resultado final |
|---|---|---|
| Aprobado | `3872245` |  `APPROVED` |
| Pendiente | `3872251` |  `PENDING` → luego `APPROVED` |
| Rechazado | `3872255` |  `REJECTED` |

---

## 1. Documentación oficial consultada

Antes de escribir código se revisó la documentación oficial de PlacetoPay,
incluyendo las dos fuentes suministradas:

| Tema | Enlace |
|---|---|
| Documentación Web Checkout (general) | https://docs.placetopay.dev/checkout |
| Documentación API Gateway (general) | https://docs.placetopay.dev/gateway/ |
| Cómo funciona Checkout (flujo completo) | https://docs.placetopay.dev/en/checkout/how-checkout-works |
| Autenticación (login / tranKey / nonce / seed) | https://docs.placetopay.dev/checkout/authentication/ |
| Referencia API — Crear sesión | https://docs.placetopay.dev/en/checkout/api/reference/session |
| Tarjetas de prueba (aprobada / pendiente / rechazada) | https://docs.placetopay.dev/en/gateway/testing-card/ |
| Cómo probar tu integración | https://docs.placetopay.dev/en/checkout/test-your-integration |

También se revisó el SDK oficial de PHP de PlacetoPay
([dnetix/redirection](https://github.com/dnetix/redirection)) para confirmar,
con código real, que la consulta de estado es `POST /api/session/{requestId}`
(no `GET`).


## 2. Ambiente y credenciales utilizadas

| Parámetro | Valor |
|---|---|
| Ambiente | Sandbox / pruebas |
| URL base — Web Checkout *(usada en esta integración)* | `https://checkout-test.placetopay.com` |
| URL base — API Gateway  | `https://api-test.placetopay.com/rest` |
| Login | `2d9eaf1e662518756a3d78806543af5b` (dato público, viaja en texto plano en cada request) |
| SecretKey | *(no se expone en este documento — se maneja vía variables de entorno / environment de Postman)* |

## 3. Parámetros mínimos para el proceso transaccional

### 3.1 Crear sesión — `POST /api/session`

| Campo | Obligatorio | Descripción |
|---|---|---|
| `auth` | Sí | Bloque de autenticación (login, tranKey, nonce, seed) |
| `ipAddress` | Sí | IP del comprador |
| `userAgent` | Sí | User-Agent del navegador del comprador |
| `returnUrl` | Sí | URL a la que PlacetoPay redirige tras completar el pago |
| `payment.reference` | Sí (para cobrar) | Identificador único de la transacción en el comercio |
| `payment.description` | Sí (para cobrar) | Descripción visible al comprador |
| `payment.amount.currency` | Sí (para cobrar) | Moneda (ISO 4217), ej. `COP` |
| `payment.amount.total` | Sí (para cobrar) | Monto a cobrar |
| `expiration` | No | Vigencia de la sesión (mínimo 5 minutos en el futuro) |
| `locale` | No | Idioma de la página de pago, ej. `es_CO` |

**Respuesta:** `requestId` (identificador de la sesión) y `processUrl` (URL a
la que se redirige al comprador).

### 3.2 Consultar sesión — `POST /api/session/{requestId}`

| Campo | Obligatorio | Descripción |
|---|---|---|
| `auth` | Sí | Bloque de autenticación (se recalcula en cada llamada) |

**Respuesta:** `status.status` con el resultado final:
- `APPROVED` — pago aprobado (estado final)
- `PENDING` — a la espera de confirmación (banco, validación manual, 3DS, etc.)
- `REJECTED` — pago rechazado o sesión cancelada/expirada (estado final)

## 4. Herramientas utilizadas

Se implementó el flujo con **tres herramientas**, tal como lo permite el
enunciado:

1. **Tienda demo (PHP)** — carpeta [`tienda/`](tienda/): un catálogo con
   carrito de compras y botón de pago, **la pieza central de este punto**.
   Es una aplicación web real (no un script de consola ni una colección de
   API) que un comprador podría usar de principio a fin.
2. **Cliente PHP + cURL nativo** (sin dependencias externas) — carpeta
   [`src/`](src/): la librería de autenticación y consumo de la API que usa
   la tienda por debajo, y que también se puede invocar por consola.
3. **Postman** (colección + environment) — carpeta [`postman/`](postman/),
   usada durante el desarrollo para validar la integración llamada por
   llamada.

### 4.1 Ejecutar la tienda (recomendado — es el flujo completo)

```bash
cp .env.example .env
# editar .env con PLACETOPAY_LOGIN y PLACETOPAY_SECRET_KEY

php -S localhost:8000 -t tienda
# abrir http://localhost:8000/index.php, agregar productos al carrito
# y dar clic en "Pagar con PlacetoPay"
```

### 4.2 Ejecutar con el cliente PHP por consola

```bash
php src/create_session.php aprobada     # o: pendiente | rechazada
# abrir el "processUrl" impreso y pagar con la tarjeta de prueba correspondiente

php src/query_session.php {requestId} aprobada
```

### 4.3 Ejecutar con Postman

1. Importar `postman/PlacetoPay-WebCheckout.postman_collection.json` y
   `postman/PlacetoPay-Sandbox.postman_environment.json`.
2. En el environment, completar `login` y `secretKey` (columna **Current
   Value**) — se dejaron como placeholder a propósito para no versionar
   credenciales reales.
3. Ejecutar **"1. Crear sesión de pago"** → el `requestId` queda guardado
   automáticamente en el environment.
4. Abrir el `processUrl` de la respuesta en el navegador y pagar con una
   tarjeta de prueba.
5. Ejecutar **"2. Consultar sesión"** → devuelve el `status.status` final.

## 5. Estructura del proyecto

```
├── README.md                     Índice general del repositorio
├── SOLUCION_PUNTO_1.md            Este documento (solución completa del punto 1)
├── .env.example                  Plantilla de credenciales
├── tienda/                        Tienda demo: catálogo + carrito + pago
│   ├── index.php                 Catálogo y carrito
│   ├── checkout.php              Crea la sesión de pago (recalcula el total en servidor)
│   ├── resultado.php             returnUrl: consulta y muestra el estado final
│   ├── productos.php             Catálogo de productos (fuente única de precios)
│   └── README.md                 Cómo ejecutar la tienda
├── src/
│   ├── PlacetoPayClient.php      Cliente HTTP + lógica de autenticación
│   ├── config.php                Carga de variables de entorno
│   ├── create_session.php        CLI: crea una sesión (POST /api/session)
│   └── query_session.php         CLI: consulta una sesión (POST /api/session/{id})
├── postman/
│   ├── PlacetoPay-WebCheckout.postman_collection.json
│   └── PlacetoPay-Sandbox.postman_environment.json
└── evidencias/
    ├── tiendaVirtual/         Evidencia real generada desde la tienda (la oficial de este punto)
    │   ├── aprobada/
    │   ├── pendiente/
    │   └── rechazada/
    └── postman/               Evidencia generada durante el desarrollo, validando la integración
        ├── aprobada/
        ├── pendiente/
        └── rechazada/
```

---

## 6. Evidencia del flujo transaccional

Los 3 escenarios se ejecutaron de principio a fin **desde la tienda virtual**
(`tienda/`, corriendo en `http://localhost:8000`): **agregar productos al
carrito → pagar en la página de PlacetoPay con una tarjeta de prueba →
ver el resultado en `resultado.php`**, que internamente consulta
`POST /api/session/{requestId}`. Todas las capturas y los JSON de respuesta
están en [`evidencias/tiendaVirtual/`](evidencias/tiendaVirtual/); a
continuación se muestran incrustadas.

### 6.1 Escenario APROBADO

| Dato | Valor |
|---|---|
| requestId | `3872245` |
| Carrito | 2x Camiseta, 1x Taza |
| Tarjeta de prueba | `4110760000000081` (Visa) |
| Monto | $115.000 COP |
| Emisor | Banco de Guayaquil, S.A. |
| Autorización | `837395` |
| Código de respuesta | `00` |
| Estado final | **`APPROVED`** — *"La petición ha sido aprobada exitosamente"* |

**1) Carrito de compras, antes de pagar**

![Carrito - Aprobado](evidencias/tiendaVirtual/aprobada/aprobada_crear_sesion_carrito.png)

**2) Pago en el Checkout de PlacetoPay**

![Pago Checkout - Aprobado](evidencias/tiendaVirtual/aprobada/aprobada_pago_checkout.png)

**3) Resultado en la tienda** (`resultado.php` → `POST /api/session/{requestId}`)

![Consulta resultado - Aprobado](evidencias/tiendaVirtual/aprobada/aprobada_consulta_resultado.png)

JSON completo: [`evidencias/tiendaVirtual/aprobada/aprobada_consulta_resultado.json`](evidencias/tiendaVirtual/aprobada/aprobada_consulta_resultado.json)

---

### 6.2 Escenario PENDIENTE

| Dato | Valor |
|---|---|
| requestId | `3872251` |
| Carrito | 1x Camiseta, 1x Taza, 1x Gorra |
| Tarjeta de prueba | `4110760000000032` (Visa, con challenge 3D Secure) |
| Monto | $105.000 COP |
| Estado inicial | **`PENDING`** — *"La petición se encuentra pendiente"* (reason `PT`) |
| Estado posterior | `APPROVED` (ver nota de ciclo de vida abajo) |

**1) Carrito de compras, antes de pagar**

![Carrito - Pendiente](evidencias/tiendaVirtual/pendiente/pendiente_crear_sesion_carrito.png)

**2) Pago en el Checkout de PlacetoPay** — la tarjeta dispara un challenge 3DS
que deja la transacción **pendiente de confirmación**

![Pago Checkout - Pendiente](evidencias/tiendaVirtual/pendiente/pendiente_pago_checkout.png)

**3) Resultado en la tienda — capturado en el momento exacto en estado `PENDING`**

![Consulta resultado - Pendiente](evidencias/tiendaVirtual/pendiente/pendiente_consulta_resultado.png)

JSON de ese momento: [`evidencias/tiendaVirtual/pendiente/pendiente_consulta_resultado_PENDING.json`](evidencias/tiendaVirtual/pendiente/pendiente_consulta_resultado_PENDING.json)

---

### 6.3 Escenario RECHAZADO

| Dato | Valor |
|---|---|
| requestId | `3872255` |
| Carrito | 3x Gorra |
| Tarjeta de prueba | `4110760000000016` (Visa — deny) |
| Monto | $105.000 COP |
| Código de respuesta | `05` |
| Estado final | **`REJECTED`** — *"Negada, puede ser tarjeta bloqueada o timeout"* |

**1) Carrito de compras, antes de pagar**

![Carrito - Rechazado](evidencias/tiendaVirtual/rechazada/rechazada_crear_sesion_carrito.png)

**2) Pago en el Checkout de PlacetoPay**

![Pago Checkout - Rechazado](evidencias/tiendaVirtual/rechazada/rechazada_pago_checkout.png)

**3) Resultado en la tienda** (`resultado.php` → `POST /api/session/{requestId}`)

![Consulta resultado - Rechazado](evidencias/tiendaVirtual/rechazada/rechazada_consulta_resultado.png)

JSON completo: [`evidencias/tiendaVirtual/rechazada/rechazada_consulta_resultado.json`](evidencias/tiendaVirtual/rechazada/rechazada_consulta_resultado.json)

---

## 7. Evidencia de las peticiones HTTP

Complementa la sección 6 (que muestra el *resultado*) mostrando *cómo* se
arma y envía cada petición — implementado en
[`src/PlacetoPayClient.php`](src/PlacetoPayClient.php) (método `post()`):

```http
POST /api/session HTTP/1.1
Host: checkout-test.placetopay.com
Content-Type: application/json
```
```json
{
  "auth": { "login": "...", "tranKey": "...", "nonce": "...", "seed": "..." },
  "payment": { "reference": "...", "amount": { "currency": "COP", "total": 115000 } },
  "returnUrl": "http://localhost:8000/resultado.php"
}
```

La consulta usa el mismo formato en `POST /api/session/{requestId}`, con solo
el bloque `auth` en el body (recalculado en cada llamada, nunca reutilizado).

**Evidencia real capturada** de estas peticiones viajando tal cual:

- Capturas de Postman (método, headers y body reales) → [`evidencias/postman/`](evidencias/postman/)
- JSON de respuesta real de cada escenario → `evidencias/tiendaVirtual/{escenario}/*_consulta_resultado.json` (ej. [aprobada](evidencias/tiendaVirtual/aprobada/aprobada_consulta_resultado.json))

---

## Tarjetas de prueba oficiales utilizadas

Fuente: https://docs.placetopay.dev/en/gateway/testing-card/

| Tarjeta | Franquicia | Comportamiento |
|---|---|---|
| `4110760000000081` | Visa | Aprueba |
| `4110760000000032` | Visa | Pendiente (3DS challenge) → aprueba |
| `4110760000000016` | Visa | Rechaza (deny) |

---
