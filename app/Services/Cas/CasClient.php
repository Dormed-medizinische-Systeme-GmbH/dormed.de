<?php

namespace App\Services\Cas;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the CAS genesisWorld REST API (v7.0).
 *
 * Every request/response is logged to the `api` channel, since the API's own
 * OpenAPI documentation is incomplete for several endpoints (some payload/
 * response schemas are wrong or explicitly marked "currently undocumented")
 * - having the raw exchange on disk is what makes that workable in practice.
 */
class CasClient
{
    /**
     * Candidate field names for the GUID returned by a create call, tried in
     * order. "GGUID" matches the "dataObjectGGUID" naming used throughout
     * the API for existing records; the others are defensive fallbacks.
     *
     * @var list<string>
     */
    private const GUID_CANDIDATE_KEYS = ['GGUID', 'guid', 'Guid', 'id', 'Id'];

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withBasicAuth(
                (string) config('services.cas_genesis_world.username'),
                (string) config('services.cas_genesis_world.password'),
            )
            ->withHeaders([
                'X-CAS-PRODUCT-KEY' => (string) config('services.cas_genesis_world.product_key'),
            ])
            ->acceptJson()
            ->asJson();
    }

    /**
     * CAS_GENESIS_WORLD_HOST is meant to be entered with a scheme, but a
     * bare host/path (as configured once in production) silently breaks
     * every request with a Guzzle "URI must include a scheme and host"
     * error - defaulting to https here turns a config typo into a working
     * request instead of a hard failure.
     */
    private function baseUrl(): string
    {
        $host = rtrim((string) config('services.cas_genesis_world.host'), '/');

        return preg_match('#^https?://#i', $host) === 1 ? $host : "https://{$host}";
    }

    /**
     * Create a data object of the given type. Returns the new record's GUID
     * for logging purposes, or null if the call succeeded but no recognizable
     * GUID field or Location header was found in the response (see
     * extractGuid() - already logged a warning to api.log in that case).
     * A failed HTTP call (network error, non-2xx) throws instead, since
     * that *is* worth retrying.
     *
     * @param  array<string, mixed>  $fields
     *
     * @throws CasRequestFailedException
     */
    public function createDataObject(string $dataObjectType, array $fields): ?string
    {
        // The API only accepts "fields" (plus permission-related keys we
        // don't use) at the top level of the body - the actual field values
        // must be nested under it, not passed flat.
        $payload = ['fields' => $fields];

        // tag-as-recently-used is a required query param on this endpoint;
        // irrelevant for a background integration, so always false.
        $response = $this->client()
            ->post("/v7.0/type/{$dataObjectType}?tag-as-recently-used=false", $payload);

        $this->logExchange('POST', "/v7.0/type/{$dataObjectType}", $payload, $response);

        if ($response->failed()) {
            throw new CasRequestFailedException(
                "CAS create request for [{$dataObjectType}] failed with status {$response->status()}."
            );
        }

        return $this->extractGuid($response);
    }

    /**
     * Fetch all records of a saved CAS view.
     *
     * @return list<array<string, mixed>>
     *
     * @throws CasRequestFailedException
     */
    public function listView(string $dataObjectType, string $viewId): array
    {
        return $this->getJsonList("/v7.0/type/{$dataObjectType}/view/{$viewId}/list");
    }

    /**
     * Fetch the dossier (linked documents) of a record.
     *
     * @return list<array<string, mixed>>
     *
     * @throws CasRequestFailedException
     */
    public function dossier(string $dataObjectType, string $guid): array
    {
        return $this->getJsonList("/v7.0/type/{$dataObjectType}/{$guid}/dossier/full");
    }

    /**
     * Download the binary file of a CAS document. The body is not logged.
     *
     * @throws CasRequestFailedException
     */
    public function documentFile(string $documentGuid): string
    {
        try {
            $response = $this->client()->timeout(30)->get("/v7.0/type/document/{$documentGuid}/file");
        } catch (ConnectionException $exception) {
            throw new CasRequestFailedException("CAS document download [{$documentGuid}] failed: {$exception->getMessage()}", previous: $exception);
        }

        if ($response->failed()) {
            throw new CasRequestFailedException(
                "CAS document download [{$documentGuid}] failed with status {$response->status()}."
            );
        }

        return $response->body();
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws CasRequestFailedException
     */
    private function getJsonList(string $path): array
    {
        try {
            $response = $this->client()->timeout(15)->get($path);
        } catch (ConnectionException $exception) {
            throw new CasRequestFailedException("CAS GET [{$path}] failed: {$exception->getMessage()}", previous: $exception);
        }

        // Reads belong to the webhook-triggered sync: logged to the webhook
        // channel, and only as a summary - the body would repeat the whole
        // device list (serial numbers etc.) on every webhook.
        $body = $response->json();

        Log::channel('webhook')->info("CAS GET {$path}", [
            'status' => $response->status(),
            'count' => is_array($body) ? count($body) : null,
        ]);

        if ($response->failed()) {
            throw new CasRequestFailedException("CAS GET [{$path}] failed with status {$response->status()}.");
        }

        return is_array($body) ? array_values($body) : [];
    }

    private function extractGuid(Response $response): ?string
    {
        $body = $response->json();

        if (is_array($body)) {
            foreach (self::GUID_CANDIDATE_KEYS as $key) {
                if (is_string($body[$key] ?? null) && $body[$key] !== '') {
                    return $body[$key];
                }
            }
        }

        // CAS answers a successful create with 201 Created and an empty
        // body - the GUID comes back as the last path segment of the
        // Location header instead (standard REST convention for 201s).
        $location = $response->header('Location');

        if ($location !== '' && $location !== null) {
            $guid = trim((string) parse_url($location, PHP_URL_PATH), '/');
            $guid = str($guid)->afterLast('/')->toString();

            if ($guid !== '') {
                return $guid;
            }
        }

        Log::channel('api')->warning('CAS create response did not contain a recognizable GUID field or Location header.', [
            'triedKeys' => self::GUID_CANDIDATE_KEYS,
            'body' => $body ?? $response->body(),
            'location' => $location,
        ]);

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function logExchange(string $method, string $path, array $payload, Response $response): void
    {
        Log::channel('api')->info("CAS {$method} {$path}", [
            'request' => $payload,
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
            'location' => $response->header('Location'),
        ]);
    }
}
