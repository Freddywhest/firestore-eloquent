<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

final class SubCollectionMainController
{
    private string $subCollectionId;
    private string $primaryKey;
    private array $fillableFields = [];
    private array $requiredFields = [];
    private array $defaultFields = [];
    private array $fieldTypes = [];
    private array $hiddenFields = [];
    private ?string $keyToUse = null;

    public function subCollection(string $value)
    {
        $this->subCollectionId = $value;
        return $this;
    }

    public function primaryKey(string $value)
    {
        $this->primaryKey = $value;
        return $this;
    }

    public function fillableFields(array $value)
    {
        $this->fillableFields = $value;
        return $this;
    }

    public function requiredFields(array $value)
    {
        $this->requiredFields = $value;
        return $this;
    }

    public function defaultFields(array $value)
    {
        $this->defaultFields = $value;
        return $this;
    }

    public function fieldTypes(array $value)
    {
        $this->fieldTypes = $value;
        return $this;
    }

    public function hiddenFields(array $value)
    {
        $this->hiddenFields = $value;
        return $this;
    }

    public function keyToUse(string $value)
    {
        $this->keyToUse = $value;
        return $this;
    }

    public function load()
    {
        if (empty($this->subCollectionId)) {
            throw new \Exception("Sub collection name/id is required");
        }

        if (empty($this->primaryKey)) {
            throw new \Exception("Primary key is required");
        }

        return [
            "subCollectionId" => $this->subCollectionId,
            "primaryKey" => $this->primaryKey,
            "fillableFields" => $this->fillableFields,
            "requiredFields" => $this->requiredFields,
            "defaultFields" => $this->defaultFields,
            "fieldTypes" => $this->fieldTypes,
            "hiddenFields" => $this->hiddenFields,
            "keyToUse" => $this->keyToUse,
        ];
    }
}
