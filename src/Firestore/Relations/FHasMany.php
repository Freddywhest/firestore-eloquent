<?php

namespace Roddy\FirestoreEloquent\Firestore\Relations;

use Roddy\FirestoreEloquent\Facade\FModel;

trait FHasMany
{
    public function fHasMany(string $related, ?string $foreignKey = null, ?string $localKey = null)
    {
        if (!$related) {
            throw new \Exception("Related class not provided");
        }

        //check if the related class starts with App\FModels and add the namespace if it doesn't
        if (strpos($related, 'App\FModels') !== 0) {
            $related = 'App\FModels\\' . $related;
        }

        // Check if the related class is a model
        if (!is_subclass_of($related, FModel::class)) {
            throw new \Exception("Class {$related} is not a model");
        }

        if (!class_exists($related)) {
            throw new \Exception("Class {$related} not found");
        }

        if ($related === $this::class) {
            throw new \Exception("Related class cannot be the same as the current class");
        }

        return [
            'related' => $related,
            'foreignKey' => $foreignKey ?? (new $related)->primaryKey,
            'localKey' => $localKey ?? $this->primaryKey,
            'relation' => 'hasMany'
        ];
    }
}
