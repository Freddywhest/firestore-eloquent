<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Illuminate\Pagination\CursorPaginator;

/**
 * Laravel's native cursor paginator with Firestore-aware cursor values.
 */
final class FirestoreCursorPaginator extends CursorPaginator
{
    /**
     * @param array<int, array{field: array{fieldPath: string}, direction: string}> $orders
     * @param array<string, string> $fieldTypes
     */
    public function __construct(
        mixed $items,
        int $perPage,
        mixed $cursor,
        array $options,
        private readonly array $orders,
        private readonly array $fieldTypes = []
    ) {
        parent::__construct($items, $perPage, $cursor, $options);
    }

    /** Return values in the exact format accepted by Firestore's startAt cursor. */
    public function getParametersForItem($item): array
    {
        if (!$item instanceof DataHelperController) {
            throw new \InvalidArgumentException('Firestore cursor items must be Firestore model results.');
        }

        $values = [];
        foreach ($this->orders as $order) {
            $field = $order['field']['fieldPath'];

            if ($field === '__name__') {
                $documentName = $item->getDocumentName();
                if (!$documentName) {
                    throw new \RuntimeException('Cannot create a cursor without the Firestore document name.');
                }

                $values[] = ['referenceValue' => $documentName];
                continue;
            }

            $value = $item->getAttribute($field);
            if ($value === null) {
                throw new \RuntimeException("Cannot create a cursor because [{$field}] is missing or null.");
            }

            $values[] = FirestoreValue::encode($value, $this->fieldTypes[$field] ?? null);
        }

        return ['values' => $values];
    }

    public function nextCursorEncoded(): ?string
    {
        return $this->nextCursor()?->encode();
    }

    public function previousCursorEncoded(): ?string
    {
        return $this->previousCursor()?->encode();
    }
}
