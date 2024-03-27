<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;

class ToArrayHelper
{
    private $queryRaw;
    private $model;
    private string $single;
    private $collection;

    public function __construct($queryRaw, $model, $collection, $single = "default")
    {
        $this->queryRaw = $queryRaw;
        $this->model = $model;
        $this->collection = $collection;
        $this->single = $single;
    }

    public function __call($name, $arguments)
    {
        if ($name == 'toArray') {
            return $this->toArray();
        }
        try {
            //code...
            return $this->{$name};
        } catch (\Throwable $th) {
            throw new \Exception("Method \"{$name}\" does not exist on method \"get()\".", 1);
        }
    }
    public function data()
    {
        $result = [];
        $queryData = $this->queryData();
        if ($this->single == "find") {
            if ($queryData->exists()) {
                return new FirestoreDataFormat(
                    row: $queryData,
                    collectionName: $this->collection,
                    model: $this->model
                );
            }

            return new ItemNotFoundHelper();
        }

        if ($queryData->count() > 0) {
            if ($this->single == "first" || $this->single == "firstOrFail") {
                $row = $queryData->documents()->rows()[0];
                return new FirestoreDataFormat(
                    row: $row,
                    collectionName: $this->collection,
                    model: $this->model
                );
            }

            $rows = $queryData->documents()->rows();
            foreach ($rows as $row) {
                array_push($result, (new FirestoreDataFormat(
                    row: $row,
                    collectionName: $this->collection,
                    model: $this->model
                )));
            }

            return $result;
        }

        if ($this->single == "first" || $this->single == "find") {
            return new ItemNotFoundHelper();
        } else if ($this->single == "firstOrFail") {
            return abort(404);
        } else {
            return [];
        }
    }

    private function queryData()
    {
        return $this->queryRaw;
    }

    private function toArray()
    {
        $queryData = $this->queryData();

        if ($this->single == "find") {
            if ($queryData->exists()) {
                return collect($queryData->data());
            }

            return [];
        }

        if ($queryData->count() > 0) {
            $rows = $queryData->documents()->rows();
            if ($this->single == "first" || $this->single == "firstOrFail") {
                return collect($rows)->map(function ($item) {
                    return $item->data();
                })->first();
            }

            return collect($rows)->map(function ($item) {
                return $item->data();
            })->all();
        }

        return [];
    }

    public function __get($name)
    {
        try {
            $this->{$name};
        } catch (\Throwable $th) {
            throw new \Exception("Property \"{$name}\" does not exist on method \"get()\".", 1);
        }
    }
}

/**
 * This interface is for  used for PHP intelligence only for auto suggestions
 */
interface IToArrayHelper
{
    public function toArray();

    public function data();
}
