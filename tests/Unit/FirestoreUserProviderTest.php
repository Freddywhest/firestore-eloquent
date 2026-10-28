<?php

namespace Roddy\FirestoreEloquent\Tests\Unit;

use Illuminate\Contracts\Hashing\Hasher;
use PHPUnit\Framework\TestCase;
use Roddy\FirestoreEloquent\Auth\FirestoreUserProvider;
use Roddy\FirestoreEloquent\Firestore\Eloquent\DataHelperController;

final class FirestoreUserProviderTest extends TestCase
{
    public function test_it_retrieves_and_validates_a_firestore_user(): void
    {
        FirestoreAuthModelFixture::$user = $this->user();
        $provider = new FirestoreUserProvider(new PlainTextHasherFixture(), FirestoreAuthModelFixture::class);

        $user = $provider->retrieveByCredentials(['email' => 'user@example.com', 'password' => 'secret']);

        self::assertSame('user-1', $user?->getAuthIdentifier());
        self::assertTrue($provider->validateCredentials($user, ['password' => 'secret']));
        self::assertFalse($provider->validateCredentials($user, ['password' => 'wrong']));
        self::assertSame(['email', '=', 'user@example.com'], FirestoreAuthModelFixture::$lastFilter);
    }

    public function test_it_persists_and_retrieves_remember_tokens(): void
    {
        $patched = null;
        FirestoreAuthModelFixture::$user = $this->user(function ($data, $id, $fields) use (&$patched): void {
            $patched = compact('data', 'id', 'fields');
        });
        $provider = new FirestoreUserProvider(new PlainTextHasherFixture(), FirestoreAuthModelFixture::class);

        $provider->updateRememberToken(FirestoreAuthModelFixture::$user, 'remember-me');

        self::assertSame('remember-me', FirestoreAuthModelFixture::$user->getRememberToken());
        self::assertSame('user-1', $patched['id']);
        self::assertSame(['remember_token'], $patched['fields']);
        self::assertSame(FirestoreAuthModelFixture::$user, $provider->retrieveByToken('user-1', 'remember-me'));
        self::assertNull($provider->retrieveByToken('user-1', 'wrong'));
    }

    private function user(?callable $patch = null): DataHelperController
    {
        return new DataHelperController(
            data: [
                'id' => 'user-1',
                'email' => 'user@example.com',
                'password' => 'secret',
            ],
            convertToFirestoreFormat: fn (array $data) => ['fields' => $data],
            primaryKey: 'id',
            collection: 'users',
            patchRequest: $patch ?? fn () => null,
            deleteRequest: fn () => null,
            modelClass: FirestoreAuthModelFixture::class,
            fillable: ['email', 'password']
        );
    }
}

final class FirestoreAuthModelFixture
{
    public string $primaryKey = 'id';
    public static ?DataHelperController $user = null;
    public static array $lastFilter = [];

    public static function find(mixed $id): ?DataHelperController
    {
        return self::$user?->getAuthIdentifier() === $id ? self::$user : null;
    }

    public static function where(array $filter): object
    {
        self::$lastFilter = $filter;

        return new class {
            public function first(): ?DataHelperController
            {
                return FirestoreAuthModelFixture::$user;
            }
        };
    }
}

final class PlainTextHasherFixture implements Hasher
{
    public function info($hashedValue): array
    {
        return [];
    }

    public function make($value, array $options = []): string
    {
        return (string) $value;
    }

    public function check($value, $hashedValue, array $options = []): bool
    {
        return hash_equals((string) $hashedValue, (string) $value);
    }

    public function needsRehash($hashedValue, array $options = []): bool
    {
        return false;
    }
}
