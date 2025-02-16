<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Roddy\FirestoreEloquent\Firestore\Eloquent\Traits\{FHttpClient, FirstQueries, DataParser, GetAndAllQueries, WhereQueries, OtherQueries, AggregationQueries, PaginateQueries, CreateMethod};
use Illuminate\Support\Str;


final class FClient
{
    use DataParser, FHttpClient, FirstQueries, GetAndAllQueries, WhereQueries, OtherQueries, AggregationQueries, PaginateQueries, CreateMethod;

    private $FIREBASE_PROJECT_ID;
    private $where = [];
    private $orWhere = [], $select = [], $orderBy = [], $limit = null, $offset = null;
    private $structuredQuery = [
        "structuredQuery" => []
    ];

    public function __construct(
        protected $collection,
        protected $primaryKey,
        protected $fillable,
        protected $required,
        protected $default,
        protected $fieldTypes,
        protected $model,
        protected $hidden,
        protected $modelClass
    ) {
        $this->FIREBASE_PROJECT_ID = env('FIREBASE_PROJECT_ID');
        if (!$this->FIREBASE_PROJECT_ID) {
            throw new \Exception("FIREBASE_PROJECT_ID not set in .env file.");
        }
    }

    public function __call($name, $arguments)
    {
        $method = Str::camel('scope ' . $name);
        if (method_exists($this->modelClass, $method)) {
            // scope method with arguments ($query, $arguments)
            $class = new $this->modelClass;
            $query = new FClient(
                collection: $class->collection,
                primaryKey: $class->primaryKey,
                fillable: $class->fillable,
                required: $class->required,
                default: $class->default,
                fieldTypes: $class->fieldTypes,
                model: $class->model,
                hidden: $class->hidden,
                modelClass: $class->modelClass
            );
            return $class->{$method}($query, ...$arguments);
        }
    }

    public function getQuery(?int $limit = null, bool $isAggregation = false)
    {
        $whereQueries = $this->getConditions();

        if ($this->select) {
            $this->structuredQuery["structuredQuery"]["select"]["fields"] = $this->select;
        }

        if ($this->orderBy || $this->limit || $this->offset || $this->select || $limit || $isAggregation || !empty($whereQueries)) {
            $array = explode("/", $this->collection);
            if (count($array) == 1) {
                $doc = $array[0];
            } else {
                $doc = end($array);
            }

            $this->structuredQuery["structuredQuery"]["from"] = [
                ["collectionId" => $doc]
            ];
        }

        if (!empty($whereQueries)) {
            $this->structuredQuery["structuredQuery"]["where"] = $whereQueries;
        }

        if ($this->orderBy) {
            $this->structuredQuery["structuredQuery"]["orderBy"] = [$this->orderBy];
        }

        if ($this->offset) {
            $this->structuredQuery["structuredQuery"]["offset"] = $this->offset;
        }

        if ($this->limit || $limit) {
            $this->structuredQuery["structuredQuery"]["limit"] = $this->limit ?? $limit;
        }

        return $this->structuredQuery;
    }
}
