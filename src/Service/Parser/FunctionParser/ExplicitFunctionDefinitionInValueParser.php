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
        return array_key_exists(self::COLUMN_FUNCTION, $queryParameters);
    }

    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    public function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable
    {
        if ($this->isColumnParsed(self::COLUMN_FUNCTION)) {
            return;
        }

        $this->markColumnAsParsed(self::COLUMN_FUNCTION);
        $functionNames = $queryParameters[self::COLUMN_FUNCTION];

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

    private function assertParameterExists(array $queryParameters, string $parameter, string $functionName): void
    {
        Assertion::keyExists(
            $queryParameters,
            $parameter,
            sprintf('There is a missing parameter %s for a function %s.', $parameter, $functionName)
        );
    }
}
