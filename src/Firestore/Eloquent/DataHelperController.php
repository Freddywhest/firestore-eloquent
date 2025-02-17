<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Str;


final class DataHelperController
{
    public function __construct(private $data, private $convertToFirestoreFormat, private $primaryKey, private $collection, private $patchRequest, private $deleteRequest, private $modelClass) {}

    public function __call($name, $arguments)
    {
        $sub = Str::camel('sub ' . $name);
        if (method_exists($this->modelClass, $sub)) {
            $subCollection = (new $this->modelClass)->$sub();
            return $this->subCollection($subCollection);
        }

        if (method_exists($this->modelClass, $name)) {
            $result =  (new $this->modelClass)->$name(...$arguments);
            if (is_array($result) && isset($result['relation'])) {
                return $this->handleRelation($result);
            } else {
                return $result;
            }
        }

        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }

        throw new \Exception("Method {$name} not found in " . $this->modelClass);
    }

    private function subCollection(array $details)
    {
        [
            'subCollectionId' => $subCollectionId,
            'primaryKey' => $primaryKey,
            'fillableFields' => $fillableFields,
            'requiredFields' => $requiredFields,
            'defaultFields' => $defaultFields,
            'fieldTypes' => $fieldTypes,
            'hiddenFields' => $hiddenFields,
            'keyToUse' => $keyToUse,
        ] = $details;

        if (!isset($this->data[$keyToUse ?? $this->primaryKey])) {
            throw new \Exception("Key to use [" . ($keyToUse ?? $this->primaryKey) . "] not found. Read the subCollection method documentation for more information.");
        }

        if (!is_string($subCollectionId) || empty($subCollectionId)) {
            throw new \Exception("Sub collection name/id must be a string. Read the subCollection method documentation for more information.");
        }

        if (!is_string($primaryKey) || empty($primaryKey)) {
            throw new \Exception("Primary key must be a string. Read the subCollection method documentation for more information.");
        }

        if (!is_array($fillableFields)) {
            throw new \Exception("Fillable fields must be an array. Read the subCollection method documentation for more information.");
        }

        if (!is_array($requiredFields)) {
            throw new \Exception("Required fields must be an array. Read the subCollection method documentation for more information.");
        }

        if (!is_array($defaultFields)) {
            throw new \Exception("Default fields must be an array. Read the subCollection method documentation for more information.");
        }

        if (!is_array($fieldTypes)) {
            throw new \Exception("Field types must be an array. Read the subCollection method documentation for more information.");
        }

        if (!is_array($hiddenFields)) {
            throw new \Exception("Hidden fields must be an array. Read the subCollection method documentation for more information.");
        }

        return new FClient(
            collection: $this->collection . '/' . $this->data[$keyToUse ?? $this->primaryKey] . '/' . $subCollectionId,
            primaryKey: $primaryKey,
            fillable: $fillableFields,
            required: $requiredFields,
            default: $defaultFields,
            fieldTypes: $fieldTypes,
            model: $this->modelClass,
            hidden: $hiddenFields,
            modelClass: $this->modelClass
        );
    }

    public function __get($key)
    {
        if (method_exists($this->modelClass, $key)) {
            $result = (new $this->modelClass)->$key();
            return $this->handleRelation($result, true);
        }
        return $this->data[$key] ?? null;
    }

    private function handleRelation($result, $isGet = false)
    {
        if (is_array($result) && isset($result['relation'])) {
            if ($result['relation'] === 'hasOne') {
                return $result['related']::where([$result['foreignKey'], $this->data[$result['localKey']]])->limit(1)->first();
            } else if ($result['relation'] === 'hasMany') {
                if ($isGet) {
                    return $result['related']::where([$result['foreignKey'], $this->data[$result['localKey']]])->get();
                } else {
                    return $result['related']::where([$result['foreignKey'], $this->data[$result['localKey']]]);
                }
            } else if ($result['relation'] === 'belongsTo') {
                return $result['related']::where([$result['foreignKey'], $this->data[$result['localKey']]])->limit(1)->first();
            }
        }
    }

    public function update($data)
    {
        try {
            if (isset($data[$this->primaryKey])) {
                unset($data[$this->primaryKey]);
            }

            $id = $this->data[$this->primaryKey];

            $dataToUpdate = array_merge($this->data, $data);

            $data = ($this->convertToFirestoreFormat)($dataToUpdate);

            if (!$id) {
                throw new \Exception("No data found to update");
            }

            ($this->patchRequest)($data, $id);
            return (object) ['status' => true, 'message' => 'Data updated successfully'];
        } catch (\Throwable $th) {
            throw new \Exception("Error updating data: " . $th->getMessage());
        }
    }

    public function delete()
    {
        try {
            if (!$this->data[$this->primaryKey]) {
                throw new \Exception("No data found to delete");
            }
            ($this->deleteRequest)($this->data[$this->primaryKey]);
            return (object) ['status' => true, 'message' => 'Data deleted successfully'];
        } catch (\Throwable $th) {
            throw new \Exception("Error deleting data: " . $th->getMessage());
        }
    }
    public function getSubDocument($key) {}
}
