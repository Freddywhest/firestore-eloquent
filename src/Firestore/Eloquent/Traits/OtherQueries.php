<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

trait OtherQueries
{
    public function orderBy($field, $direction = "ASC")
    {
        if (!in_array(strtolower($direction), ["asc", "desc"])) {
            throw new \Exception("Invalid direction: " . $direction);
        }
        $this->orderBy = [
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
        $this->limit = (int) $limit;
        return $this;
    }

    public function offset(int $offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    public function select(array $fields)
    {
        foreach ($fields as $field) {
            $arrangedField[] = [
                "fieldPath" => $field
            ];
        }
        $this->select = $arrangedField;
        return $this;
    }
}
