<?php

/**
 * This trait provides the functionality to retrieve Firestore data based on the given parameters.
 * @package Roddy\FirestoreEloquent
 */

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\ToArrayHelper;

trait GetTrait
{
    /**
     * Retrieves Firestore data based on the given parameters.
     *
     * @param string $path The path to order the data by.
     * @param string $direction The direction to order the data by.
     * @param object $query The Firestore query object.
     * @param string $model The name of the model class.
     * @param string $collection The name of the Firestore collection.
     *
     * @return Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\IToArrayHelper The get() method returns an instance of this trait.
     */
    public function fget($path, $direction, $query, $model, $collection)
    {

        if ($path) {
            if (!$direction) {
                $queryRaw = $query->orderBy($path)->documents()->rows();
            } else {
                $rows = $query->orderBy($path, $direction)->documents()->rows();
            }
        } else {
            $queryRaw = $query->documents()->rows();
        }
        return new ToArrayHelper(queryRaw: $queryRaw, model: $model, collection: $collection);
    }
}
