<?php

namespace App\Filters;

class ActivityListFilter
{
    /**
     * @var string $name
     */
    public $name;

    /**
     * @var integer $owner
     */
    public $owner;

    /**
     * @var integer $assignedUser
     */
    public $assignedUser;

    /**
     * @var array $technology
     */
    public $technology;

    public function setFilterFields(array $filter): self
    {
        if (!empty($filter['name'])) {
            $this->name = $filter['name'];
        }
        if (!empty($filter['owner']) && filter_var($filter['owner'], FILTER_VALIDATE_INT)) {
            $this->owner = $filter['owner'];
        }
        if (!empty($filter['technology'])) {
            $this->technology = $filter['technology'];
        }
        if (!empty($filter['assignedUser']) && filter_var($filter['assignedUser'], FILTER_VALIDATE_INT)) {
            $this->assignedUser = $filter['assignedUser'];
        }

        return $this;
    }
}
