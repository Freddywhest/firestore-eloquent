<?php

/**
 * This trait provides the functionality to create a new document in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 *
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\traits
 */
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\traits;

use Illuminate\Support\Str;

trait CreateTrait
{
    /**
     * Returns the type of the given value.
     *
     * @param mixed $value The value to check the type of.
     * @return string The type of the given value.
     */
    private function checkTypeInCreate($value)
    {
        return gettype($value);
    }

    /**
     * Create a new document in Firestore collection.
     *
     * @param array $data The data to be stored in the document.
     * @param mixed $id The ID of the document to be created.
     * @param mixed $firestore The Firestore instance.
     * @param array $fillable The fillable attributes of the model.
     * @param array $required The required attributes of the model.
     * @param array $default The default attributes of the model.
     * @param string $model The name of the model.
     * @param string $primaryKey The primary key of the model.
     * @param array $fieldTypes The types of the attributes of the model.
     *
     * @return mixed The created document snapshot or an empty array if no data was provided.
     *
     * @throws \Exception If a required attribute is missing or if an attribute has an invalid type.
     */
    protected function fCreate(array $data, $id, $firestore, $fillable, $required, $default, $model, $primaryKey, $fieldTypes)
    {
        $filteredData = [];

        if(count($fillable) > 0) {
            if(isset($data[$primaryKey])){
                unset($data[$primaryKey]);
            }

            if(count($default) > 0){
                foreach ($default as $k => $v) {
                    if(!isset($data[$k]) && in_array($k, $required) && in_array($k, $fillable) && $v){
                        $data[$k] = $v;
                    }else if(isset($data[$k]) && in_array($k, $required) && in_array($k, $fillable) && !$v){
                        $data[$k] = $v;
                    }
                }
            }

            foreach ($data as $key => $value) {
                if(count($fieldTypes) > 0){
                    if(isset($fieldTypes[$key])){
                        if($this->checkTypeInCreate($value) !== $fieldTypes[$key]){
                            throw new \Exception('"'.$key.'" expect type '.$fieldTypes[$key].' but got '.$this->checkTypeInCreate($value).'.', 1);
                        }
                    }
                }
                if(in_array($key, $fillable)){
                    if(in_array($key, $required) && !$value){
                        return throw new \Exception('"'.$key.'" is required.', 1);
                    }

                   $filteredData = array_merge($filteredData, [$key => $value]);
                }
            }

            if (count($required) > 0) {
                foreach ($required as $value) {
                    if(!isset($filteredData[$value])){
                        return throw new \Exception('"'.$value.'" is required.', 1);
                    }
                }
            }

            $collectionReference = $firestore;

            do {
                $id = Str::random(20);
            } while ($collectionReference->document($id)->snapshot()->exists());

            if(count($filteredData) > 0){
                $collectionReference->document($id)->set([...$filteredData, $primaryKey => $id]);

                $data = $collectionReference->document($id);

                return new class($data)
                {
                    private $data;
                    private $dataArr = [];

                    public function __construct($data)
                    {
                        $this->dataArr = $data->snapshot()->data();
                        $this->data = $data;
                    }

                    public function __get($name)
                    {
                        if(isset($this->dataArr[$name])){
                            return $this->dataArr[$name];
                        }else{
                            return null;
                        }
                    }

                    public function delete()
                    {
                        $this->data->delete();
                    }

                    public function exists()
                    {
                        return $this->data->snapshot()->exists();
                    }
                };
            }

            return new class($data)
            {
                public function __get($name)
                {
                    return null;
                }

                public function exists()
                {
                    return false;
                }
            };
        }else{
            return throw new \Exception('Cannot create a new "'.$model.'" because fillable property in "'.$model.'" model is empty or undefined.', 1);

        }
    }
}
