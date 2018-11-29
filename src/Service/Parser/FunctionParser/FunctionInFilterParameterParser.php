<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Constant\Column;

class FunctionInFilterParameterParser extends AbstractFunctionParser
{
    protected function supportsParameters(array $queryParameters, string $rawColumn, $rawValue): bool
    {
        return array_key_exists(Column::FILTER, $queryParameters);
    }

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    protected function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable
    {
        Assertion::isArray($rawValue, 'Filter parameter must have functions in array.');

        // for each functions in rawValue, create a filter function

        yield;
    }
}
