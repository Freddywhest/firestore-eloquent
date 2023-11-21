<?php
namespace Roddy\FirestoreEloquent;

trait FStyle
{
    private function loadStylesForLivewirePagination()
    {
        return "
            <style>
                [fpagination\\:loading]{display:none}
            </style>
        ";
    }
}
