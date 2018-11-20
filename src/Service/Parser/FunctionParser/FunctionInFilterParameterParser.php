<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

class FunctionInFilterParameterParser extends AbstractFunctionParser
{
    protected function supportsParameters(array $assertQueryParameters, string $rawColumn, $rawValue): bool
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }

    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    protected function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }
}
