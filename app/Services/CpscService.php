<?php

namespace App\Services;

use App\Models\CpscToken;
use Exception;

class CpscService
{
    protected string $baseUrl;
    protected ?string $certifierId;
    protected ?string $collectionId;

    public function __construct()
    {
        // Reads directly from config or .env
        $this->baseUrl = config('services.cpsc.api_url', env('CPSC_API_URL', 'https://efiling.saferproducts.gov/efiling/api'));
        $this->certifierId = config('services.cpsc.certifier_id', env('CPSC_CERTIFIER_ID'));
        $this->collectionId = config('services.cpsc.collection_id', env('CPSC_COLLECTION_ID'));
    }

    public function getCertifierId(?string $override = null): string
    {
        $id = $override ?: $this->certifierId;
        if (!$id) {
            throw new Exception("CPSC Certifier ID is not configured. Please check CPSC_CERTIFIER_ID in your .env.");
        }
        return $id;
    }

    public function getCollectionId(?string $override = null): string
    {
        $id = $override ?: $this->collectionId;
        if (!$id) {
            throw new Exception("CPSC Collection ID is not configured. Please check CPSC_COLLECTION_ID in your .env.");
        }
        return $id;
    }

    /**
     * Fetch active credentials from database, fallback to config/env.
     */
    public function getValidCredentials(): object
    {
        $credential = CpscToken::latest()->first();

        if ($credential) {
            if ($credential->expires_at && $credential->expires_at->isPast()) {
                throw new Exception("The CPSC token expired on {$credential->expires_at->toDateTimeString()}.");
            }
            return (object)[
                'token' => $credential->token,
                'api_secret' => $credential->api_secret,
            ];
        }

        // Fallback to .env / config
        $token = config('services.cpsc.token', env('CPSC_API_TOKEN'));
        $secret = config('services.cpsc.secret', env('CPSC_API_SECRET'));

        if (!$token || !$secret) {
            throw new Exception("No CPSC token found in the database or .env file.");
        }

        return (object)[
            'token' => $token,
            'api_secret' => $secret,
        ];
    }

    /**
     * Generic API caller for all endpoints using native PHP cURL.
     */
    public function callApi(string $endpoint, string $method = 'GET', array $data = []): array
    {
        $credential = $this->getValidCredentials();
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        // Ensure token does NOT contain duplicate "Bearer " prefix for Authorization: <JWT> header
        $jwt = preg_replace('/^Bearer\s+/i', '', trim($credential->token));

        $headers = [
            'Authorization: ' . $jwt,
            'x-api-secret: '  . $credential->api_secret,
            'Content-Type: application/json',
            'Accept: */*',
        ];

        $ch = curl_init();

        $curlOptions = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_SSL_VERIFYPEER => true,
        ];

        // Portable CA bundle lookup: check env CPSC_CA_PATH, or PHP/OpenSSL cafile ini settings
        $caPath = env('CPSC_CA_PATH', ini_get('curl.cainfo') ?: ini_get('openssl.cafile'));
        if (!empty($caPath) && file_exists($caPath)) {
            $curlOptions[CURLOPT_CAINFO] = $caPath;
        }

        if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $curlOptions);

        $responseBody = curl_exec($ch);
        $curlErrno    = curl_errno($ch);
        $curlError    = curl_error($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($curlErrno) {
            throw new Exception("cURL Error [{$curlErrno}]: {$curlError}");
        }

        $json = json_decode($responseBody, true);

        // Throw exception for ALL HTTP status >= 400
        if ($httpCode >= 400) {
            $msg = is_array($json) && !empty($json['statusMessage']) 
                ? $json['statusMessage'] 
                : (is_array($json) && !empty($json['message']) ? $json['message'] : $responseBody);
            throw new Exception("CPSC API Error [HTTP {$httpCode}]: {$msg}");
        }

        return is_array($json) ? $json : ['raw' => $responseBody, 'statusCode' => $httpCode];
    }

    /**
     * 1. Check token expiration directly from CPSC
     * GET /efiling/api/getTokenExpiration
     */
    public function getTokenExpiration(): array
    {
        return $this->callApi('getTokenExpiration', 'GET');
    }

    /**
     * Endpoint: getCollections
     * GET /efiling/api/getCollections
     */
    public function getCollections(): array
    {
        return $this->callApi('getCollections', 'GET');
    }

    /**
     * 2. Import Products into eFiling
     * POST /efiling/api/import?certifierId=...&collectionId=...&doCertify=...
     * Request body: {"productList": [...]}
     */
    public function importProducts(array $products, ?bool $doCertify = null, ?string $certifierId = null, ?string $collectionId = null): array
    {
        $doCertifyBool = $doCertify ?? config('services.cpsc.do_certify', true);

        $queryParams = http_build_query([
            'certifierId' => $this->getCertifierId($certifierId),
            'collectionId' => $this->getCollectionId($collectionId),
            'doCertify'    => $doCertifyBool ? 'true' : 'false',
        ]);

        return $this->callApi("import?{$queryParams}", 'POST', [
            'productList' => $products,
        ]);
    }

    /**
     * 3. Get Import Status
     * GET /efiling/api/getImportStatus?importId=...
     */
    public function getImportStatus(string $importId): array
    {
        return $this->callApi("getImportStatus?importId=" . urlencode($importId), 'GET');
    }

    /**
     * 4. Get Import Log by Import ID
     * GET /efiling/api/getImportLog?importId=...&errorsOnly=...
     */
    public function getImportLog(string $importId, bool $errorsOnly = false): array
    {
        $queryParams = http_build_query([
            'importId'   => $importId,
            'errorsOnly' => $errorsOnly ? 'true' : 'false',
        ]);
        return $this->callApi("getImportLog?{$queryParams}", 'GET');
    }

    /**
     * 5. Get Certificates
     * POST /efiling/api/getCertificates?certifierId=...&collectionId=...
     * Request body: [{"productId": "...", "version": "..."}]
     */
    public function getCertificates(array $productTuples, ?string $certifierId = null, ?string $collectionId = null): array
    {
        $queryParams = http_build_query([
            'certifierId' => $this->getCertifierId($certifierId),
            'collectionId' => $this->getCollectionId($collectionId),
        ]);

        return $this->callApi("getCertificates?{$queryParams}", 'POST', $productTuples);
    }

    /**
     * 6. Get Trade Parties
     * GET /efiling/api/getTradeParties?certifierId=...&collectionId=...&tradePartyType=...
     */
    public function getTradeParties(?string $tradePartyType = null, ?string $certifierId = null, ?string $collectionId = null): array
    {
        $params = [
            'certifierId' => $this->getCertifierId($certifierId),
        ];

        if ($collectionId || $this->collectionId) {
            $params['collectionId'] = $this->getCollectionId($collectionId);
        }

        if ($tradePartyType) {
            $params['tradePartyType'] = $tradePartyType;
        }

        $queryString = http_build_query($params);
        return $this->callApi("getTradeParties?{$queryString}", 'GET');
    }

    /**
     * Export (Synchronous, max 10,000 certificates)
     * GET /efiling/api/export?certifierId=...&collectionId=...
     */
    public function export(array $filters = [], ?string $certifierId = null, ?string $collectionId = null): array
    {
        $params = array_merge([
            'certifierId' => $this->getCertifierId($certifierId),
            'collectionId' => $this->getCollectionId($collectionId),
        ], $filters);

        $queryString = http_build_query($params);
        return $this->callApi("export?{$queryString}", 'GET');
    }

    /**
     * Export Asynchronous
     * GET /efiling/api/exportAsync?certifierId=...&collectionId=...
     */
    public function exportAsync(array $filters = [], ?string $certifierId = null, ?string $collectionId = null): array
    {
        $params = array_merge([
            'certifierId' => $this->getCertifierId($certifierId),
            'collectionId' => $this->getCollectionId($collectionId),
        ], $filters);

        $queryString = http_build_query($params);
        return $this->callApi("exportAsync?{$queryString}", 'GET');
    }

    /**
     * Export Async Status
     * GET /efiling/api/exportAsyncStatus?exportId=...
     */
    public function exportAsyncStatus(string $exportId): array
    {
        return $this->callApi("exportAsyncStatus?exportId=" . urlencode($exportId), 'GET');
    }

    /**
     * Export Async Data
     * GET /efiling/api/exportAsyncData?exportId=...
     */
    public function exportAsyncData(string $exportId): array
    {
        return $this->callApi("exportAsyncData?exportId=" . urlencode($exportId), 'GET');
    }
}