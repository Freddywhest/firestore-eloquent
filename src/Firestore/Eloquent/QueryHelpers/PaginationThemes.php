<?php
namespace Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers;

use Roddy\FirestoreEloquent\FJavaScript;

/**
 * PaginationThemes trait.
 *
 * This trait provides pagination functionality for Firestore Eloquent models.
 *
 * @package Roddy\FirestoreEloquent
 */
trait PaginationThemes
{
    use FJavaScript;
    /**
     * Returns pagination styles based on the given theme.
     *
     * @param string|null $theme The theme to use for the pagination styles.
     * @return string The pagination styles as a string.
     */
    private function styles($theme)
    {
        if(!$theme){
            return <<<'PAGINATION_STYLE'
                <style>
                    .pagination {
                        margin-top: 20px;
                    }

                    .pagination a,
                    .pagination span {
                        padding: 5px 10px;
                        margin: 0 5px;
                        border: 1px solid #ccc;
                        text-decoration: none;
                        color: #333;
                        background-color: #f9f9f9;
                        border-radius: 3px;
                    }

                    .pagination span {
                        background-color: #ddd;
                    }

                    .pagination a:hover {
                        background-color: #ddd;
                    }

                    .pagination span.ellipse {
                        margin: 0 5px;
                    }
                </style>
            PAGINATION_STYLE;
        }
        return '';

    }

    /**
     * Generates a pagination with vanilla CSS style.
     *
     * @param int $currentPage The current page number.
     * @param int $totalPages The total number of pages.
     * @param int $pagesToShow The number of pages to show before and after the current page.
     *
     * @return string The generated pagination HTML.
     */
    private function vanillaCssPagination($currentPage, $totalPages, $pagesToShow, $name)
    {
        $pagination = '';

        // Previous page link
        if ($currentPage > 1) {
            $pagination .= '<a href="?'.$name.'=' . ($currentPage - 1) . '">Previous</a>';
        }

        // Pages with ellipses
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $pagesToShow && $i <= $currentPage + $pagesToShow)) {
                $pagination .= ($i == $currentPage) ? '<span>' . $i . '</span>' : '<a href="?'.$name.'=' . $i . '">' . $i . '</a>';
            } elseif ($pagination && !strstr($pagination, '<span class="ellipse">...</span>')) {
                $pagination .= '<span class="ellipse">...</span>';
            }
        }

        // Next page link
        if ($currentPage < $totalPages) {
            $pagination .= '<a href="?'.$name.'=' . ($currentPage + 1) . '">Next</a>';
        }

        return $pagination;
    }

    private function vanillaCssPaginationLivewire($currentPage, $totalPages, $pagesToShow, $name)
    {
        $pagination = '<span islivewirepaginationavailable="" style="display: none !important">sadasdsa</span><div fpaginationpagename="'.$name.'" x-init="localStorage.setItem(\'fpaginationpagename\', window.localStorage.getItem(\'fpaginationpagename\') ? JSON.stringify({...JSON.parse(window.localStorage.getItem(\'fpaginationpagename\')), '.$name.': \''.$name.'\' }) : JSON.stringify({'.$name.': \''.$name.'\'}))">';

        // Previous page link
        if ($currentPage > 1) {
            $pagination .= '<a style="cursor: pointer !important;" wire:click="previousPage('.($currentPage - 1).', '."'$name'".'); window.history.pushState({}, {}, new URL(window.location));">Previous</a>';
        }

        // Pages with ellipses
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $pagesToShow && $i <= $currentPage + $pagesToShow)) {
                $pagination .= ($i == $currentPage) ? '<span>' . $i . '</span>' : '<a style="cursor: pointer !important;" wire:click="gotoPage('.$i.', '."'$name'".'); window.history.pushState({}, {}, new URL(window.location));">' . $i . '</a>';
            } elseif ($pagination && !strstr($pagination, '<span class="ellipse">...</span>')) {
                $pagination .= '<span class="ellipse">...</span>';
            }
        }

        // Next page link
        if ($currentPage < $totalPages) {
            $pagination .= '<a style="cursor: pointer !important;" wire:click="nextPage('.($currentPage + 1).', '."'$name'".'); window.history.pushState({}, {}, new URL(window.location));">Next</a> </div>';
        }

        return $pagination;
    }

    /**
     * Generates Bootstrap CSS pagination HTML based on the current page, total pages, and number of pages to show.
     *
     * @param int $currentPage The current page number.
     * @param int $totalPages The total number of pages.
     * @param int $pagesToShow The number of pages to show.
     *
     * @return string The generated pagination HTML.
     */
    function bootstrapCssPagination($currentPage, $totalPages, $pagesToShow, $name) {
        $pagination = '<nav aria-label="Page navigation"><ul class="pagination">';

        // Previous page link
        if ($currentPage > 1) {
            $pagination .= '<li class="page-item"><a class="page-link" href="?'.$name.'=' . ($currentPage - 1) . '">Previous</a></li>';
        }

        // Pages with ellipses
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $pagesToShow && $i <= $currentPage + $pagesToShow)) {
                $pagination .= ($i == $currentPage) ? '<li class="page-item active"><span class="page-link">' . $i . '</span></li>' : '<li class="page-item"><a class="page-link" href="?'.$name.'=' . $i . '">' . $i . '</a></li>';
            } elseif ($pagination && !strstr($pagination, '<li class="page-item disabled"><span class="page-link">...</span></li>')) {
                $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Next page link
        if ($currentPage < $totalPages) {
            $pagination .= '<li class="page-item"><a class="page-link" href="?'.$name.'=' . ($currentPage + 1) . '">Next</a></li>';
        }

        $pagination .= '</ul></nav>';

        return $pagination;
    }

    private function bootstrapCssPaginationLivewire($currentPage, $totalPages, $pagesToShow, $name)
    {

        $pagination = '<span islivewirepaginationavailable="" style="display: none !important">sadasdsa</span>';

        $pagination .= '<nav aria-label="Page navigation" class="mt-3"><ul class="pagination" fpaginationpagename="'.$name.'" x-init="localStorage.setItem(\'fpaginationpagename\', window.localStorage.getItem(\'fpaginationpagename\') ? JSON.stringify({...JSON.parse(window.localStorage.getItem(\'fpaginationpagename\')), '.$name.': \''.$name.'\' }) : JSON.stringify({'.$name.': \''.$name.'\'}))">';

        // Previous page link
        if ($currentPage > 1) {
            $pagination .= '<li class="page-item"><a class="page-link" role="button" wire:click="previousPage('.($currentPage - 1).', '."'$name'".'); window.history.pushState({}, {}, new URL(window.location));">Previous</a></li>';
        }

        // Pages with ellipses
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $pagesToShow && $i <= $currentPage + $pagesToShow)) {
                $pagination .= ($i == $currentPage) ? '<li class="page-item active"><span class="page-link">' . $i . '</span></li>' : '<li class="page-item"><a role="button" class="page-link" wire:click="gotoPage('.$i.', '."'$name'".'); window.history.pushState({}, {}, new URL(window.location));">' . $i . '</a></li>';
            } elseif ($pagination && !strstr($pagination, '<li class="page-item disabled"><span class="page-link">...</span></li>')) {
                $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Next page link
        if ($currentPage < $totalPages) {
            $pagination .= '<li class="page-item"><a class="page-link" role="button" wire:click="nextPage('.($currentPage + 1).', '."'$name'".'); window.history.pushState({}, {}, new URL(window.location));">Next</a></li>';
        }

        $pagination .= '</ul></nav>';

        return $pagination;
    }

    /**
     * Generates a pagination using Tailwind CSS framework.
     *
     * @param int $currentPage The current page number.
     * @param int $totalPages The total number of pages.
     * @param int $pagesToShow The number of pages to show before and after the current page.
     *
     * @return string The generated pagination HTML.
     */
    private function tailwindCssPagination($currentPage, $totalPages, $pagesToShow, $name)
    {
        $pagination = '<nav class="flex justify-center" class="mt-3"><ul class="pagination">';

        // Previous page link
        if ($currentPage > 1) {
            $pagination .= '<li class="page-item"><a class="page-link" href="?'.$name.'=' . ($currentPage - 1) . '">Previous</a></li>';
        }

        // Pages with ellipses
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $pagesToShow && $i <= $currentPage + $pagesToShow)) {
                $pagination .= ($i == $currentPage) ? '<li class="page-item active"><a class="page-link">' . $i . '</a></li>' : '<li class="page-item"><a class="page-link" href="?'.$name.'=' . $i . '">' . $i . '</a></li>';
            } elseif ($pagination && !strstr($pagination, '<li class="page-item disabled"><span class="page-link">...</span></li>')) {
                $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Next page link
        if ($currentPage < $totalPages) {
            $pagination .= '<li class="page-item"><a class="page-link" href="?'.$name.'=' . ($currentPage + 1) . '">Next</a></li>';
        }

        $pagination .= '</ul></nav>';

        return $pagination;
    }

    private function tailwindCssPaginationLivewire($currentPage, $totalPages, $pagesToShow, $name)
    {
        $pagination = '<span islivewirepaginationavailable="" style="display: none !important">sadasdsa</span>';;
        $pagination .= '<nav class="flex justify-center" class="mt-3"><ul class="pagination" fpaginationpagename="'.$name.'" x-init="localStorage.setItem(\'fpaginationpagename\', window.localStorage.getItem(\'fpaginationpagename\') ? JSON.stringify({...JSON.parse(window.localStorage.getItem(\'fpaginationpagename\')), '.$name.': \''.$name.'\' }) : JSON.stringify({'.$name.': \''.$name.'\'}))">';

        // Previous page link
        if ($currentPage > 1) {
            $pagination .= '<li class="page-item"><a class="page-link cursor-pointer" wire:click="previousPage('.($currentPage - 1).', '."'$name'".'); window.history.pushState({}, {}, new URL(window.location));">Previous</a></li>';
        }

        // Pages with ellipses
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $pagesToShow && $i <= $currentPage + $pagesToShow)) {
                $pagination .= ($i == $currentPage) ? '<li class="page-item active"><a class="page-link">' . $i . '</a></li>' : '<li class="page-item"><a class="page-link cursor-pointer" wire:click="gotoPage('.$i.', '."'$name'".'); window.history.pushState({}, {}, new URL(window.location));">' . $i . '</a></li>';
            } elseif ($pagination && !strstr($pagination, '<li class="page-item disabled"><span class="page-link">...</span></li>')) {
                $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Next page link
        if ($currentPage < $totalPages) {
            $pagination .= '<li class="page-item"><a class="page-link cursor-pointer" wire:click="nextPage('.($currentPage + 1).', '."'$name'".'); window.history.pushState({}, {}, new URL(window.location));">Next</a></li>';
        }

        $pagination .= '</ul></nav>';

        return $pagination;
    }

    /**
     * Generates pagination HTML based on the current page, total pages, pages to show, and theme.
     *
     * @param int $currentPage The current page number.
     * @param int $totalPages The total number of pages.
     * @param int $pagesToShow The number of pages to show.
     * @param string|null $theme The pagination theme to use (bootstrap, tailwind, or null for vanilla CSS).
     * @return string The pagination HTML.
     *
     * @throws InvalidArgumentException If an invalid theme is provided.
     */
    private function generatePagination($currentPage, $totalPages, $pagesToShow, $theme, $name)
    {
        if($theme === 'bootstrap'){
            return <<<BOOTSTRAP_PAGINATION
            <div class="container">
                {$this->bootstrapCssPagination($currentPage, $totalPages, $pagesToShow, $name)}
            </div>
            BOOTSTRAP_PAGINATION;

        }elseif($theme === 'tailwind'){
            return <<<TAILWIND_PAGINATION
            <div class="container mx-auto my-8">
                {$this->tailwindCssPagination($currentPage, $totalPages, $pagesToShow, $name)}
            </div>
            TAILWIND_PAGINATION;

        }elseif(!$theme){
            return <<<PAGINATION
            <div class="pagination">
                {$this->vanillaCssPagination($currentPage, $totalPages, $pagesToShow, $name)}
            </div>
            PAGINATION;
        }
    }

    private function generatePaginationLivewire($currentPage, $totalPages, $pagesToShow, $theme, $name)
    {
        if($theme === 'bootstrap'){
            return <<<BOOTSTRAP_PAGINATION
            <div class="container">
                {$this->bootstrapCssPaginationLivewire($currentPage, $totalPages, $pagesToShow, $name)}
            </div>
            BOOTSTRAP_PAGINATION;

        }elseif($theme === 'tailwind'){
            return <<<TAILWIND_PAGINATION
            <div class="container mx-auto my-8">
                {$this->tailwindCssPaginationLivewire($currentPage, $totalPages, $pagesToShow, $name)}
            </div>
            TAILWIND_PAGINATION;

        }elseif(!$theme){
            return <<<PAGINATION
            <div class="pagination">
                {$this->vanillaCssPaginationLivewire($currentPage, $totalPages, $pagesToShow, $name)}
            </div>
            PAGINATION;
        }
    }

}
