<?php

declare(strict_types=1);

namespace JPI\Database\Query\Result;

use JPI\Utils\Collection\PaginatedInterface;
use JPI\Utils\Collection\PaginatedTrait;

/**
 * Represents collection of zero or more rows from the database but where the query was paginated.
 */
class PaginatedCollection extends Collection implements PaginatedInterface {

    use PaginatedTrait;
}
