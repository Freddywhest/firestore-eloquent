<?php
/**
 * This trait provides the functionality to find a document in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\traits
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\traits;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;

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
     * @return mixed The found document or an empty array if not found.
     */
    protected function fFind(string $documentId, $collection, $firestore, $model)
    {
        $result = [];
        $collectionReference = $firestore;
        $data = $collectionReference->document($documentId)->snapshot();
        $collection = $collection;

        if($data->data() && count($data->data()) > 0){
            $documentId = $data->id();
            array_push($result, new FirestoreDataFormat(
                data: $data->data(),
                documentId: $documentId,
                exists: $data->exists(),
                collectionName: $collection,
                model: $model,
            ));

        }else{
            array_push($result, new FirestoreDataFormat(
                data: $data->data(),
                documentId: $documentId,
                exists: $data->exists(),
                collectionName: $collection,
                model: $model
            ));

        }

        return isset($result[0]) ? $result[0] : [];
    }
}
