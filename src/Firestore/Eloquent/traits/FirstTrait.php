<?php
/**
 * This trait provides the functionality to retrieve the first result of a Firestore query and format it into a FirestoreDataFormat object.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\traits;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;

trait FirstTrait
{
    /**
     * Retrieve the first result of a Firestore query and format it into a FirestoreDataFormat object.
     *
     * @param object $query The Firestore query object.
     * @param string $collection The name of the Firestore collection.
     * @param string $model The name of the Eloquent model.
     * @param string $primaryKey The name of the primary key field.
     * @param array $fillable The list of fillable fields.
     * @param array $required The list of required fields.
     * @param array $fieldTypes The list of field types.
     *
     * @return mixed Returns a FirestoreDataFormat object if a result is found, otherwise returns an empty array.
     */
    public function fFirst($query, $collection, $model)
    {
        $result = [];

        if (isset($query->documents()->rows()[0])) {
            $data = $query->documents()->rows()[0];
            $documentId = $data->id();
            $collection = $collection;

            array_push($result, new FirestoreDataFormat(
                data: $data->data(),
                documentId: $documentId,
                exists: $data->exists(),
                collectionName: $collection,
                model: $model
            ));

        } else {
            return new FirestoreDataFormat(
                data: [],
                documentId: '',
                exists: false,
                collectionName: $collection,
                model: $model
            );
        }

        return isset($result[0]) ? $result[0] : [];
    }
}
