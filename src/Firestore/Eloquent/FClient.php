<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Roddy\FirestoreEloquent\Firestore\Eloquent\Traits\{FHttpClient, FirstQueries, DataParser, GetAndAllQueries, WhereQueries, OtherQueries, AggregationQueries, PaginateQueries, CreateMethod, UpdateMethod, MutationQueries, TransactionQueries, EagerLoadsRelations, StreamsQueries};
use Illuminate\Support\Str;
use Roddy\FirestoreEloquent\Firestore\FirestoreTransaction;


final class FClient
{
    use DataParser, FHttpClient, FirstQueries, GetAndAllQueries, WhereQueries, OtherQueries, AggregationQueries, PaginateQueries, CreateMethod, UpdateMethod, MutationQueries, TransactionQueries, EagerLoadsRelations, StreamsQueries;

    private $FIREBASE_PROJECT_ID;
    private $FIREBASE_DATABASE_ID;
    private $where = [];
    private $orWhere = [], $select = [], $orderBy = [], $limit = null, $offset = null, $startAfter = null;
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
        protected $modelClass,
        protected bool $unrestrictedWrites = false,
        protected ?string $transactionId = null,
        protected ?FirestoreTransaction $transactionContext = null
    ) {
        $project = config('firebase.default', 'app');
        $this->FIREBASE_PROJECT_ID = config("firebase.projects.{$project}.database.project_id");
        $this->FIREBASE_DATABASE_ID = config("firebase.projects.{$project}.database.database_id", '(default)');
        if (!$this->FIREBASE_PROJECT_ID) {
            throw new \Exception("FIREBASE_PROJECT_ID not set in .env file.");
        }
    }

    public function withinTransaction(FirestoreTransaction $transaction): static
    {
        $query = clone $this;
        $query->transactionId = $transaction->id();
        $query->transactionContext = $transaction;

        return $query;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function documentNameFor(string|int $id): string
    {
        return $this->firestoreDocumentName($id);
    }

    private function assertTransactionReadAllowed(): void
    {
        if ($this->transactionContext !== null) {
            $this->transactionContext->assertCanRead();
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
                modelClass: $class->modelClass,
                unrestrictedWrites: false
            );
            return $class->{$method}($query, ...$arguments);
        }

        throw new \BadMethodCallException("Method [{$name}] does not exist on Firestore query builder.");
    }

    public function getQuery(?int $limit = null, bool $isAggregation = false)
    {
        $whereQueries = $this->getConditions();

        if ($this->select) {
            $this->structuredQuery["structuredQuery"]["select"]["fields"] = $this->select;
        }

        if ($this->orderBy || $this->limit !== null || $this->offset !== null || $this->select || $limit !== null || $isAggregation || !empty($whereQueries)) {
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
            $this->structuredQuery["structuredQuery"]["orderBy"] = $this->orderBy;
        }

        if ($this->offset !== null) {
            $this->structuredQuery["structuredQuery"]["offset"] = $this->offset;
        }

        if ($this->startAfter !== null) {
            $this->structuredQuery["structuredQuery"]["startAt"] = [
                'values' => $this->startAfter,
                'before' => false,
            ];
        }

        if ($this->limit !== null || $limit !== null) {
            $this->structuredQuery["structuredQuery"]["limit"] = $limit === null
                ? $this->limit
                : min($this->limit ?? $limit, $limit);
        }

        return $this->structuredQuery;
    }
}
