<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FsFilters;

trait CreateMethod
{
    public function create(array $data)
    {
        try {

            $filteredData = [];

            if (count($this->fillable) > 0) {
                if (isset($data[$this->primaryKey])) {
                    unset($data[$this->primaryKey]);
                }

                if (count($this->default) > 0) {
                    foreach ($this->default as $k => $v) {
                        if (!isset($data[$k]) && in_array($k, $this->required) && in_array($k, $this->fillable) && $v) {
                            $data[$k] = $v;
                        } else if (isset($data[$k]) && in_array($k, $this->required) && in_array($k, $this->fillable) && !$v) {
                            $data[$k] = $v;
                        }
                    }
                }

                foreach ($data as $key => $value) {
                    if (count($this->fieldTypes) > 0) {
                        if (isset($this->fieldTypes[$key])) {
                            if ($this->checkTypeInCreate($value) !== $this->fieldTypes[$key]) {
                                throw new \Exception('"' . $key . '" expect type ' . $this->fieldTypes[$key] . ' but got ' . $this->checkTypeInCreate($value) . '.', 1);
                            }
                        }
                    }
                    if (in_array($key, $this->fillable)) {
                        if (in_array($key, $this->required) && !$value) {
                            return throw new \Exception('"' . $key . '" is required.', 1);
                        }

                        $filteredData = array_merge($filteredData, [$key => $value]);
                    }
                }

                if (count($this->required) > 0) {
                    foreach ($this->required as $value) {
                        if (!isset($filteredData[$value])) {
                            return throw new \Exception('"' . $value . '" is required.', 1);
                        }
                    }
                };

                if (count($filteredData) > 0) {
                    $id = str_replace('-', '', Str::uuid()) . str_replace('.', '', microtime(true));
                    $filteredData[$this->primaryKey] = $id;
                    $final = $this->convertToFirestoreFormat($filteredData);
                    $this->postRequest($this->collection, $final, true, false, false, '?documentId=' . $id);
                    return $filteredData;
                }
            } else {
                return throw new \Exception('Cannot create a new "' . $this->model . '" because fillable property in "' . $this->model . '" model is empty or undefined.', 1);
            }
        } catch (\Throwable $th) {
            throw new \Exception("Error creating document: " . $th->getMessage());
        }
    }

    private function convertToFirestoreFormat($data)
    {
        $convertedFields = [];

        foreach ($data as $key => $value) {
            $convertedFields[$key] = $this->convertValue($value);
        }

        return ["fields" => $convertedFields];
    }

    private function convertValue($value)
    {
        if (is_null($value)) {
            return ["nullValue" => null];
        } elseif (is_bool($value)) {
            return ["booleanValue" => $value];
        } elseif (is_int($value)) {
            return ["integerValue" => $value];
        } elseif (is_float($value)) {
            return ["doubleValue" => $value];
        } elseif (is_string($value)) {
            // Detect Firestore timestamp format

            if (FsFilters::isValidDateWithYearMonthDay($value)) {
                return ["timestampValue" => Carbon::parse($value)->format('Y-m-d\TH:i:s.u\Z')];
            }
            return ["stringValue" => $value];
        } elseif (is_array($value)) {
            // Check if it's an indexed array or associative
            if (array_keys($value) === range(0, count($value) - 1)) {
                return [
                    "arrayValue" => [
                        "values" => array_map([$this, "convertValue"], $value)
                    ]
                ];
            } else {
                // Associative array -> mapValue
                $mapFields = [];
                foreach ($value as $k => $v) {
                    $mapFields[$k] = $this->convertValue($v);
                }
                return [
                    "mapValue" => [
                        "fields" => $mapFields
                    ]
                ];
            }
        }
        return null; // Fallback case
    }
}
