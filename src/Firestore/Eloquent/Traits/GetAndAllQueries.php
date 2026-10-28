<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

trait GetAndAllQueries
{
    public function all()
    {
        if (!empty($this->structuredQuery['structuredQuery'])) {
            throw new \Exception("Use get() method instead of all() if you want to execute a query.");
        }
        return collect($this->eagerLoadRelations($this->getRequest($this->collection)->all()));
    }

    public function get()
    {
        $query = $this->getQuery();
        if (!empty($query['structuredQuery'])) {
            $models = $this->postRequest(':runQuery', $query, false)?->all() ?? [];
            return collect($this->eagerLoadRelations($models));
        }

        throw new \Exception("No query to execute");
    }

    public function exists(): bool
    {
        return $this->first() !== null;
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    public function value(string $column): mixed
    {
        if (trim($column) === '') {
            throw new \InvalidArgumentException('The value column cannot be empty.');
        }

        return $this->select([$column])->first()?->getAttribute($column);
    }

    public function pluck(string $column, ?string $key = null)
    {
        if (trim($column) === '' || ($key !== null && trim($key) === '')) {
            throw new \InvalidArgumentException('Pluck columns must be non-empty strings.');
        }

        $fields = $key === null ? [$column] : [$column, $key];
        $values = $this->select($fields)->get()->mapWithKeys(function ($document, int $index) use ($column, $key): array {
            $value = $document->getAttribute($column);

            return $key === null
                ? [$index => $value]
                : [$document->getAttribute($key) => $value];
        });

        return $key === null ? $values->values() : $values;
    }
}
