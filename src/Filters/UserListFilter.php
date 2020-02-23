<?php

namespace App\Filters;

class UserListFilter
{
    /**
     * @var array $technology
     */
    public $technology;

    /**
     * @var array $activityRole
     */
    public $activityRole;

    public function setFilterFields(array $filter): self
    {
        if (!empty($filter['technology'])) {
            $this->technology = $filter['technology'];
        }
        if (!empty($filter['activityRole'])) {
            $this->activityRole = $filter['activityRole'];
        }
        return $this;
    }
}
