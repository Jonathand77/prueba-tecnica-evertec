<?php

/**
 * PlacetoPayClient
 * ------------------------------------------------------------------
 * Cliente minimalista (sin dependencias externas, solo cURL nativo de PHP)
 * para consumir la API de PlacetoPay Web Checkout.
 *
 * Documentacion oficial consultada:
 *  - Autenticacion:      https://docs.placetopay.dev/checkout/authentication/
 *  - Crear sesion:       https://docs.placetopay.dev/en/checkout/api/reference/session
 *  - Flujo de Checkout:  https://docs.placetopay.dev/en/checkout/how-checkout-works
 *
 * Flujo transaccional implementado:
 *   1. createSession()  -> POST {baseUrl}/api/session
 *        Crea la sesion de pago y devuelve un "processUrl" al que se debe
 *        redirigir al comprador para que ingrese los datos de pago en la
 *        pagina alojada por PlacetoPay (PCI-compliant, fuera de nuestro
 *        alcance como comercio).
 *   2. querySession()   -> POST {baseUrl}/api/session/{requestId}
 *        Una vez el comprador vuelve al returnUrl, se consulta el estado
 *        final de la sesion (APPROVED, PENDING, REJECTED, etc.)
 */
class PlacetoPayClient
{
    private string $login;
    private string $secretKey;
    private string $baseUrl;

    public function __construct(string $login, string $secretKey, string $baseUrl)
    {
        $this->login = $login;
        $this->secretKey = $secretKey;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Construye el bloque "auth" requerido en cada llamada.
     *
     * Formula oficial:
     *   tranKey = Base64( SHA256_raw( nonce_crudo + seed + secretKey ) )
     *   nonce   = Base64( nonce_crudo )   // se envia codificado, se firma crudo
     *   seed    = fecha/hora actual en ISO-8601
     *
     * El nonce crudo debe ser aleatorio y unico por peticion (evita repeticion
     * de solicitudes / replay attacks). El seed no puede diferir del reloj del
     * servidor de PlacetoPay en mas de 5 minutos.
     */
    private function buildAuth(): array
    {
        $rawNonce = random_bytes(16);
        $seed = date('c'); // ISO 8601, ej: 2026-09-29T10:15:00-05:00

        $tranKey = base64_encode(hash('sha256', $rawNonce . $seed . $this->secretKey, true));
        $nonce = base64_encode($rawNonce);

        return [
            'login' => $this->login,
            'tranKey' => $tranKey,
            'nonce' => $nonce,
            'seed' => $seed,
        ];
    }

    /**
     * POST /api/session
     *
     * Parametros minimos necesarios para el flujo basico:
     *  - auth        (obligatorio) credenciales firmadas de la peticion
     *  - ipAddress   (obligatorio) IP del comprador
     *  - userAgent   (obligatorio) user agent del comprador
     *  - returnUrl   (obligatorio) URL a la que PlacetoPay redirige al terminar
     *  - payment     (opcional pero necesario para cobrar) referencia, descripcion y monto
     *  - expiration  (opcional) vigencia de la sesion, minimo 5 minutos en el futuro
     *
     * @param array $payment  ['reference' => string, 'description' => string, 'currency' => string, 'amount' => float]
     * @param string $returnUrl
     */
    public function createSession(array $payment, string $returnUrl): array
    {
        $body = [
            'auth' => $this->buildAuth(),
            'locale' => 'es_CO',
            'payment' => [
                'reference' => $payment['reference'],
                'description' => $payment['description'],
                'amount' => [
                    'currency' => $payment['currency'],
                    'total' => $payment['amount'],
                ],
            ],
            'expiration' => date('c', strtotime('+30 minutes')),
            'returnUrl' => $returnUrl,
            'ipAddress' => $payment['ipAddress'] ?? '127.0.0.1',
            'userAgent' => $payment['userAgent'] ?? 'PruebaTecnicaEvertec/1.0',
        ];

        return $this->post('/api/session', $body);
    }

    /**
     * POST /api/session/{requestId}
     *
     * Consulta el estado final de una sesion ya creada. El unico parametro
     * requerido en el cuerpo es el bloque "auth"; el identificador de la
     * sesion (requestId) viaja en la URL.
     */
    public function querySession(int $requestId): array
    {
        $body = [
            'auth' => $this->buildAuth(),
        ];

        return $this->post('/api/session/' . $requestId, $body);
    }

    private function post(string $path, array $body): array
    {
        $ch = curl_init($this->baseUrl . $path);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            // En Windows, PHP no siempre trae un CA bundle configurado por
            // defecto, lo que provoca el error "unable to get local issuer
            // certificate" al validar el certificado TLS de PlacetoPay.
            // Se usa el bundle oficial de Mozilla publicado por curl.se.
            CURLOPT_CAINFO => __DIR__ . '/cacert.pem',
        ]);

        $raw = curl_exec($ch);

        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Error de conexion con PlacetoPay: {$error}");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            throw new RuntimeException("Respuesta invalida de PlacetoPay (HTTP {$httpCode}): {$raw}");
        }

        $decoded['_httpCode'] = $httpCode;

        return $decoded;
    }
}
