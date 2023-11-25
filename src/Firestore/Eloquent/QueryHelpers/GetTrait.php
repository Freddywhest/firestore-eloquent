<?php
/**
 * This trait provides the functionality to retrieve Firestore data based on the given parameters.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;

trait GetTrait
{
    /**
     * Retrieves Firestore data based on the given parameters.
     *
     * @param string $path The path to order the data by.
     * @param string $direction The direction to order the data by.
     * @param object $query The Firestore query object.
     * @param string $model The name of the model class.
     * @param string $collection The name of the Firestore collection.
     *
     * @return array An array of FirestoreDataFormat objects or an empty array if no data is found.
     */
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
