<?php

namespace Roddy\FirestoreEloquent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FsFilters;

final class FsFiltersTest extends TestCase
{
    public function test_in_filter_uses_a_firestore_array_value(): void
    {
        $filters = new FsFilters();
        $filter = $filters->fieldForArray(
            'status',
            'in',
            $filters->convertToFirestoreFormat(['open', 'closed'])
        );

        self::assertSame('IN', $filter['fieldFilter']['op']);
        self::assertSame(
            [['stringValue' => 'open'], ['stringValue' => 'closed']],
            $filter['fieldFilter']['value']['arrayValue']['values']
        );
    }

    public function test_not_in_operator_maps_to_the_firestore_enum(): void
    {
        $filters = new FsFilters();
        $filter = $filters->fieldForArray('status', 'not-in', FirestoreValueFixture::array(['closed']));

        self::assertSame('NOT_IN', $filter['fieldFilter']['op']);
    }

    public function test_null_comparison_uses_a_unary_filter(): void
    {
        $filters = new FsFilters();

        self::assertSame(
            'IS_NULL',
            $filters->field('deleted_at', '=', ['nullValue' => null])['unaryFilter']['op']
        );
    }
}

final class FirestoreValueFixture
{
    public static function array(array $values): array
    {
        return ['arrayValue' => ['values' => array_map(fn ($value) => ['stringValue' => $value], $values)]];
    }
}
