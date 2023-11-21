<?php
/**
 * This trait provides the functionality to create a new document in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\traits;

use Google\Cloud\Firestore\Filter;

trait OrWhereTrait
{
    /**
     * Creates a Firestore query with an OR operator for multiple where clauses.
     *
     * @param array $filters An array of where clauses in the form of [[$fieldPath, $operator, $value], [$fieldPath, $operator, $value], ...]
     * @param object $firestore The Firestore instance
     *
     * @return \Google\Cloud\Firestore\Query The Firestore query instance
     *
     * @throws \Exception If $filters is not an array of valid where clauses
     */
    protected function fOrWhere(array $filters, $firestore, $documentId = null, $collectionName = null)
    {
        if(!is_array($filters)){
            return throw new \Exception('$filters should be an array of [[$fieldPath, $operator, $value], [$fieldPath, $operator, $value], ....]. Check documentation for guide.', 1);
        }

        $filtersArray = [];

        foreach ($filters as $filter) {
            if(!is_array($filter)){
                return throw new \Exception('$filters should be an array of [[$fieldPath, $operator, $value], [$fieldPath, $operator, $value], ....]. Check documentation for guide.', 1);
            }

            if(count(array_keys($filter)) !== 3){
                return throw new \Exception('$filters should be an array of [[$fieldPath, $operator, $value], [$fieldPath, $operator, $value], ....]. Check documentation for guide.', 1);
            }

            [$fieldPath, $operator, $value] = $filter;

            array_push($filtersArray, Filter::field($fieldPath, $operator, $value));
        }

        $filter = Filter::or($filtersArray);

        $collectionReference = $firestore;

        if($documentId !== null && $collectionName !== null){
            $query = $collectionReference->document($documentId)->collection($collectionName)->where($filter);
            return $query;
        }

        $query = $collectionReference->where($filter);

        return $query;
    }
}
