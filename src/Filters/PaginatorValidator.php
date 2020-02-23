<?php

namespace App\Filters;

class PaginatorValidator
{
    public function isPageNumberValid($maxPage, $numPage): bool
    {
        return !(($maxPage !== 0 && $numPage > $maxPage) || $numPage < 0);
    }

    public function isPageSizeValid($pageSize): bool
    {
        return !($pageSize < -1);
    }
}
