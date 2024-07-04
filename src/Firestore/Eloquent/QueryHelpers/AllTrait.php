<?php

/**
 * Trait AllTrait
 *
 * This trait provides a method to retrieve all documents from a Firestore collection reference.
 * Optionally, the documents can be sorted based on a sorting path and direction.
 * For each document, a FirestoreDataFormat object is created with the provided arguments.
 *
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers
 */

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers;

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
    protected function fAll($path, $direction, $model, $collection, $firestore)
    {
        /**
         * Implemention details:
         * - obtains all documents from a collection referred to by $firestore
         * - optionally sorts them based on $path and $direction
         * - for each document, creates a FirestoreDataFormat object with provided arguments.
         * - Returns an array of them
         */

        if ($path) {
            if (!$direction) {
                $queryRaw = $firestore->orderBy($path, 'DESC');
            } else {
                $queryRaw = $firestore->orderBy($path, $direction);
            }
        } else {
            $queryRaw = $firestore;
        }

        return new ToArrayHelper(queryRaw: $queryRaw, model: $model, collection: $collection);
    }
}
