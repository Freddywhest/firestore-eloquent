<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Illuminate\Support\Str;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreValue;

trait CreateMethod
{
    public function create(array $data)
    {
        try {
            $filteredData = [];

            if ($this->unrestrictedWrites || count($this->fillable) > 0) {
                if (
                    !$this->unrestrictedWrites &&
                    !in_array($this->primaryKey, $this->fillable) &&
                    isset($data[$this->primaryKey])
                ) {
                    unset($data[$this->primaryKey]);
                }

                foreach ($this->default as $key => $value) {
                    if (!array_key_exists($key, $data) && in_array($key, $this->fillable, true)) {
                        $data[$key] = value($value);
                    }
                }

                foreach ($data as $key => $value) {
                    if (isset($this->fieldTypes[$key])) {
                        $this->assertFieldType($key, $value, $this->fieldTypes[$key]);
                    }
                    if ($this->unrestrictedWrites || in_array($key, $this->fillable, true)) {
                        if (in_array($key, $this->required, true) && $this->isMissingRequiredValue($value)) {
                            return throw new \Exception('"' . $key . '" is required.', 1);
                        }

                        $filteredData = array_merge($filteredData, [$key => $value]);
                    }
                }

                if (count($this->required) > 0) {
                    foreach ($this->required as $value) {
                        if (!array_key_exists($value, $filteredData) || $this->isMissingRequiredValue($filteredData[$value])) {
                            return throw new \Exception('"' . $value . '" is required.', 1);
                        }
                    }
                };

                if (count($filteredData) > 0) {
                    $id = $filteredData[$this->primaryKey] ?? null;

                    if (!$id) {
                        $id  = str_replace('-', '', Str::uuid()) . str_replace('.', '', microtime(true));
                        $filteredData[$this->primaryKey] = $id;
                    }

                    $final = $this->convertToFirestoreFormat($filteredData);
                    if ($this->transactionContext !== null) {
                        $this->transactionContext->queueWrite([
                            'update' => array_merge(
                                ['name' => $this->firestoreDocumentName($id)],
                                $final
                            ),
                            'currentDocument' => ['exists' => false],
                        ]);
                    } else {
                        $this->postRequest($this->collection, $final, true, false, false, '?documentId=' . rawurlencode((string) $id));
                    }
                    return $filteredData;
                }

                throw new \InvalidArgumentException('No fillable fields were provided.');
            } else {
                return throw new \Exception('Cannot create a new "' . $this->model . '" because fillable property in "' . $this->model . '" model is empty or undefined.', 1);
            }
        } catch (\Throwable $th) {
            throw new \Exception("Error creating document: " . $th->getMessage());
        }
    }

    /** Laravel query-builder style insert. */
    public function insert(array $values): bool
    {
        if ($values === []) {
            return false;
        }

        $documents = array_is_list($values) ? $values : [$values];
        foreach ($documents as $index => $document) {
            if (!is_array($document)) {
                throw new \InvalidArgumentException("Insert item [{$index}] must be an array.");
            }
            $this->create($document);
        }

        return true;
    }

    /** Insert one document and return its primary-key value. */
    public function insertGetId(array $values, ?string $sequence = null): string|int
    {
        $created = $this->create($values);
        $key = $sequence ?? $this->primaryKey;

        if (!array_key_exists($key, $created) || (!is_string($created[$key]) && !is_int($created[$key]))) {
            throw new \RuntimeException("The inserted document did not contain primary key [{$key}].");
        }

        return $created[$key];
    }

    private function convertToFirestoreFormat($data)
    {
        $convertedFields = [];

        foreach ($data as $key => $value) {
            if (isset($this->fieldTypes[$key])) {
                $this->assertFieldType($key, $value, $this->fieldTypes[$key]);
            }
            $convertedFields[$key] = FirestoreValue::encode($value, $this->fieldTypes[$key] ?? null);
        }

        return ["fields" => $convertedFields];
    }

    private function isMissingRequiredValue(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function assertFieldType(string $field, mixed $value, string $type): void
    {
        $valid = match (strtolower($type)) {
            'int', 'integer' => is_int($value),
            'float', 'double' => is_float($value),
            'numeric', 'number' => is_int($value) || is_float($value),
            'string' => is_string($value),
            'bool', 'boolean' => is_bool($value),
            'array', 'list' => is_array($value) && array_is_list($value),
            'object', 'map' => is_object($value) || (is_array($value) && !array_is_list($value)),
            'date', 'datetime', 'timestamp' => $value instanceof \DateTimeInterface || (is_string($value) && strtotime($value) !== false),
            'null' => $value === null,
            default => throw new \InvalidArgumentException("Unsupported field type [{$type}] for [{$field}]."),
        };

        if (!$valid) {
            throw new \InvalidArgumentException(
                '"' . $field . '" expects type ' . $type . ' but got ' . get_debug_type($value) . '.'
            );
        }
    }
}
