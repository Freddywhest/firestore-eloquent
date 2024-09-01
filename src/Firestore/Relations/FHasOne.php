<?php

/**
 * This trait provides the functionality to create a new document in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 */

namespace Roddy\FirestoreEloquent\Firestore\Relations;

trait FHasOne
{
    protected function fHasOne(string $model, string $foreignKey, ?string $localKey, array $data)
    {
        /**
         * Set the local key if it is not already set.
         *
         * @param  string|null  $localKey  The local key to set.
         * @param  string  $foreignKey  The foreign key to use.
         */
        if (! $localKey) {
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
         * @param  string  $model  The fully qualified class name of the related model.
         * @return array An array containing the exploded parts of the class name.
         */
        $modelArrName = explode('\\', $model);

        /**
         * Explodes the last element of the given array by '/' and returns the resulting array.
         *
         * @param  array  $modelArrName  The array to be exploded.
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
         * @param  array  $modelArrName3  The input array to be processed.
         * @return array The resulting array after exploding the last element by colon.
         */
        $modelArrName4 = explode(':', end($modelArrName3));

        /**
         * Get the last element of the array $modelArrName4 and assign it to $modelName.
         */
        $modelName = end($modelArrName4);

        /**
         * FILEPATH: /vendor/roddy/firestore-eloquent/src/Firestore/Relations/FHasOne.php
         *
         * Checks if the given model exists and returns the first record matching the foreign key.
         *
         * @param  mixed  $model  The model to check.
         * @param  string  $namespace  The namespace of the model.
         * @param  string  $modelName  The name of the model.
         * @param  array  $data  The data to search for.
         * @param  string  $localKey  The local key to search for.
         * @param  string  $foreignKey  The foreign key to search for.
         * @return mixed|null The first record matching the foreign key or null if the model does not exist.
         */
        if (gettype($model) === 'string') {
            $class = $namespace.'\\'.$modelName;

            if (! class_exists($class)) {
                $trace = debug_backtrace();
                trigger_error(
                    "Model '".$model."' does not exists.".
                        ' in '.$trace[0]['file'].
                        ' on line '.$trace[0]['line'],
                    E_USER_NOTICE
                );

                return null;
            } else {
                if (isset($data[$localKey])) {
                    $localModelId = $data[$localKey];
                } else {
                    return [];
                }

                $localModelId = $data[$localKey];
                $result = $class::where([$foreignKey, '=', $localModelId])->first()->data();

                return $result;
            }
        }
    }
}
