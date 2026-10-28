<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

/** Laravel-style mutations for a model-free FS query builder. */
trait MutationQueries
{
    public function update(array $values): int
    {
        if (!$this->unrestrictedWrites) {
            throw new \BadMethodCallException('Query-level update() is available through FS::collection(); update model results or use updateMany() instead.');
        }
        if ($values === []) {
            throw new \InvalidArgumentException('No fields were provided for update.');
        }
        if (array_key_exists($this->primaryKey, $values)) {
            throw new \InvalidArgumentException('The primary key cannot be changed by a query update.');
        }

        $documents = $this->matchingDocuments();
        if ($documents->isEmpty()) {
            return 0;
        }

        return $this->updateMany($documents->map(function ($document) use ($values): array {
            return array_merge([$this->primaryKey => $document->getAttribute($this->primaryKey)], $values);
        })->all());
    }

    public function delete(): int
    {
        if (!$this->unrestrictedWrites) {
            throw new \BadMethodCallException('Query-level delete() is available through FS::collection(); delete an individual model result instead.');
        }

        $ids = $this->matchingDocuments()
            ->map(fn ($document) => $document->getAttribute($this->primaryKey))
            ->filter(fn ($id) => is_string($id) || is_int($id))
            ->values()
            ->all();

        return $this->deleteMany($ids);
    }

    private function matchingDocuments()
    {
        $query = $this->getQuery();

        return empty($query['structuredQuery']) ? $this->all() : $this->get();
    }
}
