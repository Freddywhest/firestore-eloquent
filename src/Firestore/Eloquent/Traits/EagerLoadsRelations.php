<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Roddy\FirestoreEloquent\Firestore\Eloquent\DataHelperController;

trait EagerLoadsRelations
{
    /** @var array<string, array{constraint: callable|null, nested: string[]}> */
    private array $eagerLoads = [];

    public function with(string|array $relations): static
    {
        $relations = is_array($relations) ? $relations : func_get_args();
        foreach ($relations as $key => $value) {
            $relation = is_string($key) ? $key : $value;
            $constraint = is_string($key) ? $value : null;

            if (!is_string($relation) || trim($relation) === '') {
                throw new InvalidArgumentException('Relationship names must be non-empty strings.');
            }
            if ($constraint !== null && !is_callable($constraint)) {
                throw new InvalidArgumentException("Relationship constraint for [{$relation}] must be callable.");
            }

            [$root, $nested] = array_pad(explode('.', $relation, 2), 2, null);
            $this->eagerLoads[$root] ??= ['constraint' => null, 'nested' => []];

            if ($nested !== null) {
                $this->eagerLoads[$root]['nested'][] = $nested;
            } elseif ($constraint !== null) {
                $this->eagerLoads[$root]['constraint'] = $constraint;
            }

            $this->eagerLoads[$root]['nested'] = array_values(array_unique($this->eagerLoads[$root]['nested']));
        }

        return $this;
    }

    private function eagerLoadRelations(array $models): array
    {
        if ($models === [] || $this->eagerLoads === []) {
            return $models;
        }

        $model = new $this->modelClass();

        foreach ($this->eagerLoads as $relationName => $options) {
            if (!method_exists($model, $relationName)) {
                throw new InvalidArgumentException("Relationship [{$relationName}] does not exist on [{$this->modelClass}].");
            }

            $relation = $model->{$relationName}();
            if (!is_array($relation) || !isset($relation['relation'], $relation['related'], $relation['foreignKey'], $relation['localKey'])) {
                throw new InvalidArgumentException("Method [{$relationName}] is not a Firestore relationship.");
            }

            $localValues = [];
            foreach ($models as $item) {
                if ($item instanceof DataHelperController) {
                    $value = $item->getAttribute($relation['localKey']);
                    if ($value !== null) {
                        $localValues[$this->relationKey($value)] = $value;
                    }
                }
            }

            $related = [];
            foreach (array_chunk(array_values($localValues), 30) as $chunk) {
                $relatedQuery = $relation['related']::whereIn($relation['foreignKey'], $chunk);
                if ($options['nested'] !== []) {
                    $relatedQuery->with($options['nested']);
                }
                if ($options['constraint'] !== null) {
                    $constrained = ($options['constraint'])($relatedQuery);
                    if ($constrained !== null) {
                        $relatedQuery = $constrained;
                    }
                }

                $related = array_merge(
                    $related,
                    $relatedQuery->get()->all()
                );
            }

            $grouped = [];
            foreach ($related as $relatedItem) {
                if (!$relatedItem instanceof DataHelperController) {
                    continue;
                }
                $key = $this->relationKey($relatedItem->getAttribute($relation['foreignKey']));
                $grouped[$key][] = $relatedItem;
            }

            foreach ($models as $item) {
                if (!$item instanceof DataHelperController) {
                    continue;
                }
                $matches = $grouped[$this->relationKey($item->getAttribute($relation['localKey']))] ?? [];
                $item->setRelation(
                    $relationName,
                    $relation['relation'] === 'hasMany' ? new Collection($matches) : ($matches[0] ?? null)
                );
            }
        }

        return $models;
    }

    private function relationKey(mixed $value): string
    {
        return get_debug_type($value) . ':' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
