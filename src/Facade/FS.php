<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Facade;

use Illuminate\Support\Facades\Facade;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FClient;
use Roddy\FirestoreEloquent\Firestore\FirestoreTransaction;

/**
 * Firestore query-builder facade.
 *
 * @method static FClient collection(string $collection, string $primaryKey = 'id')
 * @method static FClient table(string $collection, string $primaryKey = 'id')
 * @method static FirestoreTransaction beginTransaction()
 * @method static array commit()
 * @method static void rollback()
 * @method static bool inTransaction()
 * @method static mixed transaction(callable(FirestoreTransaction): mixed $callback, int $attempts = 5)
 *
 * @see \Roddy\FirestoreEloquent\Firestore\FirestoreManager
 */
final class FS extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'firestore';
    }
}
