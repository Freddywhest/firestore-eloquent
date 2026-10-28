<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Roddy\FirestoreEloquent\Firestore\Exceptions\FirestoreRequestException;

trait FHttpClient
{
    private const FIRESTORE_SCOPE = 'https://www.googleapis.com/auth/datastore';
    private string $firestoreUrl = 'https://firestore.googleapis.com/v1/projects/';

    /** @var array<string, array{token: string, expires_at: int}> */
    private static array $firestoreAccessTokens = [];

    private function getRequest(string $document, bool $usePrefix = true, bool $useParser = true, string|int|null $id = null)
    {
        $this->assertTransactionReadAllowed();
        $url = $this->documentUrl($document, $usePrefix, $id);
        $transactionQuery = $this->transactionId === null ? [] : ['transaction' => $this->transactionId];

        if ($id !== null) {
            $response = $this->sendFirestoreRequest('get', $url, null, $transactionQuery);
            if ($response->status() === 404) {
                return collect([]);
            }
            $this->ensureSuccessful($response);

            return $useParser
                ? collect($this->parseFirestoreJson(['documents' => [$response->json()]]))
                : collect($response->json());
        }

        $documents = [];
        $pageToken = null;

        do {
            $query = ['pageSize' => 1000];
            if ($this->transactionId !== null) {
                $query['transaction'] = $this->transactionId;
            }
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }

            $response = $this->sendFirestoreRequest('get', $url, null, $query);
            if ($response->status() === 404) {
                return collect([]);
            }
            $this->ensureSuccessful($response);

            $payload = $response->json();
            array_push($documents, ...($payload['documents'] ?? []));
            $pageToken = $payload['nextPageToken'] ?? null;
        } while ($pageToken);

        $payload = ['documents' => $documents];
        return $useParser ? collect($this->parseFirestoreJson($payload)) : collect($payload);
    }

    private function postRequest(string $document, array|object $data, bool $usePrefix = true, bool $useParser = true, bool $isPaginate = false, ?string $param = null)
    {
        if ($this->transactionId !== null && in_array($document, [':runQuery', ':runAggregationQuery'], true)) {
            $this->assertTransactionReadAllowed();
            $data = (array) $data;
            $data['transaction'] = $this->transactionId;
        }

        $segments = explode('/', $this->collection);
        $parent = null;

        if (count($segments) > 1 && !str_contains($document, '/')) {
            array_pop($segments);
            $parent = implode('/', $segments);
        }

        $url = $this->baseDocumentsUrl()
            . ($parent ? '/' . $this->encodePath($parent) : '')
            . ($usePrefix ? '/' : '')
            . (str_starts_with($document, ':') ? $document : $this->encodePath($document))
            . ($param ?? '');

        $response = $this->sendFirestoreRequest('post', $url, $data);
        if ($response->status() === 404) {
            return collect([]);
        }
        $this->ensureSuccessful($response);

        return $useParser
            ? collect($this->parseFirestoreJson($response->json()))
            : collect($response->json());
    }

    private function patchRequest(string $document, array|object $data, bool $usePrefix = true, bool $useParser = true, ?string $id = null, array $fieldPaths = [])
    {
        $parameters = ['currentDocument.exists=true'];
        foreach ($fieldPaths as $fieldPath) {
            $parameters[] = 'updateMask.fieldPaths=' . rawurlencode($this->firestoreFieldPath($fieldPath));
        }
        $url = $this->documentUrl($document, $usePrefix, $id) . '?' . implode('&', $parameters);

        $response = $this->sendFirestoreRequest('patch', $url, $data);
        if ($response->status() === 404) {
            return collect([]);
        }
        $this->ensureSuccessful($response);

        return $useParser
            ? collect($this->parseFirestoreJson(['documents' => [$response->json()]]))
            : collect($response->json());
    }

    private function deleteRequest(string $document, bool $usePrefix = true, bool $useParser = true, ?string $id = null)
    {
        $response = $this->sendFirestoreRequest('delete', $this->documentUrl($document, $usePrefix, $id));
        if ($response->status() === 404) {
            return collect([]);
        }
        $this->ensureSuccessful($response);

        return collect([]);
    }

    /** Commit up to 500 writes atomically through Firestore's REST API. */
    private function commitWrites(array $writes): array
    {
        if ($writes === [] || count($writes) > 500) {
            throw new \InvalidArgumentException('A Firestore commit must contain between 1 and 500 writes.');
        }

        $response = $this->sendFirestoreRequest(
            'post',
            $this->baseDocumentsUrl() . ':commit',
            ['writes' => $writes]
        );
        $this->ensureSuccessful($response);

        return $response->json();
    }

    private function firestoreDocumentName(string|int $id): string
    {
        $id = (string) $id;
        if ($id === '' || str_contains($id, '/')) {
            throw new \InvalidArgumentException('Firestore document IDs must be non-empty and cannot contain a slash.');
        }

        return 'projects/' . $this->FIREBASE_PROJECT_ID
            . '/databases/' . $this->FIREBASE_DATABASE_ID
            . '/documents/' . trim($this->collection, '/') . '/' . $id;
    }

    private function sendFirestoreRequest(string $method, string $url, array|object|null $data = null, array $query = []): Response
    {
        $attempts = 0;

        do {
            $attempts++;
            try {
                $request = $this->firestoreRequest();
                if ($query !== []) {
                    $request = $request->withOptions(['query' => $query]);
                }

                $response = match ($method) {
                    'get' => $request->get($url),
                    'post' => $request->post($url, $data),
                    'patch' => $request->patch($url, $data),
                    'delete' => $request->delete($url),
                    default => throw new RuntimeException("Unsupported HTTP method [{$method}]."),
                };

                if (!in_array($response->status(), [429, 500, 502, 503, 504], true) || $attempts >= 3) {
                    return $response;
                }
            } catch (ConnectionException $exception) {
                if ($attempts >= 3) {
                    throw $exception;
                }
            }

            usleep(100000 * $attempts);
        } while ($attempts < 3);

        throw new RuntimeException('Firestore request failed without a response.');
    }

    private function firestoreRequest(): PendingRequest
    {
        $request = Http::acceptJson()->asJson()->connectTimeout(10)->timeout(30);
        $token = $this->firestoreAccessToken();

        return $token ? $request->withToken($token) : $request;
    }

    private function firestoreAccessToken(): ?string
    {
        if ($this->isFirestoreEmulator()) {
            return null;
        }

        $project = config('firebase.default', 'app');
        $credentialsPath = config("firebase.projects.{$project}.credentials");
        $allowUnauthenticated = (bool) config('firebase.allow_unauthenticated', false);
        $cacheKey = sha1((string) $credentialsPath . '|' . $this->FIREBASE_PROJECT_ID);
        $cached = self::$firestoreAccessTokens[$cacheKey] ?? null;

        if ($cached && $cached['expires_at'] > time() + 60) {
            return $cached['token'];
        }

        try {
            if ($credentialsPath) {
                $path = $this->absoluteCredentialsPath($credentialsPath);
                $json = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                $credentials = new ServiceAccountCredentials([self::FIRESTORE_SCOPE], $json);
            } else {
                $credentials = ApplicationDefaultCredentials::getCredentials([self::FIRESTORE_SCOPE]);
            }

            $token = $credentials->fetchAuthToken();
            if (empty($token['access_token'])) {
                throw new RuntimeException('Google credentials did not return an access token.');
            }

            self::$firestoreAccessTokens[$cacheKey] = [
                'token' => $token['access_token'],
                'expires_at' => time() + (int) ($token['expires_in'] ?? 3600),
            ];

            return $token['access_token'];
        } catch (\Throwable $exception) {
            if ($allowUnauthenticated) {
                return null;
            }

            throw new RuntimeException(
                'Unable to authenticate the Firestore request. Configure FIREBASE_CREDENTIALS or Application Default Credentials.',
                0,
                $exception
            );
        }
    }

    private function absoluteCredentialsPath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }

    private function documentUrl(string $document, bool $usePrefix, string|int|null $id): string
    {
        $url = $this->baseDocumentsUrl() . ($usePrefix ? '/' : '') . $this->encodePath($document);
        if ($id !== null) {
            $url .= '/' . rawurlencode((string) $id);
        }
        return $url;
    }

    private function baseDocumentsUrl(): string
    {
        if ($this->isFirestoreEmulator()) {
            $host = rtrim((string) config('firebase.emulator_host'), '/');
            if (!str_starts_with($host, 'http://') && !str_starts_with($host, 'https://')) {
                $host = 'http://' . $host;
            }
            return $host . '/v1/projects/' . rawurlencode($this->FIREBASE_PROJECT_ID)
                . '/databases/' . rawurlencode($this->FIREBASE_DATABASE_ID) . '/documents';
        }

        return $this->firestoreUrl . rawurlencode($this->FIREBASE_PROJECT_ID)
            . '/databases/' . rawurlencode($this->FIREBASE_DATABASE_ID) . '/documents';
    }

    private function isFirestoreEmulator(): bool
    {
        return (bool) config('firebase.emulator_host');
    }

    private function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', trim($path, '/'))));
    }

    private function firestoreFieldPath(string $field): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field)) {
            return $field;
        }

        return '`' . str_replace(['\\', '`'], ['\\\\', '\\`'], $field) . '`';
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->failed()) {
            throw new FirestoreRequestException($response->status(), $response->body());
        }
    }
}
