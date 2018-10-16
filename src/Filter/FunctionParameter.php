<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\Entity\Value;

class FunctionParameter extends AbstractFilter
{
    public const TITLE = 'fun';

    public function __construct(string $column, Value $value)
    {
        parent::__construct(self::TITLE, $column, $value);
    }
}
