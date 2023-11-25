<?php
/**
 * This trait provides the functionality to find a document in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\traits
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;

trait FirstOrFailTrait
{

    public function fFirstOrFail($query, $collection, $model, $path, $direction)
    {
        if($path){
            if(!$direction){
                $newQuery = $query->orderBy($path)->limit(1);
            }else{
                $newQuery = $query->orderBy($path, $direction)->limit(1);
            }
        }else{
            $newQuery = $query->limit(1);
        }

        if ($newQuery->count() > 0) {
            $row = $newQuery->documents()->rows()[0];

            return new FirestoreDataFormat(
                row: $row,
                collectionName: $collection,
                model: $model
            );

        }

        return abort(404);
    }
}
