<?php

/**
 * This trait provides the functionality to retrieve the first result of a Firestore query and format it into a FirestoreDataFormat object.
 */

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers;

use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\ToArrayHelper;

trait FirstTrait
{
    /**
     * Retrieve the first result of a Firestore query and format it into a FirestoreDataFormat object.
     *
     * @param  object  $query  The Firestore query object.
     * @param  string  $collection  The name of the Firestore collection.
     * @param  string  $model  The name of the Eloquent model.
     * @return Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\IToArrayHelper
     */
    public function fFirst($path, $direction, $query, $collection, $model, $field, $value, $order)
    {
        if ($query) {
            if ($field && $value) {
                $newQuery = $query
                    ->orderBy($field, $order)
                    ->startAt([$value])
                    ->endAt([$value."\uf8ff"])
                    ->limit(1);
            } else {
                if ($path) {
                    if (! $direction) {
                        $newQuery = $query->orderBy($path)->limit(1);
                    } else {
                        $newQuery = $query->orderBy($path, $direction)->limit(1);
                    }
                } else {
                    $newQuery = $query->limit(1);
                }
            }
        } else {
            if ($field && $value) {
                $newQuery = $this->fConnection($this->collection)
                    ->orderBy($field, $order)
                    ->startAt([$value])
                    ->endAt([$value."\uf8ff"])
                    ->limit(1);
            } else {
                if ($path) {
                    if (! $direction) {
                        $newQuery = $this->fConnection($this->collection)->orderBy($path)->limit(1);
                    } else {
                        $newQuery = $this->fConnection($this->collection)->orderBy($path, $direction)->limit(1);
                    }
                } else {
                    $newQuery = $this->fConnection($this->collection)->limit(1);
                }
            }
        }

        return new ToArrayHelper(queryRaw: $newQuery, model: $model, collection: $collection, single: 'first');
    }
}
