<?php

namespace App\Filters;

class BaseSorting
{
    public function isSortingParamValid($sortingParameter): bool
    {
        strtolower($sortingParameter);
        return $sortingParameter === 'desc' || $sortingParameter === 'asc';
    }
}
