<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

trait OtherQueries
{
    public function orderBy($field, $direction = "ASC")
    {
        if (!is_string($field) || trim($field) === '') {
            throw new \InvalidArgumentException('Order field must be a non-empty string.');
        }
        if (!in_array(strtolower($direction), ["asc", "desc"])) {
            throw new \Exception("Invalid direction: " . $direction);
        }
        $this->orderBy[] = [
            "field" => ["fieldPath" => $field],
            "direction" => strtolower($direction) == "asc" ? "ASCENDING" : "DESCENDING"
        ];
        return $this;
    }

    public function orderByDesc($field)
    {
        return $this->orderBy($field, "DESC");
    }

    public function orderByAsc($field)
    {
        return $this->orderBy($field, "ASC");
    }

    public function limit(int $limit)
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException('Limit must be greater than or equal to zero.');
        }
        $this->limit = (int) $limit;
        return $this;
    }

    public function offset(int $offset)
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset must be greater than or equal to zero.');
        }
        $this->offset = (int) $offset;
        return $this;
    }

    public function select(array|string $fields, string ...$additionalFields)
    {
        $fields = is_array($fields) ? $fields : [$fields, ...$additionalFields];
        $arrangedField = [];
        foreach ($fields as $field) {
            if (!is_string($field) || trim($field) === '') {
                throw new \InvalidArgumentException('Selected field names must be non-empty strings.');
            }
            $arrangedField[] = [
                "fieldPath" => $field
            ];
        }
        $this->select = $arrangedField;
        return $this;
    }

    private function startAfterValues(array $values): static
    {
        $this->startAfter = array_values($values);
        return $this;
    }
}
