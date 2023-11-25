<?php
/**
 * This trait provides the functionality to update multiple documents in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;

trait UpdateManyTrait
{
    /**
     * Check the type of a given value.
     *
     * @param mixed $value The value to check the type of.
     * @return string The type of the given value.
     */
    private function checkType($value)
    {
        /* if(strtotime($value)){
            return 'date';
        } */

        return gettype($value);
    }

    /**
     * Update multiple documents in Firestore collection.
     *
     * @param array $data The data to update.
     * @param mixed $query The query to filter documents to update.
     * @param mixed $firestore The Firestore instance.
     * @param string $primaryKey The primary key of the collection.
     * @param array $fillable The fields that can be updated.
     * @param array $required The fields that are required.
     * @param array $fieldTypes The expected types of the fields.
     * @throws \Exception If an error occurs during the update process.
     * @return void
     */
    protected function fUpdateMany(array $data, $query, $primaryKey, $fillable, $required, $fieldTypes, $model, $collection)
    {
        $filteredData = [];

        $result = [];

        if(isset($data[$primaryKey])){
            unset($data[$primaryKey]);
        }

        foreach ($data as $key => $value) {
            if(!$key){
                throw new \Exception("Invalid update field null => ".$value .". Expected path => value but got null => value", 1);
            }

            if(count($fieldTypes) > 0){
                if(isset($fieldTypes[$key])){
                    if($this->checkType($value) !== $fieldTypes[$key]){
                        throw new \Exception('"'.$key.'" expect type '.$fieldTypes[$key].' but got '.$this->checkType($value).'.', 1);
                    }
                }
            }

            if(in_array($key, $fillable)){
                if(in_array($key, $required) && !$value){
                    return throw new \Exception('"'.$key.'" is required.', 1);
                }

                array_push($filteredData, ['path' => $key, 'value' => $value]);
            }
        }


        if(count($filteredData) > 0){
            $rows = $query->documents()->rows();
            foreach ($rows as $row) {
                try {
                    $row->reference()->update($filteredData);

                    array_push($result, new FirestoreDataFormat(
                        row: $row->reference()->snapshot(),
                        collectionName: $collection,
                        model: $model
                    ));

                } catch (\Throwable $th) {
                    throw new \Exception($th->getMessage(), 1);
                }
            }

            return $result;
        }

        return null;
    }
}
