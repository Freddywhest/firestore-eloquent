<?php
/**
 * This trait adds the ability to add "AND" conditions to Firestore "where" query clause.
 *
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\traits
 */
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\traits;

use Google\Cloud\Firestore\Filter;

trait AndWhereTrait
{
    /**
     * Add "AND" condition(s) to Firestore "where" query clause
     *
     * @param array $filters An array of filters, each consisting of a field path, operator, and value
     * Structure of each item in the array: [[$fieldPath, $operator, $value], [$fieldPath, $operator, $value], ....].
     *
     * @param object $firestore Firestore instance
     *
     * @return object Firestore instance with "where" clause added
     *
     * @throws \Exception if $filters parameter is not of array type or doesn't contain correct subarray structure.
     */

    protected function fAndWhere(array $filters, $firestore, $documentId = null, $collectionName = null)
    {
        if(!is_array($filters)){
            return throw new \Exception('$filters parameter is not of array type or doesn\'t contain correct subarray structure. it should be an array with subarray of [[$fieldPath, $operator, $value], [$fieldPath, $operator, $value], ....]. Check documentation for guide.', 1);
        }

        $filtersArray = [];

        foreach ($filters as $filter) {
            if(!is_array($filter)){
                return throw new \Exception('$filters parameter is not of array type or doesn\'t contain correct subarray structure. it should be an array with subarray of [[$fieldPath, $operator, $value], [$fieldPath, $operator, $value], ....]. Check documentation for guide.', 1);
            }

            if(count(array_keys($filter)) !== 3){
                return throw new \Exception('$filters parameter is not of array type or doesn\'t contain correct subarray structure. it should be an array with subarray of [[$fieldPath, $operator, $value], [$fieldPath, $operator, $value], ....]. Check documentation for guide.', 1);
            }

            [$fieldPath, $operator, $value] = $filter;

            array_push($filtersArray, Filter::field($fieldPath, $operator, $value));
        }

        $filter = Filter::and($filtersArray);

        $collectionReference = $firestore;
        if($documentId !== null && $collectionName !== null){
            $query = $collectionReference->document($documentId)->collection($collectionName)->where($filter);
            return $query;
        }
        $query = $collectionReference->where($filter);

        return $query;
    }
}
