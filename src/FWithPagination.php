<?php

namespace Roddy\FirestoreEloquent;

use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\Features\HandlePagination;

trait FWithPagination
{
    use HandlePagination;
}
