<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

trait UpdateMethod
{
    /**
     * Update documents using Firestore commit batches instead of one request
     * per document. Each group of at most 500 writes is atomic.
     *
     * @param array<int, array<string, mixed>> $documents
     */
    public function updateMany(array $documents): int
    {
        if ($documents === []) {
            return 0;
        }

        $writes = [];
        foreach ($documents as $index => $document) {
            if (!is_array($document)) {
                throw new \InvalidArgumentException("Batch item [{$index}] must be an array.");
            }

            $id = $document[$this->primaryKey] ?? null;
            if (!is_string($id) && !is_int($id)) {
                throw new \InvalidArgumentException("Batch item [{$index}] is missing [{$this->primaryKey}].");
            }
            unset($document[$this->primaryKey]);

            if (!$this->unrestrictedWrites) {
                $unknownFields = array_diff(array_keys($document), $this->fillable);
                if ($unknownFields !== []) {
                    throw new \InvalidArgumentException(
                        "Batch item [{$index}] contains non-fillable fields: " . implode(', ', $unknownFields)
                    );
                }
            }
            if ($document === []) {
                throw new \InvalidArgumentException("Batch item [{$index}] has no fields to update.");
            }

            $writes[] = [
                'update' => array_merge(
                    ['name' => $this->firestoreDocumentName($id)],
                    $this->convertToFirestoreFormat($document)
                ),
                'updateMask' => [
                    'fieldPaths' => array_map(
                        fn (string $field): string => $this->firestoreFieldPath($field),
                        array_keys($document)
                    ),
                ],
                'currentDocument' => ['exists' => true],
            ];
        }

        if ($this->transactionContext !== null) {
            foreach ($writes as $write) {
                $this->transactionContext->queueWrite($write);
            }
        } else {
            foreach (array_chunk($writes, 500) as $batch) {
                $this->commitWrites($batch);
            }
        }

        return count($writes);
    }

    /** Delete documents by primary key using Firestore commit batches. */
    public function deleteMany(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $writes = [];
        foreach (array_values(array_unique($ids, SORT_REGULAR)) as $index => $id) {
            if (!is_string($id) && !is_int($id)) {
                throw new \InvalidArgumentException("Delete item [{$index}] must be a string or integer ID.");
            }

            $writes[] = ['delete' => $this->firestoreDocumentName($id)];
        }

        if ($this->transactionContext !== null) {
            foreach ($writes as $write) {
                $this->transactionContext->queueWrite($write);
            }
        } else {
            foreach (array_chunk($writes, 500) as $batch) {
                $this->commitWrites($batch);
            }
        }

        return count($writes);
    }
}
