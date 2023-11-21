<?php
/**
 * This trait provides the functionality to update multiple documents in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\traits;

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
    protected function fUpdateMany(array $data, $query, $firestore, $primaryKey, $fillable, $required, $fieldTypes)
    {
        $filteredData = [];
        $collectionReference = $firestore;

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

        $queries = $query->documents()->rows();

        if(count($filteredData) > 0){
            foreach ($queries as $query) {
                $collectionReference->document($query->id())->update($filteredData);
            }
        }
    }
}
