<?php
/**
 * This trait provides a method to update a Firestore document with the given data.
*/

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;

trait UpdateTrait
{
    /**
     * Check the type of a single value in an update operation.
     *
     * @param  mixed  $value  The value to check the type of.
     * @return string The type of the value.
     */
    private function checkTypeInUpdateSingle($value)
    {
        /* if(strtotime($value)){
            return 'date';
        } */

        return gettype($value);
    }

    /**
     * UpdateTrait.php
     * This trait provides a method to update a Firestore document with the given data.
     *
     * @param  array  $data  The data to update the document with.
     * @param  object  $firestore  The Firestore instance.
     * @param  string  $documentId  The ID of the document to update.
     * @param  string  $primaryKey  The primary key of the document.
     * @param  array  $fillable  The fields that are fillable.
     * @param  array  $required  The fields that are required.
     * @param  array  $fieldTypes  The expected types of the fields.
     * @return void
     *
     * @throws \Exception
     */
    protected function fUpdate(array $data, $row, $primaryKey, $fillable, $required, $fieldTypes)
    {
        $filteredData = [];

        if (isset($data[$primaryKey])) {
            unset($data[$primaryKey]);
        }

        foreach ($data as $key => $value) {
            if (! $key) {
                throw new \Exception('Invalid update field null => '.$value.'. Expected path => value but got null => value', 1);
            }

            if (count($fieldTypes) > 0) {
                if (isset($fieldTypes[$key])) {
                    if ($this->checkTypeInUpdateSingle($value) !== $fieldTypes[$key]) {
                        throw new \Exception('"'.$key.'" expect type '.$fieldTypes[$key].' but got '.$this->checkTypeInUpdateSingle($value).'.', 1);
                    }
                }
            }

            if (in_array($key, $fillable)) {
                if (in_array($key, $required) && ! $value) {
                    return throw new \Exception('"'.$key.'" is required.', 1);
                }

                array_push($filteredData, ['path' => $key, 'value' => $value]);
            }
        }

        if (count($filteredData) > 0) {
            try {
                $row->reference()->update($filteredData);

                return new FirestoreDataFormat(
                    row: $row->reference()->snapshot(),
                    collectionName: $this->collectionName,
                    model: $this->model
                );
            } catch (\Throwable $th) {
                throw new \Exception($th->getMessage(), 1);
            }
        }

        return null;
    }
}
