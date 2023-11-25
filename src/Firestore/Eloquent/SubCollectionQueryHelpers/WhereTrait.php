<?php
/**
 * This trait provides the functionality to filter Firestore data based on the given parameters.
 * @package Roddy\FirestoreEloquent
*/
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers;

use Google\Cloud\Firestore\Filter;

trait WhereTrait
{
    protected function fWhere(array $filter, $row)
    {
        if(count(array_keys($filter)) !== 3){
            return throw new \Exception('$filters should be an array of [$fieldPath, $operator, $value]. Check documentation for guide.', 1);
        }

        [$fieldPath, $operator, $value] = $filter;

        if(!in_array($operator, ['<', '<=', '==', '=', '>', '>=', 'array-contains', 'in', 'array-contains-any'])){
            return throw new \Exception('Invalid operator. Valid operators are <, <=, ==, >, >=, array-contains, in, array-contains-any but got "'.$operator.'" as operator.', 1);
        }

        if($operator === 'in' || $operator === 'array-contains-any'){
            if(!is_array($value)){
                return throw new \Exception('Invalid value. Value should be an array but got '.gettype($value).'.', 1);
            }
        }

        $filtered = Filter::field($fieldPath, $operator, $value);

        $query = $row->reference()->collection($this->subCollectionName)->where($filtered);

        return $query;
    }
}
