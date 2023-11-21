<?php
/**
 * This trait provides the functionality to filter Firestore data based on the given parameters.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\traits;

use Google\Cloud\Firestore\Filter;

trait WhereTrait
{
    /**
     * Filter the Firestore collection based on the given field path, operator, and value.
     *
     * @param array $filter An array of [$fieldPath, $operator, $value] to filter the collection.
     * @param object $firestore The Firestore instance.
     * @return \Google\Cloud\Firestore\Query\Query The filtered Firestore query.
     * @throws \Exception If $filter array does not contain exactly 3 elements.
     */
    protected function fWhere(array $filter, $firestore, $documentId = null, $collectionName = null)
    {
        if(count(array_keys($filter)) !== 3){
            return throw new \Exception('$filters should be an array of [$fieldPath, $operator, $value]. Check documentation for guide.', 1);
        }

        [$fieldPath, $operator, $value] = $filter;

        $filtered = Filter::field($fieldPath, $operator, $value);
        $collectionReference = $firestore;
        if($documentId !== null && $collectionName !== null){
            $query = $collectionReference->document($documentId)->collection($collectionName)->where($filtered);
            return $query;
        }
        $query = $collectionReference->where($filtered);
        return $query;
    }
}
