<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Carbon\Carbon;
use DateTimeInterface;
use InvalidArgumentException;

final class FirestoreValue
{
    public static function encode(mixed $value, ?string $declaredType = null): array
    {
        $declaredType = $declaredType ? strtolower($declaredType) : null;

        if ($value instanceof DateTimeInterface || in_array($declaredType, ['date', 'datetime', 'timestamp'], true)) {
            try {
                return [
                    'timestampValue' => Carbon::parse($value)
                        ->utc()
                        ->format('Y-m-d\TH:i:s.u\Z'),
                ];
            } catch (\Throwable $exception) {
                throw new InvalidArgumentException('Invalid Firestore timestamp value.', 0, $exception);
            }
        }

        if (is_null($value)) {
            return ['nullValue' => null];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            // Firestore represents int64 values as strings in JSON.
            return ['integerValue' => (string) $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if (is_string($value)) {
            return ['stringValue' => $value];
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return [
                    'arrayValue' => [
                        'values' => array_map(fn (mixed $item) => self::encode($item), $value),
                    ],
                ];
            }

            $fields = [];
            foreach ($value as $key => $item) {
                if (!is_string($key) || $key === '') {
                    throw new InvalidArgumentException('Firestore map keys must be non-empty strings.');
                }
                $fields[$key] = self::encode($item);
            }

            return ['mapValue' => ['fields' => $fields]];
        }

        if (is_object($value)) {
            return self::encode(get_object_vars($value), 'map');
        }

        throw new InvalidArgumentException('Unsupported Firestore value type: ' . get_debug_type($value));
    }

    public static function decode(array $value): mixed
    {
        if (array_key_exists('nullValue', $value)) {
            return null;
        }
        if (array_key_exists('stringValue', $value)) {
            return $value['stringValue'];
        }
        if (array_key_exists('booleanValue', $value)) {
            return (bool) $value['booleanValue'];
        }
        if (array_key_exists('integerValue', $value)) {
            return (int) $value['integerValue'];
        }
        if (array_key_exists('doubleValue', $value)) {
            return (float) $value['doubleValue'];
        }
        if (array_key_exists('timestampValue', $value)) {
            return $value['timestampValue'];
        }
        if (array_key_exists('bytesValue', $value)) {
            return $value['bytesValue'];
        }
        if (array_key_exists('referenceValue', $value)) {
            return $value['referenceValue'];
        }
        if (array_key_exists('geoPointValue', $value)) {
            return $value['geoPointValue'];
        }
        if (array_key_exists('arrayValue', $value)) {
            return array_map(
                fn (array $item) => self::decode($item),
                $value['arrayValue']['values'] ?? []
            );
        }
        if (array_key_exists('mapValue', $value)) {
            $decoded = [];
            foreach ($value['mapValue']['fields'] ?? [] as $key => $item) {
                $decoded[$key] = self::decode($item);
            }
            return $decoded;
        }

        throw new InvalidArgumentException('Unsupported Firestore value payload.');
    }
}
