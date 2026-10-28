<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Firestore;

use Illuminate\Support\Str;
use LogicException;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FClient;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreValue;

/** A single Firestore read-write transaction attempt. */
final class FirestoreTransaction
{
    /** @var array<int, array<string, mixed>> */
    private array $writes = [];
    private bool $writesStarted = false;
    private bool $completed = false;

    public function __construct(
        private readonly FirestoreManager $manager,
        private readonly FClient $controlClient,
        private readonly string $transactionId
    ) {
    }

    public function collection(string $collection, string $primaryKey = 'id'): FClient
    {
        $this->assertActive();

        return $this->manager->collection($collection, $primaryKey);
    }

    public function table(string $collection, string $primaryKey = 'id'): FClient
    {
        return $this->collection($collection, $primaryKey);
    }

    public function insert(string $collection, array $values, string $primaryKey = 'id'): string|int
    {
        return $this->create($collection, $values, $primaryKey);
    }

    public function create(string $collection, array $values, string $primaryKey = 'id'): string|int
    {
        $this->beginWrites();
        $client = $this->manager->collection($collection, $primaryKey);
        $id = $values[$primaryKey] ?? str_replace('-', '', (string) Str::uuid()) . str_replace('.', '', (string) microtime(true));

        if (!is_string($id) && !is_int($id)) {
            throw new \InvalidArgumentException("The transaction primary key [{$primaryKey}] must be a string or integer.");
        }
        $values[$primaryKey] = $id;

        $this->appendWrite([
            'update' => array_merge(
                ['name' => $client->documentNameFor($id)],
                $this->encodeDocument($values)
            ),
            'currentDocument' => ['exists' => false],
        ]);

        return $id;
    }

    public function update(
        string $collection,
        string|int $id,
        array $values,
        string $primaryKey = 'id'
    ): static {
        $this->beginWrites();
        if ($values === []) {
            throw new \InvalidArgumentException('A transaction update requires at least one field.');
        }
        if (array_key_exists($primaryKey, $values)) {
            throw new \InvalidArgumentException('The primary key cannot be changed by a transaction update.');
        }

        $client = $this->manager->collection($collection, $primaryKey);
        $this->appendWrite([
            'update' => array_merge(
                ['name' => $client->documentNameFor($id)],
                $this->encodeDocument($values)
            ),
            'updateMask' => [
                'fieldPaths' => array_map($this->fieldPath(...), array_keys($values)),
            ],
            'currentDocument' => ['exists' => true],
        ]);

        return $this;
    }

    public function delete(string $collection, string|int $id, string $primaryKey = 'id'): static
    {
        $this->beginWrites();
        $client = $this->manager->collection($collection, $primaryKey);
        $this->appendWrite(['delete' => $client->documentNameFor($id)]);

        return $this;
    }

    /** @return array<string, mixed> */
    public function commit(): array
    {
        $this->assertActive();
        $result = $this->controlClient->commitTransaction($this->transactionId, $this->writes);
        $this->completed = true;

        return $result;
    }

    public function rollback(): void
    {
        if ($this->completed) {
            return;
        }

        $this->controlClient->rollbackTransaction($this->transactionId);
        $this->completed = true;
    }

    public function rollbackSilently(): void
    {
        try {
            $this->rollback();
        } catch (\Throwable) {
            $this->completed = true;
        }
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function queuedWriteCount(): int
    {
        return count($this->writes);
    }

    public function id(): string
    {
        return $this->transactionId;
    }

    public function assertCanRead(): void
    {
        $this->assertActive();
        if ($this->writesStarted) {
            throw new LogicException('Firestore transaction reads must be executed before writes are queued.');
        }
    }

    private function beginWrites(): void
    {
        $this->assertActive();
        $this->writesStarted = true;
    }

    private function assertActive(): void
    {
        if ($this->completed) {
            throw new LogicException('This Firestore transaction attempt has already completed.');
        }
    }

    private function appendWrite(array $write): void
    {
        if (count($this->writes) >= 500) {
            throw new \OverflowException('A Firestore transaction cannot contain more than 500 writes.');
        }

        $this->writes[] = $write;
    }

    /** Queue a pre-encoded Firestore write from a transaction-aware model client. */
    public function queueWrite(array $write): void
    {
        $this->beginWrites();
        $this->appendWrite($write);
    }

    /** @return array{fields: array<string, array<string, mixed>>} */
    private function encodeDocument(array $values): array
    {
        if ($values === []) {
            throw new \InvalidArgumentException('A Firestore document cannot be empty.');
        }

        $fields = [];
        foreach ($values as $field => $value) {
            if (!is_string($field) || trim($field) === '') {
                throw new \InvalidArgumentException('Firestore field names must be non-empty strings.');
            }
            $fields[$field] = FirestoreValue::encode($value);
        }

        return ['fields' => $fields];
    }

    private function fieldPath(string $field): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field)) {
            return $field;
        }

        return '`' . str_replace(['\\', '`'], ['\\\\', '\\`'], $field) . '`';
    }
}
