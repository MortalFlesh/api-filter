<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

interface ParserInterface
{
    public function supports($rawColumn, $rawValue): bool;

    public function parse($rawColumn, $rawValue): iterable;
}
