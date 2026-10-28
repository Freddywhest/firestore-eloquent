<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use RuntimeException;

/** Internal REST operations used by FirestoreManager transactions. */
trait TransactionQueries
{
    public function beginTransaction(): string
    {
        $response = $this->sendFirestoreRequest(
            'post',
            $this->baseDocumentsUrl() . ':beginTransaction',
            ['options' => ['readWrite' => (object) []]]
        );
        $this->ensureSuccessful($response);

        $transaction = $response->json('transaction');
        if (!is_string($transaction) || $transaction === '') {
            throw new RuntimeException('Firestore did not return a transaction identifier.');
        }

        return $transaction;
    }

    /** @return array<string, mixed> */
    public function commitTransaction(string $transaction, array $writes): array
    {
        $response = $this->sendFirestoreRequest(
            'post',
            $this->baseDocumentsUrl() . ':commit',
            ['writes' => array_values($writes), 'transaction' => $transaction]
        );
        $this->ensureSuccessful($response);

        return $response->json();
    }

    public function rollbackTransaction(string $transaction): void
    {
        $response = $this->sendFirestoreRequest(
            'post',
            $this->baseDocumentsUrl() . ':rollback',
            ['transaction' => $transaction]
        );
        $this->ensureSuccessful($response);
    }
}
