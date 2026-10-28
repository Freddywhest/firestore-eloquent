<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Roddy\FirestoreEloquent\Firestore\Eloquent\DataHelperController;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FClient;
use Roddy\FirestoreEloquent\Firestore\FirestoreManager;

/** Concrete static methods for IDE discovery and type-safe model queries. */
trait HasStaticQueries
{
    public static function query(): FClient
    {
        $model = new static();

        $client = new FClient(
            collection: $model->collection,
            model: $model->model,
            primaryKey: $model->primaryKey,
            fillable: $model->fillable,
            required: $model->required,
            default: $model->default,
            fieldTypes: $model->fieldTypes,
            hidden: $model->hidden,
            modelClass: static::class
        );

        $container = Container::getInstance();
        if ($container->bound('firestore')) {
            /** @var FirestoreManager $manager */
            $manager = $container->make('firestore');
            if ($transaction = $manager->currentTransaction()) {
                return $client->withinTransaction($transaction);
            }
        }

        return $client;
    }

    public static function where(array $filters): FClient
    {
        return static::query()->where($filters);
    }

    public static function orWhere(array $filters, bool $or = false): FClient
    {
        return static::query()->orWhere($filters, $or);
    }

    public static function whereIn(string $field, array $values): FClient
    {
        return static::query()->whereIn($field, $values);
    }

    public static function whereNotIn(string $field, array $values): FClient
    {
        return static::query()->whereNotIn($field, $values);
    }

    public static function whereContains(string $field, mixed $value): FClient
    {
        return static::query()->whereContains($field, $value);
    }

    public static function whereContainsAny(string $field, array $values): FClient
    {
        return static::query()->whereContainsAny($field, $values);
    }

    public static function whereDate(array $filters, bool $or = false): FClient
    {
        return static::query()->whereDate($filters, $or);
    }

    public static function orderBy(string $field, string $direction = 'asc'): FClient
    {
        return static::query()->orderBy($field, $direction);
    }

    public static function orderByAsc(string $field): FClient
    {
        return static::query()->orderByAsc($field);
    }

    public static function orderByDesc(string $field): FClient
    {
        return static::query()->orderByDesc($field);
    }

    public static function limit(int $limit): FClient
    {
        return static::query()->limit($limit);
    }

    public static function offset(int $offset): FClient
    {
        return static::query()->offset($offset);
    }

    public static function select(array $fields): FClient
    {
        return static::query()->select($fields);
    }

    public static function with(string|array $relations): FClient
    {
        return static::query()->with($relations);
    }

    /** @return Collection<int, DataHelperController> */
    public static function all(): Collection
    {
        return static::query()->all();
    }

    /** @return Collection<int, DataHelperController> */
    public static function get(): Collection
    {
        return static::query()->get();
    }

    public static function first(): ?DataHelperController
    {
        return static::query()->first();
    }

    public static function firstOrFail(): DataHelperController
    {
        return static::query()->firstOrFail();
    }

    public static function find(string|int $id): ?DataHelperController
    {
        return static::query()->find($id);
    }

    public static function findOrFail(string|int $id): DataHelperController
    {
        return static::query()->findOrFail($id);
    }

    public static function paginate(
        mixed $perPage = 10,
        mixed $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        return static::query()->paginate($perPage, $columns, $pageName, $page);
    }

    public static function simplePaginate(
        mixed $perPage = 15,
        mixed $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): Paginator {
        return static::query()->simplePaginate($perPage, $columns, $pageName, $page);
    }

    public static function cursorPaginate(
        mixed $perPage = 15,
        mixed $columns = ['*'],
        string $cursorName = 'cursor',
        Cursor|string|null $cursor = null
    ): CursorPaginator {
        return static::query()->cursorPaginate($perPage, $columns, $cursorName, $cursor);
    }

    /** @return LazyCollection<int, DataHelperController> */
    public static function lazy(int $chunkSize = 100): LazyCollection
    {
        return static::query()->lazy($chunkSize);
    }

    public static function chunk(int $count, callable $callback): bool
    {
        return static::query()->chunk($count, $callback);
    }

    public static function count(): int
    {
        return static::query()->count();
    }

    public static function sum(string $field): int|float
    {
        return static::query()->sum($field);
    }

    public static function avg(string $field): float
    {
        return static::query()->avg($field);
    }

    /** @return array<string, mixed> */
    public static function create(array $data): array
    {
        return static::query()->create($data);
    }

    public static function updateMany(array $documents): int
    {
        return static::query()->updateMany($documents);
    }
}
