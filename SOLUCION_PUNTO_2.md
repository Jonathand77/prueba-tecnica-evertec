# Punto 2 — Conceptos básicos de PlacetoPay

## Documentación oficial consultada para este punto

| Tema | Enlace |
|---|---|
| Flujo de una transacción y estados | https://docs.placetopay.dev/en/gateway/transaction-flow |
| Tipos de transacción | https://docs.placetopay.dev/en/gateway/transaction-types |
| Cómo funciona Checkout (estados de sesión) | https://docs.placetopay.dev/en/checkout/how-checkout-works |
| Límite de intentos por sesión | https://docs.placetopay.dev/en/checkout/attempts-limit |
| Preautorización | https://docs.placetopay.dev/payments/preauthorization |
| Token (suscripción) | https://docs.placetopay.dev/en/checkout/api/reference/token |
| Cómo funciona AutoPay (recurrencia) | https://docs.placetopay.dev/en/autopay/how-autopay-works/ |
| API Gateway (cuándo usarlo) | https://docs.placetopay.dev/gateway/ |
| Referencia API Gateway (endpoints) | https://docs.placetopay.dev/en/gateway/api/reference/transaction/ |
| Dispersión (tipos de transacción) | https://docs.placetopay.dev/gateway/transaction-types |
| Dispersión en Checkout (`payment.dispersion`) | https://docs.placetopay.dev/checkout/api/reference/payment |
| Notificación (webhook) | https://docs.placetopay.dev/en/checkout/notification |

---

## 1. ¿Qué es el `requestId`, para qué sirve y en qué casos se repite?

### Qué es

El `requestId` es el **identificador único numérico de una sesión de pago**
en PlacetoPay Web Checkout. Se genera automáticamente y se devuelve en la
respuesta de `POST /api/session`, junto con el `processUrl` al que se
redirige al comprador:

```json
{
  "requestId": 3872075,
  "processUrl": "https://checkout-test.placetopay.com/spa/session/3872075/..."
}
```

### Para qué sirve

- **Consultar el estado de la sesión**, en cualquier momento, con
  `POST /api/session/{requestId}`.
- **Correlacionar la sesión con la transacción en el sistema del comercio**,
  junto con el `payment.reference` que el comercio define al crear la
  sesión.
- **Cancelar una sesión** aún no pagada.
- **Identificar la sesión en las notificaciones** (webhook) que PlacetoPay
  envía al `returnUrl`/URL de notificación configurada.

### En qué casos se repite (se reutiliza el mismo `requestId`)

El `requestId` **no cambia** mientras la sesión siga abierta; se reutiliza
en varios escenarios reales:

1. **Consultas repetidas de estado.** Es el caso más común: si una
   transacción queda `PENDING`, la documentación recomienda volver a
   consultar (`POST /api/session/{requestId}`) cada pocos minutos hasta
   llegar a un estado final. **Lo evidenciamos nosotros mismos en el Punto
   1**: la sesión `3872075` se consultó dos veces con el mismo `requestId`
   — a las 12:01:57 devolvió `PENDING` y a las 12:35:41, ya resuelta,
   devolvió `APPROVED` (ver [`SOLUCION_PUNTO_1.md`](SOLUCION_PUNTO_1.md#62-escenario-pendiente)).
2. **Reintentos de pago dentro de la misma sesión.** Si el comprador ingresa
   una tarjeta y es rechazada, Web Checkout le permite reintentar con otra
   tarjeta **sin generar una sesión nueva**: el mismo `requestId` acumula
   los intentos. Por defecto se permiten hasta **10 intentos rechazados**
   antes de que la sesión quede definitivamente en `REJECTED` (el contador
   de intentos es independiente por sesión; una sesión nueva empieza en
   cero).
3. **Pagos parciales (`APPROVED_PARTIAL`).** Cuando el comercio permite
   `allowPartial`, un comprador puede completar el monto total con más de
   una transacción; todas quedan asociadas al mismo `requestId` hasta que
   se completa el monto o se agota el tiempo (`PARTIAL_EXPIRED`).

---

## 2. ¿Cuáles son los estados de una transacción? Explica el significado de cada uno

El estado se obtiene en el campo `status.status` de la respuesta de
`POST /api/session/{requestId}`:

| Estado | Tipo | Significado |
|---|---|---|
| **`PENDING`** | Transitorio | La sesión está activa y la transacción aún no tiene un resultado definitivo — puede estar esperando que el comprador pague, una validación adicional del banco emisor (p. ej. un challenge 3D Secure), o una revisión manual. |
| **`APPROVED`** | Final | El pago fue aprobado y el proceso se completó exitosamente. Es el estado que confirma el cobro. |
| **`REJECTED`** | Final | El pago fue rechazado (fondos insuficientes, tarjeta bloqueada, timeout del banco, etc.), o el comprador canceló, o la sesión expiró sin que se completara un pago, o se agotó el límite de intentos rechazados. |
| **`APPROVED_PARTIAL`** | Transitorio | Se recibió un pago parcial del monto total (cuando el comercio habilita pagos fraccionados); el comprador puede seguir pagando con transacciones adicionales dentro de la misma sesión. |
| **`PARTIAL_EXPIRED`** | Final | Se venció el tiempo permitido para completar un pago que había quedado parcial; la sesión se cierra sin alcanzar el monto total. |

---

## 3. ¿Qué es una preautorización y cómo funcionaría para un comercio? Ejemplo

### Qué es

Una preautorización (`checkin`) es una transacción que **retiene un monto
específico en la tarjeta del cliente sin cobrarlo de inmediato**. El dinero
queda "apartado" — el cliente no puede gastarlo, pero el comercio tampoco lo
recibe todavía — hasta que el comercio decide capturarlo (cobrarlo) o
liberarlo.

### Cómo funciona (3 pasos)

1. **Check-in (autorización inicial):** el comercio crea una transacción
   tipo `checkin` por un monto estimado. Queda reservado en la tarjeta, sin
   cobrar. La referencia y la moneda quedan fijas para todo el flujo.
2. **Reauthorization (ajuste opcional):** si el monto final va a ser mayor
   al estimado, el comercio puede **incrementar** el monto retenido (usando
   el `internalReference` y `authorization` del check-in). Se pueden hacer
   varias reautorizaciones antes del cierre.
3. **Checkout (captura o liberación final):** el comercio **captura** el
   monto retenido (ahí sí se cobra al cliente) o lo **libera** por completo
   (enviando monto cero) si finalmente no hubo consumo.

### Ejemplo para un comercio: hotel

Un hotel usa preautorización para garantizar el pago de una reserva sin
cobrar hasta el check-out, y para cubrir consumos adicionales:

1. El huésped llega y el hotel hace **check-in** por **$500.000 COP**
   (2 noches estimadas) → el monto queda retenido, no cobrado.
2. Durante la estadía, el huésped consume el minibar y servicio a la
   habitación por **$80.000 COP** adicionales → el hotel envía una
   **reautorización** que incrementa la retención a **$580.000 COP**.
3. Al hacer el **check-out**, el consumo real final fue de **$550.000 COP**
   → el hotel captura ($checkout$) exactamente ese monto; el excedente de
   $30.000 COP queda liberado automáticamente y nunca se le cobra al
   cliente.

**Beneficio para el comercio:** flexibilidad para ajustar el monto final
sin pedirle una nueva tarjeta al cliente, seguridad de tener los fondos
garantizados desde el inicio, y mejor experiencia para el cliente porque no
se le cobra hasta que el servicio termina. Aplica igual a arriendo de autos,
reservas de eventos, o cualquier servicio de costo variable.

---

## 4. Diferencias entre cobro por suscripción (token) y cobro por recurrencia (AutoPay)

Aunque ambos mecanismos evitan pedirle la tarjeta al cliente en cada cobro,
resuelven problemas distintos:

La **suscripción/token** resuelve *"no quiero pedirle la
tarjeta de nuevo"* — el comercio sigue controlando cuándo cobrar. La
**recurrencia/AutoPay** resuelve *"no quiero tener que acordarme de cobrar"*
— PlacetoPay controla el cuándo y el cuánto, y solo le avisa al comercio el
resultado.

| | **Suscripción (Token)** | **Recurrencia (AutoPay)** |
|---|---|---|
| **Qué guarda PlacetoPay** | Solo el **token** de la tarjeta (la tokeniza una vez, vía una sesión con `subscription`). | El token **más** la regla de recurrencia (frecuencia, monto o lógica del monto). |
| **Quién ejecuta el cobro** | **El comercio**: cuando quiere cobrar, llama `POST /api/collect` enviando el token guardado. El comercio es responsable de decidir *cuándo* cobrar. | **PlacetoPay**: el motor de AutoPay detecta la fecha de corte y ejecuta el cobro automáticamente, sin que el comercio tenga que disparar nada ese día. |
| **Caso de uso típico** | Un comercio que quiere cobrar "bajo demanda" (p. ej. cobrar solo cuando el cliente hace un nuevo pedido, usando la tarjeta ya guardada). | Cobros automáticos y periódicos sin intervención humana: membresías, servicios públicos (monto variable), suscripciones SaaS. |

---

## 5. Diferencia entre API Gateway y Web Checkout

Ambos son formas de integrarse con PlacetoPay para procesar pagos; la
diferencia de fondo es **quién captura los datos sensibles de la tarjeta**:

**Web Checkout** PlacetoPay se encarga de la pantalla de
pago y de la seguridad, y el comercio solo crea la sesión y consulta el
resultado. Con **API Gateway** el comercio tiene control total del flujo, pero
asume la captura de datos sensibles y el cumplimiento PCI. Según la
documentación, Gateway *"sólo debe ser usado por aquellos clientes de la
plataforma que requieren capturar la información del tarjetahabiente"*.

| | **Web Checkout** | **API Gateway** |
|---|---|---|
| **Qué es** | Solución de pago **alojada (hosted)** por PlacetoPay: el comercio crea una sesión y **redirige** al comprador a la interfaz de PlacetoPay para pagar. | Integración **directa servidor a servidor**: el comercio captura los datos del tarjetahabiente en **su propio sistema** y los envía a la API. |
| **Quién captura la tarjeta** | PlacetoPay, en su interfaz. El comercio nunca ve ni almacena el número de tarjeta. | El comercio, en su propio formulario, app o canal (IVR, call center, etc.). |
| **Casos de uso típicos** | E-commerce estándar: tiendas en línea, pago de facturas, donaciones. | Cobros recurrentes desde bases de datos propias, sistemas IVR/call center, apps móviles con interfaz nativa (no HTML), pagos a varios destinatarios con los mismos datos de tarjeta. |

---

## 6. ¿Para qué se usa la dispersión en un comercio?

### Qué es

La dispersión permite que **un solo pago del comprador se divida entre varios
destinatarios (sitios/comercios)**: una parte del valor va al comercio
autenticado en la transacción y otra parte a uno o más comercios distintos.
El comprador paga una sola vez, con una sola tarjeta, y PlacetoPay reparte el
dinero.

### Para qué se usa

Un comercio usa la dispersión cuando **el dinero de una misma venta le
pertenece a más de una entidad** y quiere que cada una reciba su parte
directamente, sin tener que cobrar el total y luego repartirlo:

- **Repartir el pago entre varios beneficiarios en una sola transacción:**
  por ejemplo, una agencia de viajes que vende un paquete puede enviar el
  valor del tiquete directamente a la aerolínea y quedarse con su comisión.
- **Evitar transferencias posteriores entre comercios:** cada destinatario
  recibe su dinero desde el momento del pago, lo que reduce costos
  operativos y el riesgo de manejar dinero de terceros.
- **Simplificar la conciliación:** cada parte queda registrada como una
  transacción asociada al pago original, con su propio monto y estado.
- **Mantener una experiencia simple para el comprador:** paga una sola vez,
  con una sola tarjeta, aunque detrás haya varios comercios involucrados.

---

## 7. ¿Para qué sirve la notificación?

### Qué es

La notificación es un **webhook**: cuando una sesión termina, PlacetoPay envía
por iniciativa propia una petición HTTP `POST` al servidor del comercio con el
resultado. Llega a la URL de notificación configurada para el sitio, o a la
`notificationUrl` que se puede enviar al crear cada sesión.

### Para qué sirve

1. **Enterarse del resultado aunque el comprador no regrese a la tienda.** El
   `returnUrl` depende del navegador del comprador: si cierra la pestaña,
   pierde la conexión o el pago se resuelve después, el comercio nunca se
   entera por esa vía. La notificación llega **servidor a servidor**, sin
   depender del comprador.
2. **Resolver transacciones `PENDING` sin hacer polling.** En el
   [Punto 1](SOLUCION_PUNTO_1.md#62-escenario-pendiente) la sesión `3872075`
   pasó de `PENDING` a `APPROVED` más de 30 minutos después. Con la
   notificación, el comercio se entera apenas cambia el estado, sin consultar
   `POST /api/session/{requestId}` cada pocos minutos.
3. **Actualizar el pedido en el sistema del comercio** (marcarlo como pagado,
   despachar, liberar inventario si fue rechazado), relacionando la
   notificación con el pedido mediante `requestId` y `reference`.
