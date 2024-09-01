<?php

/**
 * This trait provides the functionality to find a document in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 */

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers;

use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\ToArrayHelper;

trait FirstOrFailTrait
{
    use FirestoreConnectionTrait;

    /**
     * Find a document by its ID in Firestore.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     * @param  mixed  $collection  The collection to search in.
     * @param  mixed  $firestore  The Firestore instance to use.
     * @param  mixed  $model  The model to use.
     * @param  string  $primaryKey  The primary key of the model.
     * @param  array  $fillable  The fillable attributes of the model.
     * @param  array  $required  The required attributes of the model.
     * @param  array  $fieldTypes  The field types of the model.
     * @return Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\IToArrayHelper
     *
     * @throws \Exception If a required attribute is missing or if an attribute has an invalid type.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the document is not found.
     */
    public function fFirstOrFail($query, $collection, $model, $path, $direction)
    {
        if ($query) {
            if ($path) {
                if (! $direction) {
                    $newQuery = $query->orderBy($path)->limit(1);
                } else {
                    $newQuery = $query->orderBy($path, $direction)->limit(1);
                }
            } else {
                $newQuery = $query->limit(1);
            }
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

        return new ToArrayHelper(queryRaw: $newQuery, model: $model, collection: $collection, single: 'firstOrFail');
    }
}
