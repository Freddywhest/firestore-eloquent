<?php

/**
 * Trait AllTrait
 *
 * This trait provides a method to retrieve all documents from a Firestore collection reference.
 * Optionally, the documents can be sorted based on a sorting path and direction.
 * For each document, a SubCollectionDataFormat object is created with the provided arguments.
 *
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers
 */

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\ToArrayHelper;

trait AllTrait
{
    /**
     * Get all documents from Firestore for a specific collection reference in ascending or descending order
     * if order param present.
     *
     * @param string $path Sorting path for documents
     * @param string $direction Sorting direction
     * @param string $model Model class name for Firestore data.
     * @param string $collection Collection or table for Firestore data.
     * @return Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\IToArrayHelper
     */
    protected function fAll($path, $direction, $model, $collection, $query)
    {

        if ($path) {
            if (!$direction) {
                $queryRaw = $query->orderBy($path, 'DESC')->documents()->rows();
            } else {
                $queryRaw = $query->orderBy($path, $direction)->documents()->rows();
            }
        } else {
            $queryRaw = $query->documents()->rows();
        }

        return new ToArrayHelper(queryRaw: $queryRaw, model: $model, collection: $collection);
    }
}
