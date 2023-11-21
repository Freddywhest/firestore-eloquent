<?php
/**
 * This trait provides the functionality to find a document in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\traits
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\traits;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;

trait FirstOrFailTrait
{
    use FirestoreConnectionTrait;
    /**
     * Find a document by its ID in Firestore.
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance.
     * @param mixed $collection The collection to search in.
     * @param mixed $firestore The Firestore instance to use.
     * @param mixed $model The model to use.
     * @param string $primaryKey The primary key of the model.
     * @param array $fillable The fillable attributes of the model.
     * @param array $required The required attributes of the model.
     * @param array $fieldTypes The field types of the model.
     * @return mixed The found document or an empty array if not found.
     *
     * @throws \Exception If a required attribute is missing or if an attribute has an invalid type.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the document is not found.
    */
    public function fFirstOrFail($query, $collection, $model, $subCollection = null, $documentIdForMainCollection = null)
    {
        $result = [];

        if(gettype($query) == 'object' && $subCollection == null && $documentIdForMainCollection == null){
            $data = $query->documents()->rows();
            if(isset($data[0])){
                $documentId = $data[0]->id();
                $collection = $collection;
                array_push($result, new FirestoreDataFormat(
                    data: $data[0]->data(),
                    documentId: $documentId,
                    exists: $data[0]->exists(),
                    collectionName: $collection,
                    model: $model,
                ));
                return isset($result[0]) ? $result[0] : [];
            }
            return abort(404);

        }else if($subCollection != null && $documentIdForMainCollection != null){
            $data = $this->fConnection($collection)->document($documentIdForMainCollection)->collection($subCollection)->documents()->rows();
            if(isset($data[0])){
                $documentId = $data[0]->id();
                $collection = $collection;
                array_push($result, new FirestoreDataFormat(
                    data: $data[0]->data(),
                    documentId: $documentId,
                    exists: $data[0]->exists(),
                    collectionName: $collection,
                    model: $model,
                ));

                return isset($result[0]) ? $result[0] : [];

            }

            return abort(404);
        }

        return abort(404);
    }
}
