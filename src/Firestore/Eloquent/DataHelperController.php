<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Illuminate\Contracts\Support\Arrayable;


final class DataHelperController implements Arrayable, Authenticatable
{
    private array $loadedRelations = [];

    public function __construct(
        private array $data,
        private $convertToFirestoreFormat,
        private string $primaryKey,
        private string $collection,
        private $patchRequest,
        private $deleteRequest,
        private string $modelClass,
        private array $fillable = [],
        private array $fieldTypes = [],
        private array $hidden = [],
        private ?string $documentName = null,
        private bool $unrestrictedWrites = false
    ) {
    }

    public function toArray()
    {
        $data = array_diff_key($this->data, array_flip($this->hidden));

        foreach ($this->loadedRelations as $name => $relation) {
            $data[$name] = $relation instanceof Arrayable
                ? $relation->toArray()
                : $relation;
        }

        return $data;
    }

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
        if (array_key_exists($key, $this->loadedRelations)) {
            return $this->loadedRelations[$key];
        }

        if (method_exists($this->modelClass, $key)) {
            $result = (new $this->modelClass)->$key();
            $this->loadedRelations[$key] = $this->handleRelation($result, true);
            return $this->loadedRelations[$key];
        }
        return $this->data[$key] ?? null;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function getAuthIdentifierName()
    {
        return $this->primaryKey;
    }

    public function getAuthIdentifier()
    {
        return $this->data[$this->getAuthIdentifierName()] ?? null;
    }

    /** Laravel 11+ authentication contract method. */
    public function getAuthPasswordName()
    {
        return (string) config('firebase.auth.password_field', 'password');
    }

    public function getAuthPassword()
    {
        return (string) ($this->data[$this->getAuthPasswordName()] ?? '');
    }

    public function getRememberToken()
    {
        $name = $this->getRememberTokenName();

        return $name === '' ? null : ($this->data[$name] ?? null);
    }

    public function setRememberToken($value)
    {
        $name = $this->getRememberTokenName();
        if ($name !== '') {
            $this->data[$name] = $value;
        }
    }

    public function getRememberTokenName()
    {
        return (string) config('firebase.auth.remember_token_field', 'remember_token');
    }

    /** Persist the guard's remember token without requiring it to be mass fillable. */
    public function persistRememberToken(string $token): void
    {
        $field = $this->getRememberTokenName();
        $id = $this->getAuthIdentifier();
        if ($field === '' || $id === null) {
            return;
        }

        ($this->patchRequest)(($this->convertToFirestoreFormat)([$field => $token]), $id, [$field]);
        $this->data[$field] = $token;
    }

    public function getDocumentName(): ?string
    {
        return $this->documentName;
    }

    public function setRelation(string $name, mixed $value): void
    {
        $this->loadedRelations[$name] = $value;
    }

    public function makeHidden(string|array $fields): static
    {
        $fields = is_array($fields) ? $fields : [$fields];
        $this->hidden = array_values(array_unique(array_merge($this->hidden, $fields)));
        return $this;
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

    public function update(array $data)
    {
        try {
            unset($data[$this->primaryKey]);

            $id = $this->data[$this->primaryKey] ?? null;

            if (!$id) {
                throw new \Exception("No data found to update");
            }

            if (!$this->unrestrictedWrites) {
                $unknownFields = array_diff(array_keys($data), $this->fillable);
                if ($unknownFields !== []) {
                    throw new \InvalidArgumentException('Fields are not fillable: ' . implode(', ', $unknownFields));
                }
            }

            if ($data === []) {
                throw new \InvalidArgumentException('No fields were provided for update.');
            }

            $payload = ($this->convertToFirestoreFormat)($data);
            ($this->patchRequest)($payload, $id, array_keys($data));
            $this->data = array_replace($this->data, $data);
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
    public function getSubDocument(string $key, mixed $default = null): mixed
    {
        $value = $this->data;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
