<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers\AllTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers\AndWhereTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers\DeleteManyTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers\FirstOrFailTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers\FirstTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers\GetTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers\OrWhereTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers\WhereTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\CreateTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\ToArrayHelper;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\PaginateTrait as TraitsPaginateTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\UpdateManyTrait as TraitsUpdateManyTrait;

class SubDocumentModel
{
    use FirstOrFailTrait;
    use FirstTrait,
        WhereTrait,
        OrWhereTrait,
        AndWhereTrait,
        TraitsPaginateTrait,
        DeleteManyTrait,
        GetTrait,
        CreateTrait,
        AllTrait;
    use TraitsUpdateManyTrait;

    private $subCollectionName, $row, $query, $model, $collection;
    private $direction;
    private $path;

    public function __construct($row, $subCollectionName, $model, $collection)
    {
        $this->subCollectionName = $subCollectionName;
        $this->row = $row;
        $this->model = $model;
        $this->collection = $collection;
    }

    public function firstOrFail()
    {
        if (!$this->query) {
            $query = $this->row->reference()->collection($this->subCollectionName);
        } else {
            $query = $this->query;
        }

        return $this->fFirstOrFail(
            path: $this->path,
            direction: $this->direction,
            query: $query,
            model: $this->model,
            collection: $this->subCollectionName
        );
    }

    public function first()
    {
        if (!$this->query) {
            $query = $this->row->reference()->collection($this->subCollectionName);
        } else {
            $query = $this->query;
        }

        return $this->fFirst(
            path: $this->path,
            direction: $this->direction,
            query: $query,
            model: $this->model,
            collection: $this->subCollectionName
        );
    }

    public function where(array $filter)
    {
        $this->query = $this->fWhere(
            filter: $filter,
            row: $this->row
        );

        return $this;
    }

    public function orderBy(string $path, ?string $direction = null)
    {
        if ($direction && !in_array(strtoupper($direction), ['DESC', 'ASC'])) {
            throw new \Exception('OrderBy direction should be either "DESC" or "ASC"', 1);
        }

        $this->path = $path;
        $this->direction = $direction;

        return $this;
    }

    public function orderByDesc(string $path)
    {
        $this->path = $path;
        $this->direction = 'DESC';

        return $this;
    }

    public function orderByAsc(string $path)
    {
        $this->path = $path;
        $this->direction = 'ASC';

        return $this;
    }

    public function andWhere(array $filters)
    {
        $this->query = $this->fAndWhere(
            filters: $filters,
            row: $this->row
        );
        return $this;
    }

    public function orWhere(array $filters)
    {
        $this->query = $this->fOrWhere(
            filters: $filters,
            row: $this->row
        );

        return $this;
    }

    public function all()
    {
        $query = $this->row->reference()->collection($this->subCollectionName);

        return $this->fAll(
            path: $this->path,
            direction: $this->direction,
            model: $this->model,
            query: $query,
            collection: $this->subCollectionName
        );
    }

    public function get()
    {
        if (!$this->query) {
            $query = $this->row->reference()->collection($this->subCollectionName);
        } else {
            $query = $this->query;
        }

        return $this->fget(
            path: $this->path,
            direction: $this->direction,
            model: $this->model,
            query: $query,
            collection: $this->subCollectionName
        );
    }

    public function paginate(int $limit, string $name = 'page', ?int $page = null): object
    {
        if (!$this->query) {
            $query = $this->row->reference()->collection($this->subCollectionName);
        } else {
            $query = $this->query;
        }

        return (object) $this->fPaginate(
            path: $this->path,
            direction: $this->direction,
            query: $query,
            model: $this->model,
            collection: $this->subCollectionName,
            name: $name,
            limit: $limit,
            page: $page,
            order: $this->order ? $this->order : 'ASC',
            field: $this->field,
            value: $this->value
        );
    }

    public function count()
    {
        if (!$this->query) {
            return $this->row->reference()->collection($this->subCollectionName)->count();
        }

        return $this->query->count();
    }

    public function deleteMany()
    {
        if (!$this->query) {
            $query = $this->row->reference()->collection($this->subCollectionName);
        } else {
            $query = $this->query;
        }

        $this->fDeleteMany(query: $query);
    }

    public  function create(array $data, array $fillable = [], array $required = [], array $default = [], array $fieldTypes = [], $primaryKey = 'id')
    {
        return $this->fCreate(
            data: $data,
            firestore: $this->row->reference()->collection($this->subCollectionName),
            fillable: $fillable,
            required: $required,
            default: $default,
            model: $this->model,
            primaryKey: $primaryKey,
            fieldTypes: $fieldTypes
        );
    }

    public function limit($number)
    {
        if (!$this->query) {
            if ($this->path) {
                if (!$this->direction) {
                    $query = $this->row->reference()->collection($this->subCollectionName)->orderBy($this->path)->limit($number);
                } else {
                    $query = $this->row->reference()->collection($this->subCollectionName)->orderBy($this->path, $this->direction)->limit($number);
                }
            } else {
                $query = $this->row->reference()->collection($this->subCollectionName)->limit($number);
            }
        } else {
            if ($this->path) {
                if (!$this->direction) {
                    $query = $this->query->orderBy($this->path)->limit($number);
                } else {
                    $query = $this->query->orderBy($this->path, $this->direction)->limit($number);
                }
            } else {
                $query = $this->query->limit($number);
            }
        }
        if ($number == 1) {
            return new ToArrayHelper(queryRaw: $query, model: $this->model, collection: $this->collection, single: "first");
        } else {
            return new ToArrayHelper(queryRaw: $query, model: $this->model, collection: $this->collection);
        }
    }
    public function like(string $field, int|string $value, string $order = 'asc')
    {
        $this->value = $value;
        $this->field = $field;
        $this->order = $order;

        return $this;
    }

    private $field;
    private $value;
    private $order;
}
