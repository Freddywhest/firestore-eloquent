<?php
/**
 * This trait provides a method to delete multiple documents from Firestore.
 * It is used by the DeleteMany class.
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\traits
 */
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\traits;

trait DeleteManyTrait
{
    /**
     * Delete multiple documents from Firestore.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance.
     * @param \Google\Cloud\Firestore\FirestoreClient $firestore The Firestore client instance.
     * @return void
     */
    protected function fDeleteMany($query, $firestore)
    {
        // Get the rows from the query
        $queries = $query->documents()->rows();

        // Set the collection reference to Firestore
        $collectionReference = $firestore;

        // Loop through each query and delete the corresponding document
        foreach ($queries as $query) {
            $collectionReference->document($query->id())->delete();
        }
    }
}
