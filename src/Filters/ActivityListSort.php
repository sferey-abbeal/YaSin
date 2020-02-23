<?php

namespace App\Filters;

use DateTime;

class ActivityListSort extends BaseSorting
{
    /**
     * @var DateTime
     */
    public $createdAt;

    /**
     * @var DateTime
     */
    public $finalDeadline;

    /**
     * @var string
     */
    public $name;

    public function setSortingFields(array $sorting): self
    {
        if (!empty($sorting['name']) && $this->isSortingParamValid($sorting['name'])) {
            $this->name = $sorting['name'];
        }
        if (!empty($sorting['createdAt']) && $this->isSortingParamValid($sorting['createdAt'])) {
            $this->createdAt = $sorting['createdAt'];
        }
        if (!empty($sorting['finalDeadline']) && $this->isSortingParamValid($sorting['finalDeadline'])) {
            $this->finalDeadline = $sorting['finalDeadline'];
        }

        return $this;
    }
}
