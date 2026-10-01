# Punto 3 — Gestión de casos de comercios

## Documentación oficial consultada para este punto

| Tema | Enlace |
|---|---|
| Autenticación y códigos de error | https://docs.placetopay.dev/en/checkout/authentication |

---

## Caso 1 — "Autenticación fallida 102" y riesgo de cancelación

### Diagnóstico

El código **102** significa *"TranKey hash does not match"*: el `login`
existe y el `seed` es válido, pero el `tranKey` no coincide. El problema
está en el **`secretKey`** o en **cómo se calcula el hash**. Como empezó
"desde ayer", algo cambió ese día. Causas probables:

- Se regeneró el `secretKey` y el comercio sigue usando el anterior.
- Un despliegue o cambio de configuración del comercio.
- Credenciales de pruebas usadas en producción, o al revés.
- Error al calcular el hash (por ejemplo, firmar el `nonce` ya codificado
  en Base64) o espacios al copiar la llave.

### Gestión interna

1. **Prioridad crítica:** llamar al comercio en la primera hora y ser su
   único contacto hasta el cierre.
2. **Revisar nuestra plataforma:** estado del sitio, logs desde ayer y si
   alguien regeneró el `secretKey`.
3. **Sesión técnica con el comercio:** qué cambió, qué ambiente usan y
   cómo calculan el `tranKey`. Nunca se pide el `secretKey`; si hay dudas,
   se regenera.
4. **Corregir y validar** con una transacción de prueba. Si no se resuelve,
   escalar a nivel 2 con la evidencia.
5. **Retener al cliente:** involucrar al gerente de cuenta, dar
   actualizaciones periódicas y enviar un informe del incidente al cierre.

### Respuesta al cliente

> **Asunto:** Atención prioritaria — Error "Autenticación fallida 102"
>
> Estimado equipo de [Comercio]:
>
> Lamentamos sinceramente los inconvenientes que han tenido desde ayer y el
> impacto que esto ha generado en su operación. Entendemos la gravedad de la
> situación y su caso ya está siendo atendido con **prioridad máxima**. Yo
> seré su contacto directo hasta que quede resuelto.
>
> Ya revisamos el error: el código **102** indica que la llave de transacción
> (`tranKey`) que se envía en la autenticación no coincide con la registrada
> para su sitio. Esto significa que nuestra plataforma está recibiendo sus
> solicitudes y que el problema está en las credenciales o en la forma en que
> se están firmando. Normalmente se resuelve en poco tiempo una vez
> identificamos qué cambió.
>
> Para resolverlo lo antes posible, les propongo:
>
> 1. **Una llamada hoy mismo** con su equipo técnico para revisar el caso
>    juntos. Por favor indíquenme a qué hora les queda mejor.
> 2. Mientras tanto, nos ayudaría mucho que nos confirmen:
>    - ¿Hicieron algún cambio ayer en sus servidores, despliegues o
>      configuración?
>    - ¿Qué URL del servicio están usando (pruebas o producción)?
>    - La hora aproximada en que empezó el error.
>
> Por seguridad, **no nos envíen su llave secreta (`secretKey`)** por este
> medio. Si es necesario, la regeneramos juntos durante la llamada.
>
> Valoramos mucho la relación con [Comercio] y queremos que su operación se
> normalice cuanto antes. Les enviaré actualizaciones cada dos horas hasta
> que todo esté funcionando y, al cierre, un informe con la causa y las
> medidas que tomaremos para que no vuelva a pasar.
>
> Quedo a su disposición.
>
> Cordialmente,
> Jonathan Fernandez
> Analista de Implementación — PlacetoPay

---

## Caso 2 — CLARO: "Autenticación mal formada" desde las 7:00 a. m.

### Diagnóstico

Aquí las credenciales no son el problema: **la autenticación llega
incompleta o con formato inválido**. Corresponde al código **100**
(*"Malformed authorization header"*) o al **107** (*"Bad definition of the
UsernameToken"*). Que empezara a una hora exacta apunta a un cambio
puntual. Causas probables:

- Un despliegue o actualización del comercio que cambió cómo se arma la
  petición.
- Campos con otro nombre, vacíos o con formato inválido (`seed` no ISO 8601,
  `nonce` sin Base64), o falta `Content-Type: application/json`.
- Un proxy o firewall nuevo que altera la petición.
- Un cambio en **nuestra plataforma**, si otros comercios tienen el mismo
  error.

### Gestión interna

1. **Prioridad crítica:** llamar a CLARO de inmediato y asignar una reunión.
2. **Descartar un incidente general:** revisar si otros comercios fallan
   desde las 7:00 a. m. Si es así, escalarlo como incidente de plataforma.
3. **Si es solo de CLARO:** revisar los logs del sitio y, con su equipo
   técnico, una petición fallida (sin datos sensibles).
4. **Restablecer rápido:** si el error vino de un despliegue, proponer
   revertirlo y corregir después. Validar con una transacción de prueba.
5. **Cierre:** informe del incidente y recomendar probar cada despliegue en
   el ambiente de pruebas.

### Respuesta al cliente

> **Asunto:** Atención prioritaria — Error "Autenticación mal formada"
>
> Estimado equipo de CLARO:
>
> Lamentamos mucho la afectación que están teniendo desde las 7:00 a. m. y
> el impacto que esto genera en sus usuarios. Su caso está siendo atendido
> con **prioridad máxima** y yo seré su contacto directo hasta que quede
> resuelto.
>
> El error "Autenticación mal formada" indica que la información de
> autenticación que llega con cada solicitud está incompleta o no tiene el
> formato esperado, por lo que el servicio no puede procesarla. En este
> momento estamos validando en nuestra plataforma si hubo algún cambio de
> nuestro lado que pueda estar causando el problema.
>
> Para avanzar en paralelo y restablecer el servicio cuanto antes, les
> proponemos:
>
> 1. **Una llamada de inmediato** con su equipo técnico para revisar juntos
>    una de las solicitudes que está fallando.
> 2. Que nos confirmen:
>    - ¿Hicieron algún despliegue, actualización o cambio de red anoche o
>      esta mañana?
>    - La hora del último pago que funcionó correctamente.
>    - Un ejemplo de una solicitud fallida con fecha y hora, **sin incluir
>      su llave secreta (`secretKey`)**.
>
> Si identificamos que el error empezó con un cambio reciente en su sistema,
> una opción rápida es revertir ese cambio para restablecer los pagos de
> inmediato, mientras se corrige con calma.
>
> Les enviaremos actualizaciones cada hora hasta que el servicio esté
> normalizado y, al cierre, un informe con la causa y las medidas
> preventivas.
>
> Quedo a su disposición.
>
> Cordialmente,
> Jonathan Fernandez
> Analista de Implementación — PlacetoPay

---

## Caso 3 — Sunshine: el comercio no logra entender la implementación

### Diagnóstico

Es un problema de **comunicación**, no técnico. Si la explicación se ha
repetido sin resultado, hay que cambiar el **enfoque**. Que el comercio no
esté molesto y pida otra forma de aprender es una oportunidad.

### Gestión interna

1. **Escuchar primero:** que el comercio explique con sus palabras qué
   entiende y dónde se pierde, y saber si es un perfil de negocio o técnico.
2. **Cambiar el formato:** demostración en vivo de una compra completa
   (como la tienda del [Punto 1](SOLUCION_PUNTO_1.md)), analogías simples y
   práctica guiada con su desarrollador en el ambiente de pruebas.
3. **Trabajar por etapas:** autenticación → crear sesión → consultar estado
   → notificación → producción, sin avanzar hasta que cada paso funcione.
4. **Apoyo escrito y validación:** un resumen corto después de cada sesión y
   pedir que el comercio haga el paso por sí mismo, en lugar de preguntar
   "¿quedó claro?".

### Respuesta al cliente

> **Asunto:** Nueva propuesta para avanzar con la implementación
>
> Estimado equipo de Sunshine:
>
> Muchas gracias por su sinceridad. Su comentario es muy valioso y tienen
> toda la razón: si las explicaciones anteriores no han sido suficientes, es
> momento de cambiar la forma en que lo estamos haciendo. Nuestro objetivo es
> que ustedes se sientan seguros con cada paso de la implementación.
>
> Para eso, les propongo un nuevo enfoque:
>
> 1. **Una reunión corta para escucharlos:** queremos que nos cuenten con sus
>    palabras qué tienen claro hasta ahora y en qué punto surgen las dudas.
>    Así podremos enfocarnos exactamente en lo que necesitan.
> 2. **Una demostración en vivo:** les mostraremos una compra completa de
>    principio a fin, para que vean cómo funciona el servicio en la práctica
>    y no solo en la teoría.
> 3. **Implementación por etapas:** dividiremos el proyecto en pasos pequeños
>    y trabajaremos uno a la vez, junto con su equipo técnico, sin avanzar
>    hasta que cada paso esté funcionando.
> 4. **Material de apoyo sencillo:** después de cada sesión les enviaré un
>    resumen corto con lo que vimos y el siguiente paso, además de un
>    glosario con los términos principales.
>
> Si les parece bien, ¿podrían indicarme dos o tres horarios disponibles esta
> semana para la primera reunión? También nos sería muy útil que participe la
> persona de su equipo que se encargará de la parte técnica.
>
> Estamos comprometidos con que este proyecto avance y con acompañarlos en
> cada etapa.
>
> Quedo a su disposición.
>
> Cordialmente,
> Jonathan Fernandez
> Analista de Implementación — PlacetoPay

---

## Caso 4 — Sunshine: el comercio está molesto, cuestiona mi competencia y amenaza con cancelar

### Diagnóstico

El Caso 3 escaló a un problema de **confianza**. El tono grosero viene de
la frustración acumulada y no se toma de forma personal. Probablemente las
expectativas nunca se acordaron de forma explícita, y el riesgo de perder un
cliente estratégico requiere respaldo de la compañía. El objetivo no es
tener la razón, sino **recuperar la confianza y sacar adelante el
proyecto**.

### Gestión interna

1. **Mantener la calma:** escuchar, no discutir ni justificarse. Si el tono
   continúa, poner un límite con respeto y proponer retomar la conversación
   después.
2. **Escalar** a mi líder y al gerente de cuenta con el historial completo
   del proyecto.
3. **Autoevaluación honesta:** identificar y corregir fallas reales de mi
   parte (seguimiento, tiempos, claridad).
4. **Reunión de recuperación:** acordar expectativas, alcance y fechas
   **por escrito**, presentar un plan de acción y ofrecer cambiar de
   analista si el comercio lo prefiere.
5. **Seguimiento visible:** reportes semanales de avance y documentar todos
   los acuerdos.

### Respuesta al cliente

> **Asunto:** Plan de acción para el proyecto de Sunshine
>
> Estimado equipo de Sunshine:
>
> Gracias por expresarnos con claridad su inconformidad. Lamento que el
> proyecto no haya avanzado como esperaban y entiendo la frustración que
> esto les genera, especialmente considerando lo importante que es para su
> negocio.
>
> Tomamos su comentario con total seriedad. Por eso he involucrado a mi líder
> y a su gerente de cuenta, que nos acompañarán en adelante, para asegurarnos
> de que reciban la atención y el respaldo que necesitan.
>
> Queremos proponerles lo siguiente:
>
> 1. **Una reunión esta semana** con ustedes, mi líder y su gerente de cuenta,
>    para escuchar sus expectativas y acordar juntos el alcance, los
>    entregables y los plazos del proyecto.
> 2. **Un plan de trabajo por escrito**, con etapas, responsables y fechas
>    concretas, para que tengan visibilidad total del avance.
> 3. **Acompañamiento técnico directo** con su equipo en cada etapa de la
>    implementación, hasta tenerla funcionando en producción.
> 4. **Reportes semanales de avance** con lo realizado y los próximos pasos.
>
> Si consideran que el proyecto avanzaría mejor con otro analista, también
> estamos abiertos a hacer ese cambio. Lo más importante para nosotros es
> que su implementación salga adelante.
>
> Valoramos mucho la oportunidad de trabajar con Sunshine y queremos
> recuperar su confianza con hechos. ¿Podrían indicarnos su disponibilidad
> para la reunión en los próximos días?
>
> Quedo a su disposición.
>
> Cordialmente,
> Jonathan Fernandez
> Analista de Implementación — PlacetoPay
> *Con copia a: [Líder del equipo], [Gerente de cuenta]*
