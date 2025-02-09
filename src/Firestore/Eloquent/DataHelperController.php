<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;


final class DataHelperController
{
    public function __construct(private $data, private $convertToFirestoreFormat, private $primaryKey, private $collection, private $patchRequest, private $deleteRequest, private $modelClass) {}

    public function __call($name, $arguments)
    {
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
