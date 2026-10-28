<?php

declare(strict_types=1);

namespace Roddy\FirestoreEloquent\Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;
use Roddy\FirestoreEloquent\Facade\FS;
use Roddy\FirestoreEloquent\Firestore\FirestoreManager;
use Roddy\FirestoreEloquent\Facade\FModel;

final class FirestoreManagerTest extends TestCase
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
        $container->instance('firestore', new FirestoreManager());
        Container::setInstance($container);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
    }

    public function test_fs_facade_supports_laravel_style_query_arguments(): void
    {
        $query = FS::collection('rides')
            ->where('status', '=', 'completed')
            ->orderByDesc('created_at')
            ->select('id', 'status')
            ->getQuery();

        self::assertSame('EQUAL', $query['structuredQuery']['where']['fieldFilter']['op']);
        self::assertSame('status', $query['structuredQuery']['where']['fieldFilter']['field']['fieldPath']);
        self::assertSame('DESCENDING', $query['structuredQuery']['orderBy'][0]['direction']);
        self::assertSame(
            [['fieldPath' => 'id'], ['fieldPath' => 'status']],
            $query['structuredQuery']['select']['fields']
        );
    }

    public function test_table_is_an_alias_and_custom_primary_key_is_used(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $id = FS::table('users', 'uid')->insertGetId([
            'uid' => 'user-1',
            'email' => 'person@example.com',
        ]);

        self::assertSame('user-1', $id);
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/documents/users?documentId=user-1')
                && isset($request->data()['fields']['email']['stringValue']);
        });
    }

    public function test_query_update_reads_matching_ids_then_uses_one_commit(): void
    {
        Http::fakeSequence()
            ->push([
                ['document' => $this->document('ride-1', 'open')],
                ['document' => $this->document('ride-2', 'open')],
            ])
            ->push(['writeResults' => [[], []]], 200);

        $updated = FS::collection('rides')
            ->where('status', 'open')
            ->update(['status' => 'closed']);

        self::assertSame(2, $updated);
        Http::assertSentCount(2);
        Http::assertSent(function ($request): bool {
            $writes = $request->data()['writes'] ?? [];

            return str_ends_with($request->url(), '/documents:commit')
                && count($writes) === 2
                && ($writes[0]['update']['name'] ?? null)
                    === 'projects/test-project/databases/(default)/documents/rides/ride-1';
        });
    }

    public function test_query_delete_uses_commit_delete_writes(): void
    {
        Http::fakeSequence()
            ->push([
                ['document' => $this->document('ride-1', 'cancelled')],
                ['document' => $this->document('ride-2', 'cancelled')],
            ])
            ->push(['writeResults' => [[], []]], 200);

        $deleted = FS::collection('rides')
            ->where('status', 'cancelled')
            ->delete();

        self::assertSame(2, $deleted);
        Http::assertSent(function ($request): bool {
            $writes = $request->data()['writes'] ?? [];

            return str_ends_with($request->url(), '/documents:commit')
                && ($writes[0]['delete'] ?? null)
                    === 'projects/test-project/databases/(default)/documents/rides/ride-1';
        });
    }

    public function test_value_pluck_and_exists_use_bounded_queries(): void
    {
        Http::fakeSequence()
            ->push([
                ['document' => $this->document('ride-1', 'completed')],
            ])
            ->push([
                ['document' => $this->document('ride-1', 'completed')],
                ['document' => $this->document('ride-2', 'pending')],
            ])
            ->push([
                ['document' => $this->document('ride-1', 'completed')],
            ]);

        self::assertSame('completed', FS::collection('rides')->value('status'));
        self::assertSame(
            ['ride-1' => 'completed', 'ride-2' => 'pending'],
            FS::collection('rides')->pluck('status', 'id')->all()
        );
        self::assertTrue(FS::collection('rides')->where('status', 'completed')->exists());

        Http::assertSent(function ($request): bool {
            return ($request->data()['structuredQuery']['limit'] ?? null) === 1;
        });
    }

    public function test_collection_path_must_end_in_a_collection_segment(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FS::collection('users/user-1');
    }

    public function test_transaction_reads_then_commits_queued_writes(): void
    {
        Http::fakeSequence()
            ->push(['transaction' => 'transaction-one'], 200)
            ->push($this->document('ride-1', 'pending'), 200)
            ->push(['writeResults' => [[]], 'commitTime' => '2026-10-28T10:00:00Z'], 200);

        $result = FS::transaction(function ($transaction): string {
            $ride = $transaction->collection('rides')->findOrFail('ride-1');

            $transaction->update('rides', $ride->id, [
                'status' => 'completed',
            ]);

            return $ride->status;
        });

        self::assertSame('pending', $result);
        Http::assertSentCount(3);
        Http::assertSent(function ($request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/documents/rides/ride-1')
                && ($query['transaction'] ?? null) === 'transaction-one';
        });
        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/documents:commit')
                && ($payload['transaction'] ?? null) === 'transaction-one'
                && ($payload['writes'][0]['updateMask']['fieldPaths'] ?? null) === ['status'];
        });
    }

    public function test_transaction_structured_queries_include_the_transaction_token(): void
    {
        Http::fakeSequence()
            ->push(['transaction' => 'transaction-query'], 200)
            ->push([
                ['document' => $this->document('ride-1', 'pending')],
            ], 200)
            ->push(['writeResults' => []], 200);

        $ride = FS::transaction(function ($transaction) {
            return $transaction->collection('rides')
                ->where('status', 'pending')
                ->first();
        });

        self::assertSame('ride-1', $ride->id);
        Http::assertSent(function ($request): bool {
            return str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/documents:runQuery')
                && ($request->data()['transaction'] ?? null) === 'transaction-query';
        });
    }

    public function test_transaction_rolls_back_when_callback_throws(): void
    {
        Http::fakeSequence()
            ->push(['transaction' => 'transaction-one'], 200)
            ->push([], 200);

        try {
            FS::transaction(function (): void {
                throw new \RuntimeException('stop');
            });
            self::fail('The transaction callback exception was not thrown.');
        } catch (\RuntimeException $exception) {
            self::assertSame('stop', $exception->getMessage());
        }

        Http::assertSent(function ($request): bool {
            return str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/documents:rollback')
                && ($request->data()['transaction'] ?? null) === 'transaction-one';
        });
    }

    public function test_transaction_rejects_reads_after_a_write_is_queued(): void
    {
        Http::fakeSequence()
            ->push(['transaction' => 'transaction-one'], 200)
            ->push([], 200);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('reads must be executed before writes');

        FS::transaction(function ($transaction): void {
            $transaction->update('rides', 'ride-1', ['status' => 'completed']);
            $transaction->collection('rides')->find('ride-1');
        });
    }

    public function test_transaction_retries_the_full_callback_after_aborted_contention(): void
    {
        Http::fakeSequence()
            ->push(['transaction' => 'transaction-one'], 200)
            ->push(['error' => ['status' => 'ABORTED', 'message' => 'contention']], 409)
            ->push([], 200)
            ->push(['transaction' => 'transaction-two'], 200)
            ->push(['writeResults' => [[]]], 200);

        $runs = 0;
        $result = FS::transaction(function ($transaction) use (&$runs): int {
            $runs++;
            $transaction->create('audit_logs', ['message' => 'transaction attempt']);

            return $runs;
        }, attempts: 2);

        self::assertSame(2, $runs);
        self::assertSame(2, $result);
        Http::assertSentCount(5);
    }

    public function test_manual_transaction_automatically_enlists_model_reads_and_updates(): void
    {
        Http::fakeSequence()
            ->push(['transaction' => 'manual-transaction'], 200)
            ->push($this->document('ride-1', 'pending'), 200)
            ->push(['writeResults' => [[]]], 200);

        FS::beginTransaction();

        $ride = ManualTransactionRide::findOrFail('ride-1');
        $ride->update(['status' => 'completed']);

        FS::commit();

        Http::assertSentCount(3);
        Http::assertSent(function ($request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/documents/rides/ride-1')
                && ($query['transaction'] ?? null) === 'manual-transaction';
        });
        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/documents:commit')
                && ($payload['transaction'] ?? null) === 'manual-transaction'
                && ($payload['writes'][0]['update']['fields']['status']['stringValue'] ?? null) === 'completed';
        });
    }

    public function test_manual_transaction_rollback_discards_queued_model_writes(): void
    {
        Http::fakeSequence()
            ->push(['transaction' => 'manual-transaction'], 200)
            ->push($this->document('ride-1', 'pending'), 200)
            ->push([], 200);

        FS::beginTransaction();
        $ride = ManualTransactionRide::findOrFail('ride-1');
        $ride->update(['status' => 'completed']);
        FS::rollback();

        Http::assertSentCount(3);
        Http::assertSent(function ($request): bool {
            return str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/documents:rollback');
        });
        Http::assertNotSent(function ($request): bool {
            return str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/documents:commit');
        });
    }

    public function test_manual_rollback_is_safe_without_an_active_transaction(): void
    {
        FS::rollback();

        self::assertFalse(FS::inTransaction());
        Http::assertNothingSent();
    }

    private function document(string $id, string $status): array
    {
        return [
            'name' => "projects/test-project/databases/(default)/documents/rides/{$id}",
            'fields' => [
                'id' => ['stringValue' => $id],
                'status' => ['stringValue' => $status],
            ],
        ];
    }
}

final class ManualTransactionRide extends FModel
{
    protected $collection = 'rides';
    protected $fillable = ['id', 'status'];
}
