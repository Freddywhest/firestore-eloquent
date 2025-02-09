<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Roddy\FirestoreEloquent\Firestore\Relations\{FHasMany, FHasOne, FBelongsTo};

trait FRelations
{
    use FHasOne, FHasMany, FBelongsTo;
}
