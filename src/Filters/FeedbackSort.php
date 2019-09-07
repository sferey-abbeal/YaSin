<?php

namespace App\Filters;

use DateTime;

class FeedbackSort extends BaseSorting
{
    /**
     * @var DateTime
     */
    public $createdAt = 'desc';

    /**
     * @var integer
     */
    public $stars;

    public function setSortingFields(array $sorting): self
    {
        if (!empty($sorting['stars']) && $this->isSortingParamValid($sorting['stars'])) {
            $this->stars = $sorting['stars'];
        }
        if (!empty($sorting['createdAt']) && $this->isSortingParamValid($sorting['createdAt'])) {
            $this->createdAt = $sorting['createdAt'];
        }

        return $this;
    }
}
