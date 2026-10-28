<?php

namespace Roddy\FirestoreEloquent\Firestore\Relations;

use Roddy\FirestoreEloquent\Facade\FModel;
use Illuminate\Support\Str;

trait FHasMany
{
    public function fHasMany(string $related, ?string $foreignKey = null, ?string $localKey = null)
    {
        if (!$related) {
            throw new \Exception("Related class not provided");
        }

        if (!str_contains($related, '\\')) {
            $related = rtrim((string) config('firebase.class_namespace', 'App\\FModels'), '\\') . '\\' . $related;
        }

        if (!class_exists($related)) {
            throw new \Exception("Class {$related} not found");
        }

        if (!is_subclass_of($related, FModel::class)) {
            throw new \Exception("Class {$related} is not a model");
        }

        $parentName = (new \ReflectionClass($this))->getShortName();

        return [
            'related' => $related,
            'foreignKey' => $foreignKey ?? Str::snake($parentName) . '_id',
            'localKey' => $localKey ?? $this->primaryKey,
            'relation' => 'hasMany'
        ];
    }
}
