<?php

namespace App\Filters;

class UserListSort extends BaseSorting
{
    /**
     * @var int
     */
    public $seniority = 'desc';

    public function setSortingFields(array $sorting): self
    {
        if (!empty($sorting['seniority']) && $this->isSortingParamValid($sorting['seniority'])) {
            $this->seniority = $sorting['seniority'];
        }

        return $this;
    }
}
