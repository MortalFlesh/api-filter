<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use MF\Collection\Mutable\Generic\IMap;

class FunctionDefinitionParser extends AbstractFunctionParser
{
    /** @var ?bool */
    private $isAllImplicitFunctionDefinitionsChecked;

    public function setCommonValues(
        array $queryParameters,
        IMap $alreadyParsedFunctions,
        IMap $alreadyParsedColumns
    ): void {
        parent::setCommonValues($queryParameters, $alreadyParsedFunctions, $alreadyParsedColumns);
        $this->isAllImplicitFunctionDefinitionsChecked = false;
    }

    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    public function supports(string $rawColumn, $rawValue): bool
    {
        return !$this->isTuple($rawColumn) && $this->functions->isFunctionRegistered($rawColumn);
    }

    /**
     * @param string|array $rawValue Raw column value from query parameters
     */
    public function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable
    {
        if ($this->isAllImplicitFunctionDefinitionsChecked) {
            return;
        }

        $this->isAllImplicitFunctionDefinitionsChecked = true;

        foreach ($queryParameters as $column => $value) {
            if ($this->isParsed($column)) {
                continue;
            }

            foreach ($this->functions->getFunctionNamesByParameter($column) as $functionName) {
                $parameters = $this->functions->getParametersFor($functionName);
                foreach ($parameters as $parameter) {
                    if (!array_key_exists($parameter, $queryParameters)) {
                        // skip all incomplete functions
                        continue 2;
                    }
                }

                yield from $this->parseFunction($functionName);
                foreach ($parameters as $parameter) {
                    yield from $this->parseFunctionParameter($parameter, $queryParameters[$parameter]);
                }
            }
        }
    }
}
