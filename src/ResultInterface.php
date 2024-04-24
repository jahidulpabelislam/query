<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use JPI\Utils\Arrayable;
use IteratorAggregate;

/**
 * Represents a single row from the database.
 *
 * Allow iterating over the column values and getting value by column name.
 */
interface ResultInterface extends Arrayable, IteratorAggregate
{
    public static function loadFromDatabaseRow(array $row): static;

    public function getValue(string $key): mixed;
}
