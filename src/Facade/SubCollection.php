<?php

namespace Roddy\FirestoreEloquent\Facade;

use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionMainController;

/**
 * @method static SubCollections subCollection(string $value)
 * @method static SubCollections primaryKey(string $value)
 * @method static SubCollections fillableFields(array $value)
 * @method static SubCollections requiredFields(array $value)
 * @method static SubCollections defaultFields(array $value)
 * @method static SubCollections fieldTypes(array $value)
 * @method static SubCollections hiddenFields(array $value)
 * @method static SubCollections keyToUse(string $value)
 * @method static SubCollections load()
 */

class SubCollection
{

    public static function __callStatic($name, $arguments)
    {
        if (method_exists(SubCollectionMainController::class, $name)) {
            return (new SubCollectionMainController())->$name(...$arguments);
        } else {
            throw new \Exception("Method {$name} not found in sub collection. Class: " . self::class);
        }
    }
}
