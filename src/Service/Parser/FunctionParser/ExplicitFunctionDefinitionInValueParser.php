<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Assertion;

class ExplicitFunctionDefinitionInValueParser extends AbstractFunctionParser
{
    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    public function supportsParameters(array $queryParameters, string $rawColumn, $rawValue): bool
    {
        return $rawColumn === self::FUNCTION_COLUMN;
    }

    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    public function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable
    {
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

    private function isThereAnyExplicitFunctionDefinition(array $queryParameters): bool
    {
        return !$this->isColumnParsed(self::FUNCTION_COLUMN)
            && array_key_exists(self::FUNCTION_COLUMN, $queryParameters);
    }

    private function assertParameterExists(array $queryParameters, string $parameter, string $functionName): void
    {
        Assertion::keyExists(
            $queryParameters,
            $parameter,
            sprintf('There is a missing parameter %s for a function %s.', $parameter, $functionName)
        );
    }
}
