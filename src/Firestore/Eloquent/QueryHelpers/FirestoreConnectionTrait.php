<?php
/**
 * This trait provides the functionality to create a new document in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers
 */

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers;
use Google\Cloud\Firestore\FirestoreClient;

trait FirestoreConnectionTrait
{
    /**
     * Returns a Firestore collection instance for the given collection name.
     *
     * @param string $collection The name of the Firestore collection.
     * @return Google\Cloud\Firestore\CollectionReference The Firestore collection instance.
     */
    protected function fConnection($collection)
    {
        $firestore = new FirestoreClient([
            'projectId' => config('firebase.projects.app.project_id', env('FIREBASE_PROJECT_ID')),
            'keyFilePath' => base_path().'/'.env('GOOGLE_APPLICATION_CREDENTIALS'),
        ]);

        return $firestore->collection($collection);
    }
}
