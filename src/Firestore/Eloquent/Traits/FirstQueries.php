<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

trait FirstQueries
{
    public function first()
    {
        $query = $this->getQuery(1);
        $models = $this->postRequest(':runQuery', $query, false)?->all() ?? [];
        return $this->eagerLoadRelations($models)[0] ?? null;
    }

    public function firstOrFail()
    {
        return $this->first() ?? throw new \Exception("No data found");
    }

    public function find($id)
    {
        $model = $this->getRequest($this->collection, true, true, $id)?->first() ?? null;

        return $model ? $this->eagerLoadRelations([$model])[0] : null;
    }

    public function findOrFail($id)
    {
        return $this->find($id) ?? throw new \Exception("No data found");
    }
}
