<?php
/**
 * This trait provides the functionality to retrieve Firestore data based on the given parameters.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;

trait GetTrait
{
    public function fget($path, $direction, $query, $model, $collection)
    {
        $result =[];

        if($path){
            if(!$direction){
                $rows = $query->orderBy($path)->documents()->rows();
            }else{
                $rows = $query->orderBy($path, $direction)->documents()->rows();
            }
        }else{
            $rows = $query->documents()->rows();
        }

        if($query->count() > 0){
            foreach($rows as $row){

                array_push($result, new FirestoreDataFormat(
                    row: $row,
                    collectionName: $collection,
                    model: $model
                ));
            }

            return $result;
        }

        return [];

    }
}
