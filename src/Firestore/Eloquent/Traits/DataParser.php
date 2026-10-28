<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Roddy\FirestoreEloquent\Firestore\Eloquent\DataHelperController;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreValue;

trait DataParser
{
    private function parseFirestoreJson($json)
    {
        $parseFields = function ($fields) {
            $parsed = [];
            foreach ($fields as $key => $value) {
                $parsed[$key] = FirestoreValue::decode($value);
            }
            return $parsed;
        };

        $data = $json;
        $result = [];

        $convertFunction  = function ($data) {
            return $this->convertToFirestoreFormat($data);
        };
        $patchRequestFuntion = function ($data, $id, array $fieldPaths) {
            if ($this->transactionContext !== null) {
                $this->transactionContext->queueWrite([
                    'update' => array_merge(
                        ['name' => $this->firestoreDocumentName($id)],
                        $data
                    ),
                    'updateMask' => [
                        'fieldPaths' => array_map(
                            fn (string $field): string => $this->firestoreFieldPath($field),
                            $fieldPaths
                        ),
                    ],
                    'currentDocument' => ['exists' => true],
                ]);

                return null;
            }
            return $this->patchRequest($this->collection, $data, true, false, $id, $fieldPaths);
        };

        $deleteRequestFuntion = function ($id) {
            if ($this->transactionContext !== null) {
                $this->transactionContext->queueWrite([
                    'delete' => $this->firestoreDocumentName($id),
                ]);

                return null;
            }
            return $this->deleteRequest($this->collection, true, false, $id);
        };
        if (isset($data['documents'])) {
            foreach ($data['documents'] as $doc) {
                $result[] = $this->newDataHelper($doc, $parseFields, $convertFunction, $patchRequestFuntion, $deleteRequestFuntion);
            }
        } elseif (is_array($data)) {
            foreach ($data as $doc) {
                if (!isset($doc['document'])) {
                    continue;
                }
                $result[] = $this->newDataHelper($doc['document'], $parseFields, $convertFunction, $patchRequestFuntion, $deleteRequestFuntion);
            }
        }

        return $result;
    }

    private function newDataHelper(array $document, callable $parseFields, callable $convertFunction, callable $patchRequestFunction, callable $deleteRequestFunction): DataHelperController
    {
        $data = $parseFields($document['fields'] ?? []);
        $documentName = $document['name'] ?? null;

        if (!array_key_exists($this->primaryKey, $data) && $documentName) {
            $data[$this->primaryKey] = rawurldecode(basename($documentName));
        }

        return new DataHelperController(
            data: $data,
            convertToFirestoreFormat: $convertFunction,
            primaryKey: $this->primaryKey,
            collection: $this->collection,
            patchRequest: $patchRequestFunction,
            deleteRequest: $deleteRequestFunction,
            modelClass: $this->modelClass,
            fillable: $this->fillable,
            fieldTypes: $this->fieldTypes,
            hidden: $this->hidden,
            documentName: $documentName,
            unrestrictedWrites: $this->unrestrictedWrites
        );
    }
}
