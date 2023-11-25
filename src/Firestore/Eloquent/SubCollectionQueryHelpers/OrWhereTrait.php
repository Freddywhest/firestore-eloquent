<?php
/**
 * This trait provides the functionality to create a new document in Firestore collection.
 * It includes methods to check the type of a given value, filter the data to be stored in the document,
 * and validate required and fillable attributes of the model.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers;

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
    protected function fOrWhere(array $filters, $row)
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

            if(!in_array($operator, ['<', '<=', '=', '==', '>', '>=', 'array-contains', 'in', 'array-contains-any'])){
                return throw new \Exception('Invalid operator. Valid operators are <, <=, ==, >, >=, array-contains, in, array-contains-any but got "'.$operator.'" as operator.', 1);
            }

            if($operator === 'in' || $operator === 'array-contains-any'){
                if(!is_array($value)){
                    return throw new \Exception('Invalid value. Value should be an array but got '.gettype($value).'.', 1);
                }
            }

            array_push($filtersArray, Filter::field($fieldPath, $operator, $value));
        }

        $filter = Filter::or($filtersArray);

        $query = $row->reference()->collection($this->subCollectionName)->where($filtersArray);

        return $query;
    }
}
