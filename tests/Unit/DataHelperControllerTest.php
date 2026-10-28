<?php

namespace Roddy\FirestoreEloquent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Roddy\FirestoreEloquent\Firestore\Eloquent\DataHelperController;

final class DataHelperControllerTest extends TestCase
{
    public function test_hidden_fields_are_serialization_only_and_updates_are_partial(): void
    {
        $patched = null;
        $model = new DataHelperController(
            data: ['id' => 'one', 'name' => 'Before', 'password' => 'secret'],
            convertToFirestoreFormat: fn (array $data) => ['fields' => $data],
            primaryKey: 'id',
            collection: 'users',
            patchRequest: function (array $data, string $id, array $fields) use (&$patched): void {
                $patched = compact('data', 'id', 'fields');
            },
            deleteRequest: fn () => null,
            modelClass: DataHelperModelFixture::class,
            fillable: ['name', 'password'],
            hidden: ['password']
        );

        self::assertSame('secret', $model->password);
        self::assertArrayNotHasKey('password', $model->toArray());

        $model->update(['name' => 'After']);

        self::assertSame('one', $patched['id']);
        self::assertSame(['name'], $patched['fields']);
        self::assertSame(['fields' => ['name' => 'After']], $patched['data']);
    }

    public function test_update_rejects_non_fillable_fields(): void
    {
        $model = new DataHelperController(
            data: ['id' => 'one'],
            convertToFirestoreFormat: fn (array $data) => ['fields' => $data],
            primaryKey: 'id',
            collection: 'users',
            patchRequest: fn () => null,
            deleteRequest: fn () => null,
            modelClass: DataHelperModelFixture::class,
            fillable: ['name']
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Fields are not fillable: role');
        $model->update(['role' => 'admin']);
    }

    public function test_nested_document_values_can_be_read_with_dot_notation(): void
    {
        $model = new DataHelperController(
            data: ['id' => 'one', 'profile' => ['address' => ['city' => 'Accra']]],
            convertToFirestoreFormat: fn (array $data) => ['fields' => $data],
            primaryKey: 'id',
            collection: 'users',
            patchRequest: fn () => null,
            deleteRequest: fn () => null,
            modelClass: DataHelperModelFixture::class
        );

        self::assertSame('Accra', $model->getSubDocument('profile.address.city'));
        self::assertSame('fallback', $model->getSubDocument('profile.missing', 'fallback'));
    }
}

final class DataHelperModelFixture
{
}
