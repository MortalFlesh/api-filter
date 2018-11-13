<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

class SingleColumnArrayValueParser extends AbstractParser
{
    public function supports($rawColumn, $rawValue): bool
    {
        return !$this->isTuple($rawColumn) && is_array($rawValue);
    }

    public function parse($rawColumn, $rawValue): iterable
    {
        foreach ($rawValue as $filter => $value) {
            yield $this->createFilter($rawColumn, $filter, $value);
        }
    }
}
