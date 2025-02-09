<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

trait AggregationQueries
{
    public function count($from_paginate = false)
    {
        $query = $this->getQuery(isAggregation: true);

        if ($from_paginate) {
            if (isset($query['structuredQuery']['offset'])) {
                unset($query['structuredQuery']['offset']);
            }
            if (isset($query['structuredQuery']['limit'])) {
                unset($query['structuredQuery']['limit']);
            }
        }

        $data = ["structuredAggregationQuery" => ["aggregations" => [["alias" => "count", "count" => (object) []]], "structuredQuery" => $query["structuredQuery"]]];
        $result = $this->postRequest(':runAggregationQuery', $data, false, false)?->first() ?? null;
        $final =  isset($result['result']) ? $result['result']['aggregateFields']['count']['integerValue'] ?? 0 : 0;
        return (int) $final;
    }

    public function sum($field)
    {
        $query = $this->getQuery(isAggregation: true);
        $data = ["structuredAggregationQuery" => ["aggregations" => [["alias" => "sum", "sum" => (object) ["field" => (object) ["fieldPath" => $field]]]], "structuredQuery" => $query["structuredQuery"]]];
        $result = $this->postRequest(':runAggregationQuery', $data, false, false)?->first() ?? null;
        $final =  isset($result['result']) ? $result['result']['aggregateFields']['sum']['integerValue'] ?? 0 : 0;
        return (int) $final;
    }

    public function avg($field)
    {
        $query = $this->getQuery(isAggregation: true);
        $data = ["structuredAggregationQuery" => ["aggregations" => [["alias" => "avg", "avg" => (object) ["field" => (object) ["fieldPath" => $field]]]], "structuredQuery" => $query["structuredQuery"]]];
        $result = $this->postRequest(':runAggregationQuery', $data, false, false)?->first() ?? null;
        $final =  isset($result['result']) ? $result['result']['aggregateFields']['avg']['doubleValue'] ?? 0 : 0;
        return (float) $final;
    }
}
