<?php

/**
 * Class FirestoreDataFormat
 * @package Firestore\Eloquent
 */

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\PaginateTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\UpdateTrait;
use Roddy\FirestoreEloquent\Firestore\Relations\FHasMany;
use Roddy\FirestoreEloquent\Firestore\Relations\FHasOne;

class FirestoreDataFormat
{
    use FHasOne;
    use FHasMany;
    use UpdateTrait;
    use PaginateTrait;

    private $data;
    private ?string $documentId;
    private bool $exists;

    public function __construct(
        private object $row,
        private $collectionName,
        private $model
    ) {
        /**
         * Constructor for FirestoreDataFormat class.
         *
         * @param array $data The data to be formatted.
         * @param string $documentId The ID of the document.
         * @param bool $exists Whether the document exists or not.
         * @param mixed $collectionName The name of the collection.
         * @param int $count The number of documents.
         * @param mixed $model The model to be used.
         * @throws \Exception if FIREBASE_PROJECT_ID is not set in .env file.
         */
        if (!config('firebase.projects.app.project_id', env('FIREBASE_PROJECT_ID'))) {
            throw new \Exception("FIREBASE_PROJECT_ID not set in .env file.");
        }

        $this->data = $row->data();
        $this->documentId = $row->id();
        $this->exists = count($this->data) > 0 ? true : false;
    }

    /**
     * Magic method to set the value of a property.
     *
     * @param string $name The name of the property to set.
     * @param mixed $value The value to set the property to.
     * @return void
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * FILEPATH: /f:/Projects/OrbitRideBackend V2/vendor/roddy/firestore-eloquent/src/Firestore/Eloquent/FirestoreDataFormat.php
     *
     * Magic method to get the value of a property.
     *
     * @param string $name The name of the property to get.
     * @return mixed|null The value of the property if it exists, otherwise null.
     */
    public function __get($name)
    {
        if (count($this->data) < 1) { // If the document does not exist.
            return null;
        }

        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        /* $trace = debug_backtrace();
        trigger_error(
            'Attempt to read undefined property "'.$name.'"'.
            ' which does not exists in the database/document. Trace in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE); */
        return null;
    }
    /**
     * Returns the data as an object.
     *
     * @return object The data as an object.
     */
    public function data()
    {
        if (count($this->data) == 0) {
            return null;
        }

        return (object) $this->data;
    }

    /**
     * Get the document ID.
     *
     * @return string
     */
    public function documentId()
    {
        return $this->documentId;
    }

    /**
     * Get the name of the Firestore collection.
     *
     * @return string
     */
    public function collectionName()
    {
        return $this->collectionName;
    }


    /**
     * Check if the data exists.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->exists;
    }

    public function updateSubDocument(array $data, array $fillable = [], array $required = [], array $fieldTypes = [], $primaryKey = 'id')
    {
        return $this->fupdate(
            data: $data,
            row: $this->row,
            primaryKey: $primaryKey,
            fillable: $fillable,
            required: $required,
            fieldTypes: $fieldTypes,
        );
    }

    /**
     * Update the Firestore document with the given data.
     *
     * @param array $data The data to update the document with.
     * @return void
     *
     * @example
     * ```php
     * $user = User::find('user1');
     * $user->update(['name' => 'John Doe']);
     * ```
     */
    public function update(array $data)
    {
        /**
         * Get the namespace for the related model class.
         * If the 'firebase.class_namespace' configuration value is not set, the default namespace is 'App\FModels'.
         *
         * @return string The namespace for the related model class.
         */
        $namespace = config('firebase.class_namespace') ?? 'App\\FModels';

        /**
         * Explodes the fully qualified class name of the related model into an array.
         *
         * @param string $model The fully qualified class name of the related model.
         * @return array An array containing the exploded parts of the class name.
         */
        $modelArrName = explode('\\', $this->model);

        /**
         * Explodes the last element of the given array by '/' and returns the resulting array.
         *
         * @param array $modelArrName The array to be exploded.
         * @return array The resulting array after exploding the last element of the given array.
         */
        $modelArrName2 = explode('/', end($modelArrName));

        /**
         * Explodes the last element of the given model array name by '::' and returns the resulting array.
         */
        $modelArrName3 = explode('::', end($modelArrName2));

        /**
         * Explodes the last element of the given array by colon and returns the resulting array.
         *
         * @param array $modelArrName3 The input array to be processed.
         * @return array The resulting array after exploding the last element by colon.
         */
        $modelArrName4 = explode(':', end($modelArrName3));


        /**
         * Get the last element of the array $modelArrName4 and assign it to $modelName.
         */
        $modelName = end($modelArrName4);

        $class = $namespace . '\\' . $modelName;

        return $this->fupdate(
            data: $data,
            row: $this->row,
            primaryKey: (new $class)->primaryKey,
            fillable: (new $class)->fillable,
            required: (new $class)->required,
            fieldTypes: (new $class)->fieldTypes
        );
    }

    /**
     * Delete the Firestore document represented by this FirestoreDataFormat instance.
     *
     * @return void
     *
     * @example
     * ```php
     * $user = User::find('user1');
     * $user->delete();
     * ```
     */
    public function delete()
    {
        try {
            return $this->row->reference()->delete();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param string $model The related model class name.
     * @param string $foreignKey The foreign key of the related model.
     * @param string|null $localKey The local key of the parent model.
     * @return mixed
     *
     * @example
     * ```php
     * $user = User::find('user1');
     * $profile = $user->hasOne('Profile', 'user_id', 'id');
     * ```
     */
    public function hasOne(string $model, string $foreignKey, ?string $localKey = null)
    {
        return $this->fHasOne($model, $foreignKey, $localKey, $this->data);
    }

    /**
     * Define a has-many relationship.
     *
     * @param string $model The related model class name.
     * @param string $foreignKey The foreign key of the related model.
     * @param string|null $localKey The local key of the current model.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The has-many relationship instance.
     *
     * @example
     * ```php
     * $user = User::find('user1');
     * $posts = $user->hasMany('Post', 'user_id', 'id');
     * ```
     */
    public function hasMany(string $model, string $foreignKey, ?string $localKey = null)
    {
        return $this->fHasMany($model, $foreignKey, $localKey, $this->data);
    }

    public function collection($subCollectionName)
    {
        return new SubDocumentModel(
            row: $this->row,
            subCollectionName: $subCollectionName,
            model: $this->model,
            collection: $this->collectionName
        );
    }

    public function toArray()
    {
        return $this->data;
    }
}
