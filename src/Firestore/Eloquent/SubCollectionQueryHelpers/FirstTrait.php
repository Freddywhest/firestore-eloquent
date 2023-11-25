<?php
/**
 * This trait provides the functionality to retrieve the first result of a Firestore query and format it into a SubCollectionDataFormat object.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\ItemNotFoundHelper;

trait FirstTrait
{
    public function fFirst($path, $direction, $query, $collection, $model)
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

        return new ItemNotFoundHelper();
    }
}
