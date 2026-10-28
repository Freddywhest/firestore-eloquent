<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Firestore;

use InvalidArgumentException;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FClient;
use Roddy\FirestoreEloquent\Firestore\Exceptions\FirestoreRequestException;

/** Creates model-free Firestore query builders for the FS facade. */
final class FirestoreManager
{
    private ?FirestoreTransaction $activeTransaction = null;

    public function collection(string $collection, string $primaryKey = 'id'): FClient
    {
        $client = $this->newClient($collection, $primaryKey);

        return $this->activeTransaction === null
            ? $client
            : $client->withinTransaction($this->activeTransaction);
    }

    private function newClient(string $collection, string $primaryKey = 'id'): FClient
    {
        $collection = trim($collection);
        $primaryKey = trim($primaryKey);

        if ($collection === '' || $collection !== trim($collection, '/') || str_contains($collection, '//')) {
            throw new InvalidArgumentException('Firestore collection paths must be non-empty and cannot contain empty path segments.');
        }

        $segments = explode('/', $collection);
        if (count($segments) % 2 === 0) {
            throw new InvalidArgumentException('A Firestore collection path must end with a collection segment.');
        }

        if ($primaryKey === '' || str_contains($primaryKey, '/')) {
            throw new InvalidArgumentException('The primary key must be a non-empty field name.');
        }

        return new FClient(
            collection: $collection,
            primaryKey: $primaryKey,
            fillable: [],
            required: [],
            default: [],
            fieldTypes: [],
            model: 'FirestoreDocument',
            hidden: [],
            modelClass: UntypedFirestoreDocument::class,
            unrestrictedWrites: true
        );
    }

    /** Laravel DB::table()-style alias. */
    public function table(string $collection, string $primaryKey = 'id'): FClient
    {
        return $this->collection($collection, $primaryKey);
    }

    public function beginTransaction(): FirestoreTransaction
    {
        if ($this->activeTransaction !== null) {
            throw new \LogicException('A Firestore transaction is already active in this execution context.');
        }

        $control = $this->newClient('__firestore_transactions');
        $this->activeTransaction = new FirestoreTransaction(
            manager: $this,
            controlClient: $control,
            transactionId: $control->beginTransaction()
        );

        return $this->activeTransaction;
    }

    /** @return array<string, mixed> */
    public function commit(): array
    {
        $transaction = $this->requireActiveTransaction();
        $result = $transaction->commit();
        $this->activeTransaction = null;

        return $result;
    }

    public function rollback(): void
    {
        if ($this->activeTransaction === null) {
            return;
        }

        $transaction = $this->activeTransaction;

        try {
            $transaction->rollback();
        } finally {
            $this->activeTransaction = null;
        }
    }

    public function currentTransaction(): ?FirestoreTransaction
    {
        return $this->activeTransaction;
    }

    public function inTransaction(): bool
    {
        return $this->activeTransaction !== null;
    }

    /**
     * Run a read-write Firestore transaction, retrying the full callback when
     * Firestore reports an ABORTED contention conflict.
     */
    public function transaction(callable $callback, int $attempts = 5): mixed
    {
        if ($attempts < 1 || $attempts > 20) {
            throw new InvalidArgumentException('Transaction attempts must be between 1 and 20.');
        }

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $transaction = $this->beginTransaction();

            try {
                $result = $callback($transaction);
                $this->commit();

                return $result;
            } catch (\Throwable $exception) {
                if ($this->activeTransaction !== null) {
                    $transaction->rollbackSilently();
                    $this->activeTransaction = null;
                }

                if ($exception instanceof FirestoreRequestException && $exception->isAborted() && $attempt < $attempts) {
                    usleep(100000 * $attempt);
                    continue;
                }

                throw $exception;
            }
        }

        throw new \RuntimeException('Firestore transaction attempts were exhausted.');
    }

    private function requireActiveTransaction(): FirestoreTransaction
    {
        if ($this->activeTransaction === null) {
            throw new \LogicException('No Firestore transaction is active. Call FS::beginTransaction() first.');
        }

        return $this->activeTransaction;
    }
}

/** Marker class used by model-free query results. */
final class UntypedFirestoreDocument
{
}
