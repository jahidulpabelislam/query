<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use JPI\Utils\Collection\PaginatedTrait;
use JPI\Utils\Collection\PaginatedInterface;

class PaginatedResult extends Result implements PaginatedInterface {

    use PaginatedTrait;
}
