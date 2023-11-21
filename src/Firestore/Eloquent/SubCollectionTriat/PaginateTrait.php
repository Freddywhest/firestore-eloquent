<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat;

use Illuminate\Support\Facades\Request;
use Livewire\Attributes\On;
use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat;
use Roddy\FirestoreEloquent\Firestore\Url\GetLivewireUrl as Url;

trait PaginateTrait
{
    private $name;
    /**
     * Paginate Firestore collection.
     *
     * @param int $perPage The number of items per page.
     * @param mixed $query The query to filter documents to paginate.
     * @param mixed $firestore The Firestore instance.
     * @param string $primaryKey The primary key of the collection.
     * @param array $orderBy The fields to order the documents by.
     * @param string $order The order to sort the documents by.
     * @throws \Exception If an error occurs during the pagination process.
     * @return array The paginated documents.
     */
    protected function fPaginate($path, $direction, $query, $model, $collection, $name, $limit): object
    {
        if($path){
            if(!$direction){
                $data = $query->orderBy($path, 'DESC');
            }else{
                $data = $query->orderBy($path, $direction);
            }
        }else{
            $data = $query;
        }

        $this->name = $name;

        $totalData = $data->count();
        $totalPages = (int) ceil($totalData/$limit);

        $url = Url::current();
        $url_decomposition = parse_url ($url);
        $queries = array_key_exists('query',(array) $url_decomposition)?$url_decomposition['query']:false;
        $queries_array = array ();
        if ($queries) {
            $cut_queries   = explode('&', $queries);
            foreach ($cut_queries as $k => $v) {
                if ($v)
                {
                    $tmp = explode('=', $v);
                    if (sizeof($tmp ) < 2) $tmp[1] = true;
                    $queries_array[$tmp[0]] = urldecode($tmp[1]);
                }
            }
        }

        $newQueries = array_merge($queries_array);


        if(Request::has($name)){
            $currentPage = (int) Request::input($name);
        }elseif(isset($newQueries[$name]) && $newQueries[$name] > 0 && $newQueries[$name] <= $totalPages){
            $currentPage = (int) $newQueries[$name];
        }else{
            $currentPage = 1;
        }

        $offset = ($currentPage - 1) * $limit;

        $paginatedData = $data->offset($offset)->limit($limit);

        $result = [];

        $pagination = [
            "total_pages" => $totalPages,
            "per_page" => $limit,
            "current_page" => $currentPage,
            "last_page" => $totalPages,
            "next_page" => $currentPage < $totalPages ? $currentPage + 1 : null,
            "prev_page" => $currentPage > 1 ? $currentPage - 1 : null,
            "from" => $offset,
            "to" => $totalData < $limit ? $totalData : $offset + $limit,
            "total" => $totalData,
        ];

        if($paginatedData->count() > 0){
            foreach($paginatedData->documents()->rows() as $data){
                $documentId = $data->id();
                $collection = $collection;

                array_push($result, new FirestoreDataFormat(
                    data: $data->data(),
                    documentId: $documentId,
                    exists: $data->exists(),
                    collectionName: $collection,
                    model: $model,
                ));
            }
        }else{
            array_push($result, new FirestoreDataFormat(
                data: [],
                documentId: null,
                exists: false,
                collectionName: $collection,
                model: $model,
            ));
        }

        return new class($pagination, $result, $name) {
            private $pagination;
            private $data;
            private $name;
            use PaginationThemes;

            public function __construct($pagination, $result, $name)
            {
                $this->pagination = (object) $pagination;
                $this->data = $result;
                $this->name = $name;
            }

            public function __get($name)
            {
                if ($this->pagination->$name) {
                    return $this->pagination->$name;
                }

                $trace = debug_backtrace();
                trigger_error(
                    'Attempt to read undefined property "'.$name.'"'.
                    ' in ' . $trace[0]['file'] .
                    ' on line ' . $trace[0]['line'],
                    E_USER_NOTICE);
                return null;
            }

            public function data()
            {
                return isset($this->data[0]) && $this->data[0]->exists() ? $this->data : [];
            }

            public function pagination()
            {
                return $this->pagination;
            }

            public function count()
            {
                return $this->pagination->total_pages;
            }

            public function currentPage()
            {
                return $this->pagination->current_page;
            }

            public function firstItem()
            {
                return $this->pagination->from;
            }

            public function hasMorePages()
            {
                return $this->pagination->current_page < $this->pagination->total_pages;
            }

            public function totalPages()
            {
                return $this->pagination->total_pages;
            }

            public function lastItem()
            {
                return $this->pagination->to;
            }

            public function lastPage()
            {
                return $this->pagination->last_page;
            }

            public function nextPageUrl()
            {
                if($this->pagination->next_page){
                    $url = Url::current();
                    $url_decomposition = parse_url ($url);
                    $array = array($this->name => $this->pagination->next_page);
                    $cut_url = explode('?', $url);
                    $queries = array_key_exists('query',(array) $url_decomposition)?$url_decomposition['query']:false;
                    $queries_array = array ();
                    if ($queries) {
                        $cut_queries   = explode('&', $queries);
                        foreach ($cut_queries as $k => $v) {
                            if ($v)
                            {
                                $tmp = explode('=', $v);
                                if (sizeof($tmp ) < 2) $tmp[1] = true;
                                $queries_array[$tmp[0]] = urldecode($tmp[1]);
                            }
                        }
                    }
                    $newQueries = array_merge($queries_array,$array);
                    return $cut_url[0].'?'.http_build_query($newQueries);
                }

                return null;
            }

            public function previousPageUrl()
            {
                if($this->pagination->prev_page){
                    $url = Url::current();
                    $url_decomposition = parse_url ($url);
                    $array = array($this->name => $this->pagination->prev_page);
                    $cut_url = explode('?', $url);
                    $queries = array_key_exists('query',(array) $url_decomposition)?$url_decomposition['query']:false;
                    $queries_array = array ();
                    if ($queries) {
                        $cut_queries   = explode('&', $queries);
                        foreach ($cut_queries as $k => $v) {
                            if ($v)
                            {
                                $tmp = explode('=', $v);
                                if (sizeof($tmp ) < 2) $tmp[1] = true;
                                $queries_array[$tmp[0]] = urldecode($tmp[1]);
                            }
                        }
                    }
                    $newQueries = array_merge($queries_array,$array);
                    return $cut_url[0].'?'.http_build_query($newQueries);
                }

                return null;
            }

            public function onFirstPage()
            {
                return $this->pagination->current_page === 1;
            }

            public function url($page)
            {
                $url = Url::current();
                $url_decomposition = parse_url ($url);
                $array = array($this->name => $page);
                $cut_url = explode('?', $url);
                $queries = array_key_exists('query',(array) $url_decomposition)?$url_decomposition['query']:false;
                $queries_array = array ();
                if ($queries) {
                    $cut_queries   = explode('&', $queries);
                    foreach ($cut_queries as $k => $v) {
                        if ($v)
                        {
                            $tmp = explode('=', $v);
                            if (sizeof($tmp ) < 2) $tmp[1] = true;
                            $queries_array[$tmp[0]] = urldecode($tmp[1]);
                        }
                    }
                }

                $newQueries = array_merge($queries_array, $array);
                return $cut_url[0].'?'.http_build_query(array_filter($newQueries));
            }

            public function total()
            {
                return $this->pagination->total;
            }

            public function getPageName()
            {
                return $this->name;
            }

            public function links($theme = null)
            {
                if($this->hasMorePages() || $this->totalPages() > 1){
                    return <<<PAGINATION_HTML
                        {$this->styles($theme)}
                        {$this->generatePagination($this->pagination->current_page, $this->pagination->total_pages, 3, $theme, $this->name)}
                    PAGINATION_HTML;
                }
            }

            public function livewireLinks($theme = null)
            {
                if($this->hasMorePages() || $this->totalPages() > 1){
                    return <<<PAGINATION_HTML
                        {$this->styles($theme)}
                        {$this->generatePaginationLivewire($this->pagination->current_page, $this->pagination->total_pages, 3, $theme, $this->name)}
                    PAGINATION_HTML;
                }
            }

        };

    }
}
