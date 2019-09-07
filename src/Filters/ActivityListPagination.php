<?php

namespace App\Filters;

class ActivityListPagination
{
    /**
     * @var integer $currentPage
     */
    public $currentPage = 1;

    /**
     * @var integer $pageSize
     */
    public $pageSize = 10;

    public function setPaginationFields(array $pagination): self
    {
        if (!empty($pagination['page']) && filter_var($pagination['page'], FILTER_VALIDATE_INT)) {
            $this->currentPage = (integer)$pagination['page'];
        }
        if (!empty($pagination['per_page']) && filter_var($pagination['per_page'], FILTER_VALIDATE_INT)) {
            $this->pageSize = (integer)$pagination['per_page'];
        }

        return $this;
    }
}
