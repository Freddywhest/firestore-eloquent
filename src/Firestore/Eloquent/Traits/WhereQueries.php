<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FsFilters;
use Carbon\Carbon;

trait WhereQueries
{
    public $whereIn, $whereContains, $whereContainsAny, $whereNotIn, $whereDate;

    public function whereIn($field, array $value)
    {
        if (!is_array($value)) {
            return throw new \Exception('Invalid value. WhereIn value should be an array but got ' . gettype($value) . '.', 1);
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

    public function whereContains($field, string|int|float $value)
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return throw new \Exception('Invalid value. WhereContains value should be a string, integer or float but got ' . gettype($value) . '.', 1);
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
        if (!is_array($value)) {
            return throw new \Exception('Invalid value. WhereContainsAny value should be an array but got ' . gettype($value) . '.', 1);
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
        if (!is_array($value)) {
            return throw new \Exception('Invalid value. WhereNotIn value should be an array but got ' . gettype($value) . '.', 1);
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

        foreach ($array as $item) {
            if (empty($item)) {
                return false;
            }
            if (is_array($item)) {
                foreach ($item as $subItem) {
                    if (empty($subItem)) {
                        return false;
                    }
                    if (is_array($subItem)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function whereDate(array $filters, bool $or = false)
    {
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
                if (count(array_keys($filter)) === 3) {
                    [$field, $operator, $value] = $filter;
                } else if (count(array_keys($filter)) === 2) {
                    [$field, $value] = $filter;
                    $operator = '=';
                }

                if (!in_array($operator, ["=", ">", "<", ">=", "<=", "!="])) {
                    throw new \Exception("Invalid operator: " . $operator);
                }

                if (!strtotime($value)) {
                    throw new \Exception('Invalid value. WhereDate value should be a valid date but got ' . $value . '.', 1);
                }

                $s = new FsFilters();
                $formattedDate = $s->convertToFirestoreFormat($value);
                $filter = $s->field($field, $operator, $formattedDate);
                $d[] = $filter;
            }

            $this->whereDate = $s->compositeFilter($or ? 'OR' : 'AND', $d);
        } else {
            if (count(array_keys($filters)) === 3) {
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

            if (!strtotime($value)) {
                throw new \Exception('Invalid value. WhereDate value should be a valid date but got ' . $value . '.', 1);
            }

            $s = new FsFilters();
            $formattedDate = $s->convertToFirestoreFormat($value);
            $filter = $s->field($field, $operator, $formattedDate);

            $this->whereDate = $filter;
        }

        return $this;
    }


    public function where(array $filters)
    {
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
                if (count(array_keys($filter)) === 3) {
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
            if (count(array_keys($filters)) === 3) {
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


    public function orWhere(array $filters, bool $or = false)
    {
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
                if (count(array_keys($filter)) === 3) {
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
            if (count(array_keys($filters)) === 3) {
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
}
