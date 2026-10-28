<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreCursorPaginator;

trait PaginateQueries
{
    /**
     * Laravel-compatible numbered pagination.
     *
     * A numeric second argument remains supported for the package's former
     * paginate($perPage, $page) signature.
     */
    public function paginate($perPage = 10, $columns = ['*'], $pageName = 'page', $page = null): LengthAwarePaginator
    {
        $perPage = $this->validatedPerPage($perPage);

        if (is_numeric($columns)) {
            $page = (int) $columns;
            $columns = ['*'];
        }

        $currentPage = $page ?? Paginator::resolveCurrentPage((string) $pageName);
        $currentPage = is_numeric($currentPage) && (int) $currentPage > 0 ? (int) $currentPage : 1;

        $query = clone $this;
        $query->applyPaginationColumns($columns);
        $query->limit($perPage)->offset(($currentPage - 1) * $perPage);

        $models = $query->postRequest(':runQuery', $query->getQuery(), false)?->all() ?? [];
        $models = $query->eagerLoadRelations($models);

        return new LengthAwarePaginator($models, (clone $this)->count(true), $perPage, $currentPage, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => (string) $pageName,
            'query' => Paginator::resolveQueryString() ?? [],
        ]);
    }

    /** Paginate without the extra Firestore aggregation request. */
    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null): Paginator
    {
        $perPage = $this->validatedPerPage($perPage);
        $currentPage = $page ?? Paginator::resolveCurrentPage((string) $pageName);
        $currentPage = is_numeric($currentPage) && (int) $currentPage > 0 ? (int) $currentPage : 1;

        $query = clone $this;
        $query->applyPaginationColumns($columns);
        $query->limit($perPage + 1)->offset(($currentPage - 1) * $perPage);
        $models = $query->postRequest(':runQuery', $query->getQuery(), false)?->all() ?? [];
        $models = $query->eagerLoadRelations($models);

        return new Paginator($models, $perPage, $currentPage, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => (string) $pageName,
            'query' => Paginator::resolveQueryString() ?? [],
        ]);
    }

    /** Laravel-compatible, bidirectional Firestore cursor pagination. */
    public function cursorPaginate(
        $perPage = 15,
        $columns = ['*'],
        $cursorName = 'cursor',
        $cursor = null
    ): CursorPaginator {
        $perPage = $this->validatedPerPage($perPage);

        // Compatibility with the short-lived cursorPaginate($perPage, $cursor)
        // API while keeping Laravel's normal method signature.
        if ($columns instanceof Cursor || is_string($columns)) {
            $cursor = $columns;
            $columns = ['*'];
        }

        if (is_string($cursor)) {
            $cursor = Cursor::fromEncoded($cursor);
            if (!$cursor) {
                throw new \InvalidArgumentException('Invalid Firestore cursor.');
            }
        } elseif ($cursor !== null && !$cursor instanceof Cursor) {
            throw new \InvalidArgumentException('Cursor must be an encoded string, a Laravel Cursor, or null.');
        }

        $cursor ??= CursorPaginator::resolveCurrentCursor((string) $cursorName);

        $query = clone $this;
        $query->applyPaginationColumns($columns);
        $query->ensureDeterministicCursorOrder();
        $originalOrders = $query->orderBy;

        if ($cursor) {
            $values = $cursor->parameter('values');
            if (!is_array($values) || count($values) !== count($originalOrders)) {
                throw new \InvalidArgumentException('Cursor does not match this query ordering.');
            }
            $query->startAfterValues($values);
        }

        if ($cursor?->pointsToPreviousItems()) {
            $query->orderBy = array_map(static function (array $order): array {
                $order['direction'] = ($order['direction'] ?? 'ASCENDING') === 'ASCENDING'
                    ? 'DESCENDING'
                    : 'ASCENDING';
                return $order;
            }, $query->orderBy);
        }

        $query->limit = $perPage + 1;
        $models = $query->postRequest(':runQuery', $query->getQuery(), false)?->all() ?? [];
        $models = $query->eagerLoadRelations($models);

        return new FirestoreCursorPaginator(
            items: $models,
            perPage: $perPage,
            cursor: $cursor,
            options: [
                'path' => Paginator::resolveCurrentPath(),
                'cursorName' => (string) $cursorName,
                'query' => Paginator::resolveQueryString() ?? [],
            ],
            orders: $originalOrders,
            fieldTypes: $this->fieldTypes
        );
    }

    private function validatedPerPage(mixed $perPage): int
    {
        if (!is_numeric($perPage) || (int) $perPage < 1 || (int) $perPage > 1000) {
            throw new \InvalidArgumentException('Per-page must be between 1 and 1000.');
        }

        return (int) $perPage;
    }

    private function applyPaginationColumns(mixed $columns): void
    {
        if (!is_array($columns)) {
            throw new \InvalidArgumentException('Pagination columns must be an array.');
        }

        if ($columns !== ['*'] && $columns !== []) {
            $this->select($columns);
        }
    }

    private function ensureDeterministicCursorOrder(): void
    {
        if ($this->orderBy === []) {
            $this->orderBy('__name__');
            return;
        }

        foreach ($this->orderBy as $order) {
            if (($order['field']['fieldPath'] ?? null) === '__name__') {
                return;
            }
        }

        $lastDirection = $this->orderBy[array_key_last($this->orderBy)]['direction'] ?? 'ASCENDING';
        $this->orderBy('__name__', $lastDirection === 'DESCENDING' ? 'desc' : 'asc');
    }
}
