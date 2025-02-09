<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Roddy\FirestoreEloquent\Firestore\Eloquent\DataHelperController;

trait DataParser
{
    private function parseFirestoreJson($json)
    {
        $extractValue = function ($value) use (&$extractValue, &$parseFields) {
            if (isset($value['stringValue'])) return $value['stringValue'];
            if (isset($value['booleanValue'])) return $value['booleanValue'];
            if (isset($value['integerValue'])) return (int) $value['integerValue'];
            if (isset($value['doubleValue'])) return (float) $value['doubleValue'];
            if (isset($value['timestampValue'])) return $value['timestampValue'];
            if (isset($value['mapValue']['fields'])) return $parseFields($value['mapValue']['fields'], $extractValue);
            if (isset($value['arrayValue']['values'])) {
                return array_map(function ($item) use ($extractValue) {
                    return $extractValue($item);
                }, $value['arrayValue']['values']);
            }
            return null;
        };

        $parseFields = function ($fields, $extractValue) {
            $parsed = [];
            foreach ($fields as $key => $value) {
                $parsed[$key] = $extractValue($value);
            }
            return $parsed;
        };

        $data = $json;
        $result = [];

        $convertFunction  = function ($data) {
            return $this->convertToFirestoreFormat($data);
        };
        $patchRequestFuntion = function ($data, $id) {
            return $this->patchRequest($this->collection, $data, true, false, $id);
        };

        $deleteRequestFuntion = function ($id) {
            return $this->deleteRequest($this->collection, true, false, $id);
        };
        if (isset($data['documents'])) {
            foreach ($data['documents'] as $doc) {
                $data = $parseFields($doc['fields'], $extractValue);
                if ($this->hidden) {
                    $data = array_diff_key($data, array_flip($this->hidden));
                }
                $result[] = new DataHelperController($data, $convertFunction, $this->primaryKey, $this->collection, $patchRequestFuntion, $deleteRequestFuntion, $this->modelClass);
            }
        } elseif (isset($data[0]['document'])) {
            foreach ($data as $doc) {
                $data = $parseFields($doc['document']['fields'], $extractValue);
                if ($this->hidden) {
                    $data = array_diff_key($data, array_flip($this->hidden));
                }
                $result[] = new DataHelperController($data, $convertFunction, $this->primaryKey, $this->collection, $patchRequestFuntion, $deleteRequestFuntion, $this->modelClass);
            }
        }

        return $result;
    }
}
