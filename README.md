
![Packagist Downloads](https://img.shields.io/packagist/dt/roddy/firestore-eloquent)

<h1 align="center"> Laravel Firestore Eloquent</h1> <br>
<h1 align="center">
   
![Logo](img/logo.png)

</h1> <br>

This package is a customized version of Laravel Eloquent designed for seamless integration with Google Firestore within Laravel applications. Firestore boasts exceptional scalability and speed, but it provides a more limited feature set compared to conventional SQL databases.

### Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Documentation](#documentation)
- [Limitations](#limitations)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [License](#license)

### Requirements

- [PHP >= 8.1](https://php.net)
- [Laravel >= 9](https://laravel.com/docs/9.x) (Recommeded [Latest Laravel](https://laravel.com))
- [Composer](https://getcomposer.org/)

### Installation

1. Install this package using composer
   ```bash
    composer require roddy/firestore-eloquent
   ```
2. Configure the project and server credentials. Firestore requests are authenticated by default.

   ```dotenv
   FIREBASE_PROJECT_ID=your-project-id
   FIREBASE_CREDENTIALS=/absolute/path/to/service-account.json
   GOOGLE_CLOUD_STORAGE_BUCKET=your-project.appspot.com
   ```

   Application Default Credentials are also supported when `FIREBASE_CREDENTIALS` is omitted. Only projects that intentionally use public Firestore rules should set `FIREBASE_ALLOW_UNAUTHENTICATED=true`.

3. Publish the package configuration using Artisan
   ```bash
   php artisan vendor:publish --provider="Roddy\FirestoreEloquent\Providers\FModelProvider" --force
   ```
   You can locate the configuration file in **`config/firebase.php`**.

### Documentation

Visit [Laravel Firestore Eloquent](https://firestore-eloquent.netlify.app/) for documentation.

OR

Use this link [https://firestore-eloquent.netlify.app/](https://firestore-eloquent.netlify.app/)

### `FS` query builder

Use the `FS` facade when you want Laravel `DB::table()`-style access without creating an `FModel`:

```php
use Roddy\FirestoreEloquent\Facade\FS;

$rides = FS::collection('rides')
    ->where('status', '=', 'completed')
    ->orderByDesc('created_at')
    ->cursorPaginate(20);

// table() is an alias for collection().
$ride = FS::table('rides')->find('ride-123');
```

The builder supports retrieval, filters, ordering, selection, pagination, aggregates, `exists()`, `value()`, `pluck()`, inserts, batch-backed query updates, and batch-backed query deletes.

```php
$id = FS::collection('rides')->insertGetId([
    'status' => 'pending',
    'driver_id' => 'driver-123',
]);

FS::collection('rides')
    ->where('status', 'pending')
    ->update(['status' => 'expired']);
```

Unlike model writes, raw `FS` writes do not apply `$fillable`, `$required`, `$default`, `$hidden`, or `$fieldTypes`. Validate and authorize raw data before writing it.

### Transactions

Use `FS::transaction()` when writes depend on values read from Firestore:

```php
use Roddy\FirestoreEloquent\Firestore\FirestoreTransaction;
use RuntimeException;

$newBalance = FS::transaction(function (FirestoreTransaction $transaction) {
    $account = $transaction->collection('accounts')->findOrFail('account-1');
    $newBalance = $account->balance - 50;

    if ($newBalance < 0) {
        throw new RuntimeException('Insufficient balance.');
    }

    $transaction->update('accounts', 'account-1', [
        'balance' => $newBalance,
    ]);
    $transaction->create('ledger_entries', [
        'account_id' => 'account-1',
        'amount' => -50,
    ]);

    return $newBalance;
});
```

All transaction reads must run before any write is queued. The callback is rolled back on exceptions and automatically retried when Firestore reports `ABORTED` contention, so it may execute more than once. Keep external side effects out of the callback.

Manual Laravel-style control is also available. Model reads and writes created after `beginTransaction()` automatically join the active transaction:

```php
try {
    FS::beginTransaction();

    $ride = Ride::findOrFail('ride-1');
    $ride->update(['status' => 'completed']);

    FS::commit();
} catch (\Throwable $exception) {
    FS::rollback();
    throw $exception;
}
```

Manual transactions do not automatically replay the surrounding code after contention. Prefer `FS::transaction()` when automatic retries are required.

### Fast pagination

The pagination methods now use Laravel's native paginator classes, so `links()`, named paginator state, JSON resources, and Livewire pagination work the same way as Laravel's query builder.

Use cursor pagination for large collections. Unlike page-number pagination, it does not scan documents from earlier pages and it does not execute a count query.

```php
$page = Post::orderBy('created_at', 'desc')->cursorPaginate(20);

$items = $page->items();
$nextCursor = $page->nextCursor()?->encode();
$previousCursor = $page->previousCursor()?->encode();

// Blade and Livewire can render the native paginator directly.
echo $page->links();
```

Use `simplePaginate()` when page numbers are required but the total count is not. Use `paginate()` only when an exact total is required; Firestore offsets become progressively slower on deep pages.

For large background jobs, `lazy()` and `chunk()` use cursor pagination internally and keep memory usage bounded:

```php
Post::orderBy('created_at')->chunk(200, function ($posts) {
    foreach ($posts as $post) {
        // Process one bounded page.
    }
});
```

Bulk updates use Firestore commits, reducing hundreds of HTTP requests to one request per 500 documents:

```php
Post::updateMany([
    ['id' => 'post-1', 'status' => 'published'],
    ['id' => 'post-2', 'status' => 'published'],
]);
```

Every group of at most 500 updates is atomic. Documents must include the model's primary key, and updated fields must be fillable.

### Avoid relationship N+1 queries

Load relationships in batches with `with()`. Firestore `IN` limits are handled automatically by chunking parent keys into groups.

```php
$posts = Post::with(['comments', 'author'])
    ->orderBy('created_at', 'desc')
    ->get();

$posts = Post::with([
    'comments.author',
    'comments' => fn ($query) => $query->orderBy('created_at', 'desc'),
])->get();
```

Without `with()`, accessing a relationship property still performs a lazy query for that individual model.

### Laravel authentication

Register the Firestore user provider in the application's `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'firestore',
        'model' => App\FModels\User::class,
    ],
],
```

The normal session guard can then use Firestore through Laravel's standard API:

```php
Auth::attempt(['email' => $email, 'password' => $password], remember: true);
Auth::user();
Auth::id();
Auth::check();
Auth::loginUsingId($id);
Auth::logout();
```

The model should include `password` in its fillable fields if automatic password rehashing is desired. The remember token is persisted separately and does not need to be fillable. The older `FAuth` helper remains available for backward compatibility.

### Limitations

[Limitations](https://firestore-eloquent.netlify.app/docs/limitations) for documentation.

### TODO

[Todo](https://firestore-eloquent.netlify.app/docs/todo) for documentation.

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Acknowledgments

- [Laravel](https://laravel.com/)
- [Google Cloud Firestore](https://cloud.google.com/firestore/)
- [Google Cloud Firestore Storage](https://cloud.google.com/)
- [PHP](https://php.net)

## Contributors

### Code Contributors

This project exists thanks to all the people who contribute. [[Contribute](https://github.com/FreddyWhest/firestore-eloquent/graphs/contributors)].

<a href = "https://github.com/FreddyWhest/firestore-eloquent/graphs/contributors">
  <img src = "https://contrib.rocks/image?repo=FreddyWhest/firestore-eloquent"/>
</a>

### Financial Contributors

Become a financial contributor and help us sustain our community.

<h1>
  <a href="https://www.buymeacoffee.com/alfrednti" target="_blank">
  <img src="https://cdn.buymeacoffee.com/buttons/v2/default-red.png" alt="Buy Me A Coffee" width="150" />
  </a>
</h1>
