<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Firestore\Exceptions;

use RuntimeException;

final class FirestoreRequestException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $responseBody
    ) {
        parent::__construct("Firestore request failed with HTTP {$status}: {$responseBody}", $status);
    }

    public function isAborted(): bool
    {
        if ($this->status !== 409) {
            return false;
        }

        $payload = json_decode($this->responseBody, true);

        return strtoupper((string) ($payload['error']['status'] ?? '')) === 'ABORTED'
            || str_contains(strtoupper($this->responseBody), 'ABORTED');
    }
}
