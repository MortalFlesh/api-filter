<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Assert\Assertion;
use Lmc\ApiFilter\Entity\Value;

class FilterFunction extends AbstractFilter
{
    public const TITLE = 'fun';

    public function __construct(string $column, Value $value, string $title = self::TITLE)
    {
        $this->assertValue($value);
        parent::__construct($title, $column, $value);
    }

    private function assertValue(Value $value): void
    {
        Assertion::isCallable($value->getValue(), 'Value for filter function must be callable. "%s" given.');
    }
}
