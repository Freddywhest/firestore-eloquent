<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

trait GetAndAllQueries
{
    public function all()
    {
        if (!empty($this->structuredQuery['structuredQuery'])) {
            throw new \Exception("Use get() method instead of all() if you want to execute a query.");
        }
        return $this->getRequest($this->collection);
    }

    public function get()
    {
        $query = $this->getQuery();
        if (!empty($query['structuredQuery'])) {
            return $this->postRequest(':runQuery', $query, false)?->all() ?? [];
        }

        throw new \Exception("No query to execute");
    }
}
