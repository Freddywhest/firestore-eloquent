<?php


namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

trait PaginateQueries
{
    public function paginate($perPage = 10, $page = 1)
    {
        $currentPage = !is_numeric((int) $page) || $page < 1 ? 1 : (int) $page;
        $query = $this->limit($perPage)->offset(($currentPage - 1) * $perPage)->getQuery();
        $data =  $this->postRequest(':runQuery', $query, false)?->all();
        $total = $this->count(true);
        return new LengthAwarePaginator($data, $total, $perPage, $currentPage, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }
}
