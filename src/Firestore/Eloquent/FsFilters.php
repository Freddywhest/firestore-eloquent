<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

final class FsFilters
{
    /**
     * Helper function for `and` filter.
     *
     * Example:
     * ```
     * use Google\Cloud\Firestore\Filter;
     *
     * $result = $query->where(Filter::and([
     *     Filter::field('firstName', '=', 'John'),
     *     Filter::field('age', '>', '25')
     * ]));
     * ```
     *
     * @param array $filters A filter array.
     * @return array A composite filter array.
     */
    public function and(array $filters)
    {
        return $this->compositeFilter("AND", $filters);
    }

    protected function mapOperator($operator)
    {
        $mapping = [
            '=' => 'EQUAL',
            '>' => 'GREATER_THAN',
            '<' => 'LESS_THAN',
            '>=' => 'GREATER_THAN_OR_EQUAL',
            '<=' => 'LESS_THAN_OR_EQUAL',
            '!=' => 'NOT_EQUAL',
            'in' => 'IN',
            'not in' => 'NOT_IN',
            'not-in' => 'NOT_IN',
            'array-contains' => 'ARRAY_CONTAINS', // Firestore does not support LIKE
            'array-contains-any' => 'ARRAY_CONTAINS_ANY', // Firestore does not support NOT LIKE
        ];
        return $mapping[$operator] ?? $operator;
    }

    /**
     * Helper function for `or` filter.
     *
     * Example:
     * ```
     * use Google\Cloud\Firestore\Filter;
     *
     * $result = $query->where(Filter::or([
     *     Filter::field('firstName', '=', 'John'),
     *     Filter::field('firstName', '=', 'Monica')
     * ]));
     * ```
     *
     * @param array $filters A filter array.
     * @return array A composite Filter array.
     */
    public function or(array $filters)
    {
        return $this->compositeFilter("OR", $filters);
    }

    /**
     * Helper function for field filter.
     *
     * Example:
     * ```
     * use Google\Cloud\Firestore\Filter;
     *
     * $result = $query->where(Filter::field('firstName', '=', 'John'));
     * ```
     *
     * @param string|FieldPath $fieldPath A field to filter by.
     * @param string|int $operator An operator to filter by.
     * @param mixed $value A value to compare to.
     * @return array A field Filter array.
     */
    public function field($fieldPath, $operator, $value)
    {
        if (array_key_exists('nullValue', $value)) {
            if ($operator === '=') {
                return ['unaryFilter' => ['field' => ['fieldPath' => $fieldPath], 'op' => 'IS_NULL']];
            }
            if ($operator === '!=') {
                return ['unaryFilter' => ['field' => ['fieldPath' => $fieldPath], 'op' => 'IS_NOT_NULL']];
            }
        }

        $filter = [
            'fieldFilter' => [
                'field' => ["fieldPath" => $fieldPath],
                'op' => $this->mapOperator($operator),
                'value' => $value
            ]
        ];
        return $filter;
    }

    public function fieldForArray($fieldPath, $operator, $value)
    {
        $filter = [
            'fieldFilter' => [
                'field' => ["fieldPath" => $fieldPath],
                'op' => $this->mapOperator($operator),
                'value' => $value
            ]
        ];
        return $filter;
    }

    /**
     * Helper function for composite filter.
     *
     * @param int $operator An operator enum (Operator::PBAND | Operator::PBOR).
     * @param array $filters A filter array.
     * @return array A composite filter array.
     */
    public function compositeFilter($operator, $filters)
    {
        $filter = [
            'compositeFilter' => [
                'op' => $operator,
                'filters' => $filters
            ]
        ];
        return $filter;
    }

    public function convertToFirestoreFormat($data)
    {
        return FirestoreValue::encode($data);
    }
}
