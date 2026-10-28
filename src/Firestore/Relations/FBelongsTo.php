<?php

namespace Roddy\FirestoreEloquent\Firestore\Relations;

use Roddy\FirestoreEloquent\Facade\FModel;
use Illuminate\Support\Str;

trait FBelongsTo
{
    public function fBelongsTo(string $related, ?string $foreignKey = null, ?string $localKey = null)
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

        $relatedModel = new $related();
        $relatedName = (new \ReflectionClass($related))->getShortName();

        return [
            'related' => $related,
            'foreignKey' => $foreignKey ?? $relatedModel->primaryKey,
            'localKey' => $localKey ?? Str::snake($relatedName) . '_id',
            'relation' => 'belongsTo'
        ];
    }
}
