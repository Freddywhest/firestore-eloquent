<?php
/**
 * This trait provides a method to delete multiple documents from Firestore.
 * It is used by the DeleteMany class.
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers
 */
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers;

trait DeleteManyTrait
{
    /**
     * Delete multiple documents from Firestore.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance.
     * @param \Google\Cloud\Firestore\FirestoreClient $firestore The Firestore client instance.
     * @return void
     */
    protected function fDeleteMany($query)
    {
        // Get the rows from the query
        $rows = $query->documents()->rows();
        // Loop through each query and delete the corresponding document
        foreach ($rows as $row) {
            $row->reference()->delete();
        }

        return true;
    }
}
