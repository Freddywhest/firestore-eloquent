<?php
/**
 * This trait provides the functionality to create a new document in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\traits
 */
namespace Roddy\FirestoreEloquent\Firestore\Relations;

trait FHasMany
{
    /**
     * FILEPATH: /f:/Projects/OrbitRideBackend V2/vendor/roddy/firestore-eloquent/src/Firestore/Relations/FHasMany.php
     *
     * Defines a has-many relationship between two models in Firestore.
     *
     * @param string $model The name of the related model.
     * @param string $foreignKey The name of the foreign key in the related model.
     * @param string|null $localKey The name of the local key in the current model. If null, defaults to the foreign key.
     * @param array $data An array of data containing the local key value.
     *
     * @return mixed|null Returns the related model instances or null if the model does not exist or is not a string.
     */
    protected function fHasMany(string $model, string $foreignKey, ?string $localKey = null, array $data)
    {
        /**
         * Set the local key if it is not defined.
         *
         * @param string|null $localKey The local key.
         * @param string $foreignKey The foreign key.
         * @return void
         */
        if(!$localKey){
            $localKey = $foreignKey;
        }

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
        $modelArrName = explode('\\', $model);

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

        /**
         * This code block is from the file FHasMany.php located at /vendor/roddy/firestore-eloquent/src/Firestore/Relations/FHasMany.php.
         * This function checks if the given model exists and returns the result of a query that fetches all the records from the related model that have a foreign key matching the local model's primary key.
         *
         * @param string $model The name of the related model.
         * @param array $data The data of the local model.
         * @param string $localKey The name of the local model's primary key.
         * @param string $foreignKey The name of the foreign key in the related model.
         * @param string $namespace The namespace of the related model.
         * @param string $modelName The name of the related model class.
         * @return mixed|null The result of the query or null if the model does not exist or is not a string.
         */
        if(gettype($model) === 'string'){
            $class = $namespace.'\\'.$modelName;

            if(!class_exists($class)){
                $trace = debug_backtrace();
                trigger_error(
                    "Model '".$model."' does not exists.".
                    ' in ' . $trace[0]['file'] .
                    ' on line ' . $trace[0]['line'],
                    E_USER_NOTICE);
                return null;
            }else{
                if(isset($data[$localKey])){
                    $localModelId = $data[$localKey];
                }else{
                    return [];
                }

                $localModelId = $data[$localKey];
                $result = $class::where([$foreignKey, '=', $localModelId]);
                return $result;
            }
        }else{
            $trace = debug_backtrace();
                trigger_error(
                    "Model '".$model."' should be type string.".
                    ' in ' . $trace[0]['file'] .
                    ' on line ' . $trace[0]['line'],
                    E_USER_NOTICE);
                return null;
        }
    }
}
