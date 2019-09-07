<?php

namespace App\Filters;

class ActivityListFilter
{
    /**
     * @var string $name
     */
    public $name;

    /**
     * @var integer $status
     */
    public $status;

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

    /**
     * @var array $activityType
     */
    public $activityType;

    public function setFilterFields(array $filter): self
    {
        if (!empty($filter['name'])) {
            $this->name = $filter['name'];
        }
        if (!empty($filter['status']) && filter_var($filter['status'], FILTER_VALIDATE_INT)) {
            $this->status = $filter['status'];
        }
        if (!empty($filter['owner']) && filter_var($filter['owner'], FILTER_VALIDATE_INT)) {
            $this->owner = $filter['owner'];
        }
        if (!empty($filter['technology'])) {
            $this->technology = $filter['technology'];
        }
        if (!empty($filter['activityType'])) {
            $this->activityType = $filter['activityType'];
        }
        if (!empty($filter['assignedUser']) && filter_var($filter['assignedUser'], FILTER_VALIDATE_INT)) {
            $this->assignedUser = $filter['assignedUser'];
        }

        return $this;
    }
}
