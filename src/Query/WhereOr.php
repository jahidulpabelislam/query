<?php

declare(strict_types=1);

namespace JPI\Database\Query;

class WhereOr extends WhereAnd {

    protected $separator = "OR";
}
