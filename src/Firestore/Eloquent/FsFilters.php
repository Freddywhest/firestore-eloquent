<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Carbon\Carbon;

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
        $convertedFields = [];

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $convertedFields[$key] = $this->convertValue($value);
            }
        } else {
            $convertedFields = $this->convertValue($data);
        }

        return $convertedFields;
    }

    private function convertValue($value)
    {
        if (is_null($value)) {
            return ["nullValue" => null];
        } elseif (is_bool($value)) {
            return ["booleanValue" => $value];
        } elseif (is_int($value)) {
            return ["integerValue" => $value];
        } elseif (is_float($value)) {
            return ["doubleValue" => $value];
        } elseif (is_string($value)) {
            // Detect Firestore timestamp format
            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/', $value) || strtotime($value)) {
                return ["timestampValue" => Carbon::parse($value)->toAtomString()];
            }
            return ["stringValue" => $value];
        } elseif (is_array($value)) {
            // Check if it's an indexed array or associative
            if (array_keys($value) === range(0, count($value) - 1)) {
                return [
                    "arrayValue" => [
                        "values" => array_map([$this, "convertValue"], $value)
                    ]
                ];
            } else {
                // Associative array -> mapValue
                $mapFields = [];
                foreach ($value as $k => $v) {
                    $mapFields[$k] = $this->convertValue($v);
                }
                return [
                    "mapValue" => [
                        "fields" => $mapFields
                    ]
                ];
            }
        }
        return null; // Fallback case
    }
}
