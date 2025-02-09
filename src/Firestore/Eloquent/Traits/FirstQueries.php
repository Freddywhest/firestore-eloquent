<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

trait FirstQueries
{
    public function first()
    {
        $query = $this->getQuery(1);
        return $this->postRequest(':runQuery', $query, false)?->first() ?? null;
    }

    public function firstOrFail()
    {
        $query = $this->getQuery(1);
        return $this->postRequest(':runQuery', $query, false)?->first() ?? throw new \Exception("No data found");
    }

    public function find($id)
    {
        try {
            return $this->getRequest($this->collection, true, true, $id) ?? null;
        } catch (\Throwable $th) {
            throw new \Exception("Error finding data: " . $th->getMessage());
        }
    }

    public function findOrFail($id)
    {
        try {
            return $this->getRequest($this->collection, true, true, $id) ?? throw new \Exception("No data found");
        } catch (\Throwable $th) {
            throw new \Exception("No data found");
        }
    }
}
