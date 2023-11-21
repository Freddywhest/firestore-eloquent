<?php
/**
 * Class FirestoreDataFormat
 * @package Firestore\Eloquent
 */
namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\FirestoreConnectionTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\PaginateTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\UpdateTrait;
use Roddy\FirestoreEloquent\Firestore\Relations\FHasMany;
use Roddy\FirestoreEloquent\Firestore\Relations\FHasOne;

class SubCollectionDataFormat
{
    use FHasOne;
    use FHasMany;
    use UpdateTrait;
    use PaginateTrait;
    use FirestoreConnectionTrait;
    public function __construct(
        private array $data,
        private ?string $documentId,
        private bool $exists,
        private $collectionName,
        private $model,
        private $subCollectionName,
        private $documentIdForMainCollection
    )
    {
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
        if(!config('firebase.projects.app.project_id', env('FIREBASE_PROJECT_ID'))){
            throw new \Exception("FIREBASE_PROJECT_ID not set in .env file.");
        }
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
        if(count($this->data) < 1){
            return [];
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
        if(count($this->data) == 0){
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
    public function update(array $data, array $fillable = [], array $required = [], array $default = [], array $fieldTypes = [])
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

        $class = $namespace.'\\'.$modelName;

        $this->fupdate(
            data: $data,
            firestore: $this->fConnection($this->collectionName)->document($this->documentIdForMainCollection)->collection($this->subCollectionName),
            documentId: $this->documentId,
            primaryKey: (new $class)->primaryKey,
            fillable: $fillable,
            required: $required,
            fieldTypes: $fieldTypes,
            subCollectionName: $this->subCollectionName
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
        $this->fConnection($this->collectionName)
            ->document($this->documentIdForMainCollection)
            ->collection($this->subCollectionName)
            ->document($this->documentId)
            ->delete();
    }
}
