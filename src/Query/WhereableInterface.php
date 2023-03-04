<?php

declare(strict_types=1);

namespace JPI\Database\Query;

interface WhereableInterface {

    /**
     * @param $where string|int
     * @return $this
     */
    public function where($where);
}
