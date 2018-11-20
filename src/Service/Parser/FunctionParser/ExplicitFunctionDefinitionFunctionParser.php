<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Assertion;

class ExplicitFunctionDefinitionFunctionParser extends AbstractFunctionParser
{
    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    public function supports(string $rawColumn, $rawValue): bool
    {
        return $rawColumn === self::FUNCTION_COLUMN;
    }

    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    public function parse(string $rawColumn, $rawValue): iterable
    {
        $queryParameters = $this->assertQueryParameters();

        if ($this->isThereAnyExplicitFunctionDefinition($queryParameters)) {
            $this->markColumnAsParsed(self::FUNCTION_COLUMN);
            $functionNames = $queryParameters[self::FUNCTION_COLUMN];

            Assertion::isArray(
                $functionNames,
                'Explicit function definition by values must be an array of functions. %s given.'
            );

            foreach ($functionNames as $functionName) {
                yield from $this->parseFunction($functionName);

                foreach ($this->functions->getParametersFor($functionName) as $parameter) {
                    $this->assertParameterExists($queryParameters, $parameter, $functionName);

                    yield from $this->parseFunctionParameter($parameter, $queryParameters[$parameter]);
                }
            }
        }
    }
}