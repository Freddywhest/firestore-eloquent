<?php

namespace Roddy\FirestoreEloquent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreValue;

final class FirestoreValueTest extends TestCase
{
    public function test_date_looking_strings_remain_strings_without_an_explicit_type(): void
    {
        self::assertSame(['stringValue' => '2026-10-25'], FirestoreValue::encode('2026-10-25'));
    }

    public function test_empty_arrays_round_trip_as_arrays(): void
    {
        $encoded = FirestoreValue::encode([]);

        self::assertSame(['arrayValue' => ['values' => []]], $encoded);
        self::assertSame([], FirestoreValue::decode($encoded));
    }

    public function test_nested_values_round_trip(): void
    {
        $value = ['active' => true, 'score' => 0, 'tags' => ['one', 'two']];

        self::assertSame($value, FirestoreValue::decode(FirestoreValue::encode($value)));
    }
}
