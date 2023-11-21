<?php
/**
 * Trait AllTrait
 *
 * This trait provides a method to retrieve all documents from a Firestore collection reference.
 * Optionally, the documents can be sorted based on a sorting path and direction.
 * For each document, a FirestoreDataFormat object is created with the provided arguments.
 *
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\traits
 */
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\traits;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;

trait AllTrait
{
    /**
     * Get all documents from Firestore for a specific collection reference in ascending or descending order
     * if order param present.
     *
     * @param string $path Sorting path for documents
     * @param string $direction Sorting direction
     * @param string $model Model class name for Firestore data.
     * @param string $collection Collection or table for Firestore
     * @param Google\Cloud\Firestore\FirestoreClient $firestore Collection reference
     * @param string|null $primaryKey Primary key of the Firestore/Db table
     * @param array $fillable fillable attributes for the given model
     * @param array $required required attributes for the given model
     * @param array $fieldTypes The attribute has a specific data type
     *        [
     *            'attribute_name' => 'data_type',
     *            ...
     *        ]
     * @return array FirestoreDataFormat[] Array where each item is a FirestoreDataFormat object
     *                                    representing a document
     */
    protected function fAll($path, $direction, $model, $collection, $firestore, $documentId = null, $collectionName = null)
    {
        /*
         * Implemention details:
         * - obtains all documents from a collection referred to by $firestore
         * - optionally sorts them based on $path and $direction
         * - for each document, creates a FirestoreDataFormat object with provided arguments.
         * - Returns an array of them
         */

        $result = [];
        $collectionReference = $firestore;
        if($documentId !== null && $collectionName !== null){
            if($path){
                if(!$direction){
                    $datas = $collectionReference->document($documentId)->collection($collectionName)->orderBy($path, 'DESC')->documents()->rows();
                }else{
                    $datas = $collectionReference->document($documentId)->collection($collectionName)->orderBy($path, $direction)->documents()->rows();
                }
            }else{
                $datas = $collectionReference->document($documentId)->collection($collectionName)->documents()->rows();
            }

            foreach($datas as $data){
                $documentId = $data->id();
                $collection = $collection;
                array_push(
                    $result,
                    new FirestoreDataFormat(
                        data: $data->data(),
                        documentId: $documentId,
                        exists: $data->exists(),
                        collectionName: $collection,
                        model: $model
                    )
                );
            }

            return $result;
        }

        if($path){
            if(!$direction){
                $datas = $collectionReference->orderBy($path, 'DESC')->documents()->rows();
            }else{
                $datas = $collectionReference->orderBy($path, $direction)->documents()->rows();
            }

        }else{
            $datas = $collectionReference->documents()->rows();
        }

        foreach($datas as $data){
            $documentId = $data->id();
            $collection = $collection;
            array_push(
                $result,
                new FirestoreDataFormat(
                    data: $data->data(),
                    documentId: $documentId,
                    exists: $data->exists(),
                    collectionName: $collection,
                    model: $model
                )
            );
        }

        return $result;
    }
}
