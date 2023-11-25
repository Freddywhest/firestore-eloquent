<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features;

class ItemNotFoundHelper
{
    public function __get($name)
    {
        return null;
    }

    public function delete()
    {
        return false;
    }

    public function update()
    {
        return false;
    }

    public function exists()
    {
        return false;
    }

    public function collection()
    {
        return false;
    }

    public function collectionName()
    {
        return null;
    }

    public function documentId()
    {
        return null;
    }

    public function data()
    {
        return null;
    }

    public function hasOne(string $model, string $foreignKey, ?string $localKey = null)
    {
        return null;
    }

    public function hasMany(string $model, string $foreignKey, ?string $localKey = null)
    {
        return null;
    }
}
