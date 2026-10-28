<?php

namespace Roddy\FirestoreEloquent\Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FClient;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreCursorPaginator;

final class FClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        $container->instance('config', new Repository([
            'firebase' => [
                'default' => 'app',
                'emulator_host' => '127.0.0.1:8080',
                'projects' => [
                    'app' => [
                        'credentials' => null,
                        'database' => ['project_id' => 'test-project', 'database_id' => '(default)'],
                    ],
                ],
            ],
        ]));
        $container->instance('http', new Factory());
        Container::setInstance($container);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
    }

    public function test_array_filters_and_multiple_orders_generate_valid_query_shape(): void
    {
        $query = $this->client()
            ->whereIn('status', ['open', 'closed'])
            ->orderBy('created_at', 'desc')
            ->orderBy('__name__', 'desc')
            ->getQuery();

        self::assertSame('IN', $query['structuredQuery']['where']['fieldFilter']['op']);
        self::assertArrayHasKey('arrayValue', $query['structuredQuery']['where']['fieldFilter']['value']);
        self::assertCount(2, $query['structuredQuery']['orderBy']);
    }

    public function test_first_query_limit_overrides_a_larger_user_limit(): void
    {
        $query = $this->client()->limit(100)->getQuery(1);

        self::assertSame(1, $query['structuredQuery']['limit']);
    }

    public function test_cursor_pagination_fetches_one_extra_document_and_emits_a_cursor(): void
    {
        Http::fakeSequence()
            ->push([
                ['document' => $this->document('one')],
                ['document' => $this->document('two')],
            ])
            ->push([
                ['document' => $this->document('two')],
            ]);

        $firstPage = $this->client()->cursorPaginate(1);

        self::assertInstanceOf(FirestoreCursorPaginator::class, $firstPage);
        self::assertInstanceOf(\Illuminate\Pagination\CursorPaginator::class, $firstPage);
        self::assertCount(1, $firstPage);
        self::assertTrue($firstPage->hasMorePages());
        self::assertNotNull($firstPage->nextCursor());
        self::assertNotNull($firstPage->nextCursorEncoded());

        $secondPage = $this->client()->cursorPaginate(1, $firstPage->nextCursor());

        self::assertCount(1, $secondPage);
        self::assertFalse($secondPage->hasMorePages());

        Http::assertSent(function ($request) {
            $query = $request->data()['structuredQuery'];
            return ($query['limit'] ?? null) === 2
                && ($query['orderBy'][0]['field']['fieldPath'] ?? null) === '__name__';
        });
        Http::assertSent(function ($request) {
            return isset($request->data()['structuredQuery']['startAt']['values'][0]['referenceValue']);
        });
    }

    public function test_cursor_pagination_can_navigate_to_the_previous_page(): void
    {
        Http::fakeSequence()
            ->push([
                ['document' => $this->document('one')],
                ['document' => $this->document('two')],
            ])
            ->push([
                ['document' => $this->document('two')],
                ['document' => $this->document('three')],
            ])
            ->push([
                ['document' => $this->document('one')],
            ]);

        $first = $this->client()->cursorPaginate(1);
        $second = $this->client()->cursorPaginate(1, $first->nextCursor());
        $previous = $this->client()->cursorPaginate(1, $second->previousCursor());

        self::assertSame('one', $previous->items()[0]->id);
        self::assertNull($previous->previousCursor());
        self::assertNotNull($previous->nextCursor());

        Http::assertSent(function ($request) {
            $query = $request->data()['structuredQuery'];

            return ($query['orderBy'][0]['direction'] ?? null) === 'DESCENDING'
                && isset($query['startAt']['values'][0]['referenceValue']);
        });
    }

    public function test_simple_pagination_avoids_the_count_request(): void
    {
        Http::fake([
            '*' => Http::response([
                ['document' => $this->document('one')],
                ['document' => $this->document('two')],
            ]),
        ]);

        $page = $this->client()->simplePaginate(1, ['*'], 'page', 1);

        self::assertCount(1, $page);
        self::assertTrue($page->hasMorePages());
        Http::assertSentCount(1);
    }

    public function test_lazy_streams_results_across_cursor_pages(): void
    {
        Http::fakeSequence()
            ->push([
                ['document' => $this->document('one')],
                ['document' => $this->document('two')],
                ['document' => $this->document('three')],
            ])
            ->push([
                ['document' => $this->document('three')],
            ]);

        $ids = $this->client()->lazy(2)->map(fn ($model) => $model->id)->all();

        self::assertSame(['one', 'two', 'three'], array_values($ids));
        Http::assertSentCount(2);
    }

    public function test_chunk_can_stop_without_fetching_another_page(): void
    {
        Http::fake([
            '*' => Http::response([
                ['document' => $this->document('one')],
                ['document' => $this->document('two')],
            ]),
        ]);

        $completed = $this->client()->chunk(1, fn () => false);

        self::assertFalse($completed);
        Http::assertSentCount(1);
    }

    public function test_update_many_uses_one_atomic_commit_instead_of_one_request_per_document(): void
    {
        Http::fake(['*' => Http::response(['writeResults' => [[], []]]), 200]);

        $updated = $this->client()->updateMany([
            ['id' => 'one', 'status' => 'closed'],
            ['id' => 'two', 'status' => 'closed'],
        ]);

        self::assertSame(2, $updated);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $writes = $request->data()['writes'] ?? [];

            return str_ends_with($request->url(), '/documents:commit')
                && count($writes) === 2
                && ($writes[0]['update']['name'] ?? null)
                    === 'projects/test-project/databases/(default)/documents/records/one'
                && ($writes[0]['updateMask']['fieldPaths'] ?? null) === ['status']
                && ($writes[0]['currentDocument']['exists'] ?? null) === true;
        });
    }

    public function test_with_batches_relationships_instead_of_querying_per_parent(): void
    {
        Http::fake([
            '*' => Http::response([
                ['document' => $this->document('one')],
                ['document' => $this->document('two')],
            ]),
        ]);
        RelatedModelFixture::$calls = [];
        RelatedModelFixture::$eagerLoads = [];
        RelatedModelFixture::$orders = [];
        RelatedModelFixture::$items = collect([
            $this->relatedModel('child-one', 'one'),
            $this->relatedModel('child-two', 'two'),
        ]);

        $parents = $this->client(ParentModelFixture::class)->with('children')->orderBy('__name__')->get();

        self::assertCount(1, RelatedModelFixture::$calls);
        self::assertSame(['one', 'two'], RelatedModelFixture::$calls[0]);
        self::assertSame('child-one', $parents[0]->children->first()->id);
        self::assertSame('child-two', $parents[1]->children->first()->id);
    }

    public function test_relationship_eager_loading_supports_nested_relations_and_constraints(): void
    {
        Http::fake([
            '*' => Http::response([
                ['document' => $this->document('one')],
            ]),
        ]);
        RelatedModelFixture::$calls = [];
        RelatedModelFixture::$eagerLoads = [];
        RelatedModelFixture::$orders = [];
        RelatedModelFixture::$items = collect([$this->relatedModel('child-one', 'one')]);

        $this->client(ParentModelFixture::class)
            ->with([
                'children.author',
                'children' => fn ($query) => $query->orderBy('created_at', 'desc'),
            ])
            ->orderBy('__name__')
            ->get();

        self::assertSame([['author']], RelatedModelFixture::$eagerLoads);
        self::assertSame([['created_at', 'desc']], RelatedModelFixture::$orders);
    }

    private function client(string $modelClass = FClientModelFixture::class): FClient
    {
        return new FClient(
            collection: 'records',
            primaryKey: 'id',
            fillable: ['id', 'status', 'created_at'],
            required: [],
            default: [],
            fieldTypes: [],
            model: 'Record',
            hidden: [],
            modelClass: $modelClass
        );
    }

    private function relatedModel(string $id, string $parentId): \Roddy\FirestoreEloquent\Firestore\Eloquent\DataHelperController
    {
        return new \Roddy\FirestoreEloquent\Firestore\Eloquent\DataHelperController(
            data: ['id' => $id, 'parent_id' => $parentId],
            convertToFirestoreFormat: fn (array $data) => ['fields' => $data],
            primaryKey: 'id',
            collection: 'children',
            patchRequest: fn () => null,
            deleteRequest: fn () => null,
            modelClass: FClientModelFixture::class
        );
    }

    private function document(string $id): array
    {
        return [
            'name' => "projects/test-project/databases/(default)/documents/records/{$id}",
            'fields' => ['id' => ['stringValue' => $id]],
        ];
    }
}

final class FClientModelFixture
{
}

final class ParentModelFixture
{
    public function children(): array
    {
        return [
            'relation' => 'hasMany',
            'related' => RelatedModelFixture::class,
            'foreignKey' => 'parent_id',
            'localKey' => 'id',
        ];
    }
}

final class RelatedModelFixture
{
    public static array $calls = [];
    public static array $eagerLoads = [];
    public static array $orders = [];
    public static $items;

    public static function whereIn(string $field, array $values): object
    {
        self::$calls[] = $values;
        return new class {
            public function with(array $relations): static
            {
                RelatedModelFixture::$eagerLoads[] = $relations;
                return $this;
            }

            public function orderBy(string $field, string $direction): static
            {
                RelatedModelFixture::$orders[] = [$field, $direction];
                return $this;
            }

            public function get()
            {
                return RelatedModelFixture::$items;
            }
        };
    }
}
