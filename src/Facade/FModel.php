<?php

namespace Roddy\FirestoreEloquent\Facade;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FClient;
use Roddy\FirestoreEloquent\Firestore\Eloquent\Traits\HasStaticQueries;
use Roddy\FirestoreEloquent\Firestore\Eloquent\Traits\FRelations;

/**
 * Base Firestore model.
 *
 * The query API is dispatched through __callStatic(). These declarations are
 * intentionally kept here so Intelephense and other PHP language servers can
 * autocomplete the inherited API on application models such as Ride.
 *
 * @property-read string $collection
 * @property-read string $model
 * @property-read string $primaryKey
 * @property-read array<int, string> $fillable
 * @property-read array<int, string> $required
 * @property-read array<string, mixed> $default
 * @property-read array<string, string> $fieldTypes
 * @property-read array<int, string> $hidden
 *
 * @mixin FClient
 */
class FModel
{
    use FRelations, HasStaticQueries;

    protected $collection;
    private $model;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $required = [];
    protected $default = [];
    protected $fieldTypes = [];
    protected $hidden = [];

    public function __construct()
    {
        /**
         * Check if FIREBASE_PROJECT_ID is set in .env file or config file.
         * If not set, throw an exception.
         */
        $project = config('firebase.default', 'app');
        if (!config("firebase.projects.{$project}.database.project_id")) {
            throw new \Exception("FIREBASE_PROJECT_ID not set in .env file.");
        }

        /**
         * Get the class name and set the collection name if not already set.
         */
        $className = explode('\\', $this::class);
        if (!$this->collection) {
            $this->collection = end($className) . 's';
        }

        /**
         * Set the model name.
         */
        $this->model = end($className);
    }

    public function __get($name)
    {
        if ($name === 'modelClass') {
            return get_called_class();
        }

        if (in_array($name, ['primaryKey', 'fillable', 'required', 'default', 'fieldTypes', 'hidden', 'model', 'collection'])) {
            return $this->{$name};
        }
    }
    public static function __callStatic($name, $arguments)
    {
        try {
            return static::query()->$name(...$arguments);
        } catch (\Throwable $th) {
            throw new \Exception('Error: ' . $th->getMessage() . ". From " . static::class . "::" . $name . "().", 1);
        }
    }
}
