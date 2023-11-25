<?php
/**
 * Trait AllTrait
 *
 * This trait provides a method to retrieve all documents from a Firestore collection reference.
 * Optionally, the documents can be sorted based on a sorting path and direction.
 * For each document, a SubCollectionDataFormat object is created with the provided arguments.
 *
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers
 */
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;


trait AllTrait
{
    protected function fAll($path, $direction, $model, $collection, $query)
    {

        $result = [];

        if($path){
            if(!$direction){
                $rows = $query->orderBy($path, 'DESC')->documents()->rows();
            }else{
                $rows = $query->orderBy($path, $direction)->documents()->rows();
            }

        }else{
            $rows = $query->documents()->rows();
        }

        foreach($rows as $row){
            array_push(
                $result,
                new FirestoreDataFormat(
                    row: $row,
                    collectionName: $collection,
                    model: $model
                )
            );
        }

        return $result;
    }
}
