<?php

/**
 * This trait provides the functionality to find a document in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers
 */

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers;

use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\ToArrayHelper;

trait FindTrait
{
    /**
     * Find a document by its ID in Firestore.
     *
     * @param string $documentId The ID of the document to find.
     * @param mixed $collection The collection to search in.
     * @param mixed $firestore The Firestore instance to use.
     * @param mixed $model The model to use.
     * @param string $primaryKey The primary key of the model.
     * @param array $fillable The fillable attributes of the model.
     * @param array $required The required attributes of the model.
     * @param array $fieldTypes The field types of the model.
     * @return Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\IToArrayHelper The helper to use.
     */
    protected function fFind(string $documentId, $collection, $firestore, $model)
    {
        $snapshot = $firestore->document($documentId)->snapshot();

        return new ToArrayHelper(queryRaw: $snapshot, model: $model, collection: $collection, single: "find");
    }
}
