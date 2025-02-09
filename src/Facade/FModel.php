<?php

namespace Roddy\FirestoreEloquent\Facade;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FClient;

class FModel
{
    /**
     * @property string $collection
     * @property mixed $model
     * @property string $primaryKey
     * @property array $fillable
     * @property array $required
     * @property array $default
     * @property array $fieldTypes
     */

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
        if (!config('firebase.projects.app.project_id', env('FIREBASE_PROJECT_ID'))) {
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
            return (new FClient(
                collection: (new static)->collection,
                model: (new static)->model,
                primaryKey: (new static)->primaryKey,
                fillable: (new static)->fillable,
                required: (new static)->required,
                default: (new static)->default,
                fieldTypes: (new static)->fieldTypes,
                hidden: (new static)->hidden,
                modelClass: get_called_class()
            ))->$name(...$arguments);
        } catch (\Throwable $th) {
            throw new \Exception('Error: ' . $th->getMessage() . ". From " . static::class . "::" . $name . "().", 1);
        }
    }
}
