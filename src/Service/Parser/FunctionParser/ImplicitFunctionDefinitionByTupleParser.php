<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use MF\Collection\Immutable\Tuple;

class ImplicitFunctionDefinitionByTupleParser extends AbstractFunctionParser
{
    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    public function supportsParameters(array $queryParameters, string $rawColumn, $rawValue): bool
    {
        if ($this->isTuple($rawColumn)) {
            $possiblyParameters = Tuple::parse($rawColumn)->toArray();

            foreach ($this->functions->getFunctionNamesByAllParameters($possiblyParameters) as $functionName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    protected function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable
    {
        $rawValue = $this->assertTupleValue($rawValue);
        $columns = Tuple::parse($rawColumn)->toArray();
        $values = Tuple::parse($rawValue, count($columns))->toArray();

        foreach ($this->functions->getFunctionNamesByAllParameters($columns) as $functionName) {
            yield from $this->parseFunction($functionName);
        }

        foreach ($columns as $parameter) {
            yield from $this->parseFunctionParameter($parameter, array_shift($values));
        }
    }
}
