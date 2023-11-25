<?php
/**
 * This trait provides the functionality to retrieve the first result of a Firestore query and format it into a FirestoreDataFormat object.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\ItemNotFoundHelper;

trait FirstTrait
{
    /**
     * Retrieve the first result of a Firestore query and format it into a FirestoreDataFormat object.
     *
     * @param object $query The Firestore query object.
     * @param string $collection The name of the Firestore collection.
     * @param string $model The name of the Eloquent model.
     *
     * @return mixed Returns a FirestoreDataFormat object if a result is found, otherwise returns an empty array.
     */
    public function fFirst($path, $direction, $query, $collection, $model)
    {
        if($query){
            if($path){
                if(!$direction){
                    $newQuery = $query->orderBy($path)->limit(1);
                }else{
                    $newQuery = $query->orderBy($path, $direction)->limit(1);
                }
            }else{
                $newQuery = $query->limit(1);
            }
        }else{
            if($path){
                if(!$direction){
                    $newQuery = $this->fConnection($this->collection)->orderBy($path)->limit(1);
                }else{
                    $newQuery = $this->fConnection($this->collection)->orderBy($path, $direction)->limit(1);
                }
            }else{
                $newQuery = $this->fConnection($this->collection)->limit(1);
            }
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
