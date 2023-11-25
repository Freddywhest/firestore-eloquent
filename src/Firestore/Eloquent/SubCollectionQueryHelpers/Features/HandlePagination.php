<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionQueryHelpers\Features;

use Illuminate\Support\Str;
use Livewire\Attributes\On;

trait HandlePagination
{

    public function previousPage($page, $pageName = 'page')
    {
        $this->setPage($page, $pageName);
    }

    public function nextPage($page, $pageName = 'page')
    {
        $this->setPage($page, $pageName);
    }
    #[On('gotoPageFromLivewireJavascript')]
    public function gotoPage($page, $pageName = 'page')
    {
        $this->setPage($page, $pageName);
    }

    #[On('resetPageFromLivewireJavascript')]
    public function resetPage($pageName = 'page')
    {
        $this->setPage(1, $pageName);
    }

    #[On('resetPageFromLivewireJavascriptWithoutParameter')]
    public function resetPageFromLivewireJavascriptWithoutParameter($pageName = 'page')
    {
        request()->merge([$pageName => 1]);
    }

    public function setPage($page, $pageName = 'page')
    {
        if (is_numeric($page)) {
            $page = (int) ($page <= 0 ? 1 : $page);
        }

        $beforePaginatorMethod = 'updatingPaginators';
        $afterPaginatorMethod = 'updatedPaginators';

        $beforeMethod = 'updating' . ucfirst(Str::camel($pageName));
        $afterMethod = 'updated' . ucfirst(Str::camel($pageName));

        if (method_exists($this, $beforePaginatorMethod)) {
            $this->{$beforePaginatorMethod}($page, $pageName);
        }

        if (method_exists($this, $beforeMethod)) {
            $this->{$beforeMethod}($page, null);
        }

        request()->merge([$pageName => $page]);
        $this->$pageName = $page;

        if (method_exists($this, $afterPaginatorMethod)) {
            $this->{$afterPaginatorMethod}($page, $pageName);
        }

        if (method_exists($this, $afterMethod)) {
            $this->{$afterMethod}($page, null);
        }
    }



}
