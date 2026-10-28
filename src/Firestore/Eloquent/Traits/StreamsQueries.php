<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Illuminate\Pagination\Cursor;
use Illuminate\Support\LazyCollection;

trait StreamsQueries
{
    /**
     * Process a query in cursor-backed pages without loading the full result.
     */
    public function chunk(int $count, callable $callback): bool
    {
        if ($count < 1 || $count > 1000) {
            throw new \InvalidArgumentException('Chunk size must be between 1 and 1000.');
        }

        $cursor = null;
        $page = 1;

        do {
            $paginator = (clone $this)->cursorPaginate(
                perPage: $count,
                cursor: $cursor
            );

            if ($paginator->isEmpty()) {
                break;
            }

            if ($callback($paginator->getCollection(), $page) === false) {
                return false;
            }

            $cursor = $paginator->nextCursor();
            $page++;
        } while ($cursor instanceof Cursor);

        return true;
    }

    /**
     * Lazily yield Firestore results using cursor pagination.
     */
    public function lazy(int $chunkSize = 100): LazyCollection
    {
        if ($chunkSize < 1 || $chunkSize > 1000) {
            throw new \InvalidArgumentException('Chunk size must be between 1 and 1000.');
        }

        $query = clone $this;

        return LazyCollection::make(static function () use ($query, $chunkSize) {
            $cursor = null;

            do {
                $paginator = (clone $query)->cursorPaginate(
                    perPage: $chunkSize,
                    cursor: $cursor
                );

                foreach ($paginator->items() as $item) {
                    yield $item;
                }

                $cursor = $paginator->nextCursor();
            } while ($cursor instanceof Cursor);
        });
    }
}
