<?php

declare(strict_types=1);

namespace JPI\Database;

use JPI\Utils\Collection\PaginatedTrait;
use JPI\Utils\Collection\PaginatedInterface;

class PaginatedCollection extends Collection implements PaginatedInterface {

    use PaginatedTrait;
}
