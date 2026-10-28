<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FsFilters;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreValue;

trait WhereQueries
{
    public $whereIn, $whereContains, $whereContainsAny, $whereNotIn, $whereDate;

    public function whereIn($field, array $value)
    {
        if ($value === [] || count($value) > 30) {
            throw new \InvalidArgumentException('WhereIn expects between 1 and 30 values.');
        }

        if ($this->whereContains || $this->whereContainsAny || $this->whereNotIn || $this->whereDate || $this->where || $this->whereIn) {
            return throw new \Exception('Invalid operator. WhereIn cannot be used with whereContains, whereContainsAny, whereNotIn, another whereIn, whereDate or where.', 1);
        }

        $s = new FsFilters();
        $f = $s->convertToFirestoreFormat($value);
        $f = $s->fieldForArray($field, 'in', $f);
        $this->whereIn = $f;
        return $this;
    }

    public function whereContains($field, mixed $value)
    {
        if (is_array($value) && array_is_list($value)) {
            throw new \InvalidArgumentException('WhereContains cannot compare a nested Firestore array.');
        }

        if ($this->whereIn || $this->whereContainsAny || $this->whereNotIn || $this->whereDate || $this->where || $this->whereContains) {
            return throw new \Exception('Invalid operator. WhereContains cannot be used with whereIn, whereContainsAny, whereNotIn, another whereContains, whereDate or where.', 1);
        }

        $s = new FsFilters();
        $f = $s->convertToFirestoreFormat($value);
        $f = $s->fieldForArray($field, 'array-contains', $f);
        $this->whereContains = $f;
        return $this;
    }

    public function whereContainsAny($field, array $value)
    {
        if ($value === [] || count($value) > 30) {
            throw new \InvalidArgumentException('WhereContainsAny expects between 1 and 30 values.');
        }

        if ($this->whereIn || $this->whereContainsAny || $this->whereNotIn || $this->whereDate || $this->where || $this->whereContains) {
            return throw new \Exception('Invalid operator. WhereContainsAny cannot be used with whereIn, whereContains, whereNotIn, another whereContainsAny, whereDate, where or whereContains.', 1);
        }

        $s = new FsFilters();
        $f = $s->convertToFirestoreFormat($value);
        $f = $s->fieldForArray($field, 'array-contains-any', $f);
        $this->whereContainsAny = $f;
        return $this;
    }

    public function whereNotIn($field, array $value)
    {
        if ($value === [] || count($value) > 10) {
            throw new \InvalidArgumentException('WhereNotIn expects between 1 and 10 values.');
        }

        if ($this->whereIn || $this->whereContainsAny || $this->whereNotIn || $this->whereDate || $this->where || $this->whereContains) {
            return throw new \Exception('Invalid operator. WhereNotIn cannot be used with whereIn, whereContainsAny, another whereNotIn, whereDate, where or whereContains.', 1);
        }

        $s = new FsFilters();
        $f = $s->convertToFirestoreFormat($value);
        $f = $s->fieldForArray($field, 'not-in', $f);
        $this->whereNotIn = $f;
        return $this;
    }

    private function isNestedArray($array)
    {
        return is_array($array) && count($array) > 0 && is_array($array[0]);
    }

    private function isValidNestedArray($array)
    {
        // Check if it's an array
        if (!is_array($array)) {
            return false;
        }

        if (empty($array)) {
            return false;
        }

        $tuples = $this->isNestedArray($array) ? $array : [$array];
        foreach ($tuples as $tuple) {
            if (!is_array($tuple) || !in_array(count($tuple), [2, 3], true)) {
                return false;
            }
            if (!isset($tuple[0]) || !is_string($tuple[0]) || trim($tuple[0]) === '') {
                return false;
            }
        }

        return true;
    }

    public function whereDate(array|string $filters, mixed $operator = null, mixed $value = null)
    {
        $or = is_array($filters) && is_bool($operator) ? $operator : false;
        if (is_string($filters)) {
            $filters = $this->normalizeWhereArguments($filters, $operator, $value, func_num_args());
        }

        if (!$this->isValidNestedArray($filters)) {
            throw new \Exception('Invalid value. WhereDate value should be an array or a nested array like [[], [], []] or [] and not empty but got type: ' . gettype($filters) . ' and value: ' . json_encode($filters) . '.', 1);
        }

        // Prevent conflicts with incompatible where clauses
        if ($this->whereIn || $this->whereContainsAny || $this->whereNotIn || $this->whereDate || $this->where || $this->whereContains) {
            throw new \Exception('Invalid operator. WhereDate cannot be used with whereIn, whereContainsAny, whereNotIn, another whereDate, where or whereContains.', 1);
        }


        if ($this->isNestedArray($filters)) {
            $d = [];
            foreach ($filters as $filter) {
                if (count($filter) === 3) {
                    [$field, $operator, $value] = $filter;
                } else if (count(array_keys($filter)) === 2) {
                    [$field, $value] = $filter;
                    $operator = '=';
                }

                if (!in_array($operator, ["=", ">", "<", ">=", "<=", "!="])) {
                    throw new \Exception("Invalid operator: " . $operator);
                }

                if (!is_string($value) || strtotime($value) === false) {
                    throw new \Exception('Invalid value. WhereDate value should be a valid date but got ' . $value . '.', 1);
                }

                $s = new FsFilters();
                $formattedDate = FirestoreValue::encode($value, 'timestamp');
                $filter = $s->field($field, $operator, $formattedDate);
                $d[] = $filter;
            }

            $this->whereDate = $s->compositeFilter($or ? 'OR' : 'AND', $d);
        } else {
            if (count($filters) === 3) {
                [$field, $operator, $value] = $filters;
            } else if (count(array_keys($filters)) === 2) {
                [$field, $value] = $filters;
                $operator = '=';
            } else {
                throw new \Exception('Invalid value. WhereDate value should be an array of [$field, $operator, $value] or [$field, $value] but got ' . json_encode($filters) . '.', 1);
            }

            if (!in_array($operator, ["=", ">", "<", ">=", "<=", "!="])) {
                throw new \Exception("Invalid operator: " . $operator);
            }

            if (!is_string($value) || strtotime($value) === false) {
                throw new \Exception('Invalid value. WhereDate value should be a valid date but got ' . $value . '.', 1);
            }

            $s = new FsFilters();
            $formattedDate = FirestoreValue::encode($value, 'timestamp');
            $filter = $s->field($field, $operator, $formattedDate);

            $this->whereDate = $filter;
        }

        return $this;
    }


    public function where(array|string $filters, mixed $operator = null, mixed $value = null)
    {
        if (is_string($filters)) {
            $filters = $this->normalizeWhereArguments($filters, $operator, $value, func_num_args());
        }

        if (!$this->isValidNestedArray($filters)) {
            throw new \Exception('Invalid value. Where value should be an array or a nested array like [[], [], []] or [] and not empty but got type: ' . gettype($filters) . ' and value: ' . json_encode($filters) . '.', 1);
        }

        // Prevent conflicts with incompatible where clauses
        if ($this->whereIn || $this->whereContainsAny || $this->whereNotIn || $this->whereDate || $this->where || $this->whereContains) {
            throw new \Exception('Invalid operator. Where cannot be used with whereIn, whereContainsAny, whereNotIn, whereDate, another where or whereContains.', 1);
        }


        if ($this->isNestedArray($filters)) {
            $d = [];
            foreach ($filters as $filter) {
                if (count($filter) === 3) {
                    [$field, $operator, $value] = $filter;
                } else if (count(array_keys($filter)) === 2) {
                    [$field, $value] = $filter;
                    $operator = '=';
                }

                if (!in_array($operator, ["=", ">", "<", ">=", "<=", "!="])) {
                    throw new \Exception("Invalid operator: " . $operator);
                }

                $s = new FsFilters();
                $formattedDate = $s->convertToFirestoreFormat($value);
                $filter = $s->field($field, $operator, $formattedDate);
                $d[] = $filter;
            }

            $this->where = $s->compositeFilter('AND', $d);
        } else {
            if (count($filters) === 3) {
                [$field, $operator, $value] = $filters;
            } else if (count(array_keys($filters)) === 2) {
                [$field, $value] = $filters;
                $operator = '=';
            } else {
                throw new \Exception('Invalid value. Where value should be an array of [$field, $operator, $value] or [$field, $value] but got ' . json_encode($filters) . '.', 1);
            }

            if (!in_array($operator, ["=", ">", "<", ">=", "<=", "!="])) {
                throw new \Exception("Invalid operator: " . $operator);
            }

            $s = new FsFilters();
            $formattedDate = $s->convertToFirestoreFormat($value);
            $filter = $s->field($field, $operator, $formattedDate);
            $this->where = $filter;
        }

        return $this;
    }


    public function orWhere(array|string $filters, mixed $operator = null, mixed $value = null)
    {
        $or = is_array($filters) && is_bool($operator) ? $operator : false;
        if (is_string($filters)) {
            $filters = $this->normalizeWhereArguments($filters, $operator, $value, func_num_args());
        }

        if (!$this->isValidNestedArray($filters)) {
            throw new \Exception('Invalid value. OrWhere value should be an array or a nested array like [[], [], []] or [] and not empty but got type: ' . gettype($filters) . ' and value: ' . json_encode($filters) . '.', 1);
        }

        // Prevent conflicts with incompatible where clauses
        if ($this->orWhere) {
            throw new \Exception('Invalid operator. OrWhere cannot be used with whereIn, whereContainsAny, whereNotIn, whereDate, another where or whereContains.', 1);
        }


        if ($this->isNestedArray($filters)) {
            $d = [];
            foreach ($filters as $filter) {
                if (count($filter) === 3) {
                    [$field, $operator, $value] = $filter;
                } else if (count(array_keys($filter)) === 2) {
                    [$field, $value] = $filter;
                    $operator = '=';
                }

                if (!in_array($operator, ["=", ">", "<", ">=", "<=", "!="])) {
                    throw new \Exception("Invalid operator: " . $operator);
                }

                $s = new FsFilters();
                $formattedDate = $s->convertToFirestoreFormat($value);
                $filter = $s->field($field, $operator, $formattedDate);
                $d[] = $filter;
            }

            $this->orWhere = $s->compositeFilter($or ? 'OR' : 'AND', $d);
        } else {
            if (count($filters) === 3) {
                [$field, $operator, $value] = $filters;
            } else if (count(array_keys($filters)) === 2) {
                [$field, $value] = $filters;
                $operator = '=';
            } else {
                throw new \Exception('Invalid value. OrWhere value should be an array of [$field, $operator, $value] or [$field, $value] but got ' . json_encode($filters) . '.', 1);
            }

            if (!in_array($operator, ["=", ">", "<", ">=", "<=", "!="])) {
                throw new \Exception("Invalid operator: " . $operator);
            }

            $s = new FsFilters();
            $formattedDate = $s->convertToFirestoreFormat($value);
            $filter = $s->field($field, $operator, $formattedDate);

            $this->orWhere = $filter;
        }

        return $this;
    }

    private function getConditions()
    {
        $s = new FsFilters();
        $conditions = [
            $this->where,
            $this->whereContains,
            $this->whereContainsAny,
            $this->whereNotIn,
            $this->whereDate,
            $this->whereIn
        ];

        // Filter out empty conditions
        $conditions = array_filter($conditions);

        // If OR conditions exist, merge them with existing conditions
        if (!empty($this->orWhere)) {
            if (!empty($conditions)) {
                return $s->compositeFilter('OR', array_merge($conditions, [$this->orWhere]));
            }
            return $this->orWhere;
        }

        // Return the first non-empty condition, or an empty array if none exist
        return $conditions ? reset($conditions) : [];
    }

    private function normalizeWhereArguments(string $field, mixed $operator, mixed $value, int $argumentCount): array
    {
        if (trim($field) === '') {
            throw new \InvalidArgumentException('The Firestore filter field cannot be empty.');
        }

        return match ($argumentCount) {
            2 => [$field, '=', $operator],
            3 => [$field, $operator, $value],
            default => throw new \InvalidArgumentException(
                'A string filter expects where($field, $value) or where($field, $operator, $value).'
            ),
        };
    }
}
