<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use MF\Collection\Immutable\Tuple;

class ExplicitFunctionDefinitionByTupleParser extends AbstractFunctionParser
{
    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    public function supportsParameters(array $queryParameters, string $rawColumn, $rawValue): bool
    {
        return $this->isTuple($rawColumn) && Tuple::parse($rawColumn)->first() === self::FUNCTION_COLUMN;
    }

    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    protected function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable
    {
        $rawValue = $this->assertTupleValue($rawValue);
        $columns = Tuple::parse($rawColumn)->toArray();
        $values = Tuple::parse($rawValue, count($columns))->toArray();

        array_shift($columns);  // just get rid of the first parameter
        $functionName = array_shift($values);

        yield from $this->parseFunction($functionName);
        foreach ($this->functions->getParametersFor($functionName) as $parameter) {
            yield from $this->parseFunctionParameter($parameter, array_shift($values));
        }
    }
}
