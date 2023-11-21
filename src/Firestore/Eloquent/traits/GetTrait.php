<?php
/**
 * This trait provides the functionality to retrieve Firestore data based on the given parameters.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\traits;
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
     * @param string $primaryKey The name of the primary key field.
     * @param array $fillable The fields that are fillable.
     * @param array $required The fields that are required.
     * @param array $fieldTypes The types of the fields.
     *
     * @return array An array of FirestoreDataFormat objects.
     */
    public function fget($path, $direction, $query, $model, $collection, $documentId = null, $collectionName = null)
    {
        $result =[];

        if($documentId !== null && $collectionName !== null){
            if($path){
                if(!$direction){
                    $datas = $query->orderBy($path, 'DESC')->documents()->rows();
                }else{
                    $datas = $query->orderBy($path, $direction)->documents()->rows();
                }
            }else{
                $datas = $query->documents()->rows();
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
                $datas = $query->orderBy($path)->documents()->rows();
            }else{
                $datas = $query->orderBy($path, $direction)->documents()->rows();
            }
        }else{
            $datas = $query->documents()->rows();
        }


        if(count($datas) > 0){
            foreach($datas as $data){
                $documentId = $data->id();
                $collection = $collection;

                array_push($result, new FirestoreDataFormat(
                    data: $data->data(),
                    documentId: $documentId,
                    exists: $data->exists(),
                    collectionName: $collection,
                    model: $model
                ));
            }
        }else{
            array_push($result, new FirestoreDataFormat(
                data: [],
                documentId: null,
                exists: false,
                collectionName: $collection,
                model: $model
            ));
        }

        return $result;
    }
}
