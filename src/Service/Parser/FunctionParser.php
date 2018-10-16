<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Assert\Assertion;
use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Service\FilterFactory;
use Lmc\ApiFilter\Service\Functions;
use MF\Collection\Immutable\Tuple;

class FunctionParser extends AbstractParser
{
    private const FUNCTION_COLUMN = 'fun';

    private const ERROR_MULTIPLE_FUNCTION_CALL = 'It is not allowed to call one function multiple times.';

    /** @var Functions */
    private $functions;
    /** @var ?array */
    private $queryParameters;
    /** @var ?array<string,bool> */
    private $alreadyParsedFunctions;
    /** @var ?array<string,bool> */
    private $alreadyParsedQueryParameters;
    /** @var ?bool */
    private $isAllImplicitFunctionDefinitionsChecked;

    public function __construct(FilterFactory $filterFactory, Functions $functions)
    {
        parent::__construct($filterFactory);
        $this->functions = $functions;
    }

    public function setQueryParameters(array $queryParameters): void
    {
        $this->queryParameters = $queryParameters;
        $this->alreadyParsedFunctions = [];
        $this->alreadyParsedQueryParameters = [];
        $this->isAllImplicitFunctionDefinitionsChecked = false;
    }

    public function supports($rawColumn, $rawValue): bool
    {
        $this->assertQueryParameters();

        // is a function column for explicit function definition by values
        if ($rawColumn === self::FUNCTION_COLUMN) {
            return true;
        }

        // is a function definition
        if (!$this->isTuple($rawColumn) && $this->functions->isFunctionRegistered($rawColumn)) {
            return true;
        }

        // is a function definition by tuple
        if ($this->isTuple($rawColumn)) {
            $tuple = Tuple::parse($rawColumn);

            // is an explicit function definition by tuple
            if ($tuple->first() === self::FUNCTION_COLUMN) {
                return true;
            }

            // is an implicit function definition by tuple
            foreach ($this->functions->getFunctionNamesByAllParameters($tuple->toArray()) as $functionName) {
                return true;
            }

            return false;
        }

        // is an implicit function definition by value
        foreach ($this->functions->getFunctionNamesByParameter($rawColumn) as $functionName) {
            // - are there all parameters for at least one of the functions?
            foreach ($this->functions->getParametersFor($functionName) as $parameter) {
                if (!array_key_exists($parameter, $this->queryParameters)) {
                    // check next function
                    continue 2;
                }
            }

            // at least one function has all parameters -> no more searching is required
            return true;
        }

        return false;
    }

    private function assertQueryParameters(): void
    {
        Assertion::notNull($this->queryParameters, 'Query parameters must be set to FunctionParser.');
    }

    public function parse($rawColumn, $rawValue): iterable
    {
        $this->assertQueryParameters();

        // all explicit function definitions by values
        if ($this->isThereAnyExplicitFunctionDefinition()) {
            $this->alreadyParsedQueryParameters[self::FUNCTION_COLUMN] = true;
            $functionNames = $this->queryParameters[self::FUNCTION_COLUMN];

            Assertion::isArray(
                $functionNames,
                'Explicit function definition by values must be an array of functions. %s given.'
            );

            foreach ($functionNames as $functionName) {
                yield from $this->parseFunction($functionName);

                foreach ($this->functions->getParametersFor($functionName) as $parameter) {
                    Assertion::keyExists(
                        $this->queryParameters,
                        $parameter,
                        sprintf('There is a missing parameter %s for a function %s.', $parameter, $functionName)
                    );

                    yield from $this->parseFunctionParameter($parameter, $this->queryParameters[$parameter]);
                }
            }
        }

        // all implicit function definitions by values
        if (!$this->isAllImplicitFunctionDefinitionsChecked) {
            $this->isAllImplicitFunctionDefinitionsChecked = true;

            foreach ($this->queryParameters as $column => $value) {
                if ($this->isParsed($column)) {
                    continue;
                }

                foreach ($this->functions->getFunctionNamesByParameter($column) as $functionName) {
                    $parameters = $this->functions->getParametersFor($functionName);
                    foreach ($parameters as $parameter) {
                        if (!array_key_exists($parameter, $this->queryParameters)) {
                            // skip all incomplete functions
                            continue 2;
                        }
                    }

                    yield from $this->parseFunction($functionName);
                    foreach ($parameters as $parameter) {
                        yield from $this->parseFunctionParameter($parameter, $this->queryParameters[$parameter]);
                    }
                }
            }
        }

        // explicit function definitions
        if ($this->functions->isFunctionRegistered($rawColumn)) {
            Assertion::true($this->isTuple($rawValue), 'Direct function definition must have a tuple value.');

            yield from $this->parseFunction($rawColumn);

            $parameters = $this->functions->getParametersFor($rawColumn);
            $values = Tuple::parse($rawValue, count($parameters))->toArray();
            foreach ($parameters as $parameter) {
                yield from $this->parseFunctionParameter($parameter, array_shift($values));
            }
        }

        // function definition by tuple
        if ($this->isTuple($rawColumn)) {
            Assertion::true($this->isTuple($rawValue), 'Function definition by a tuple must have a tuple value.');
            $columns = Tuple::parse($rawColumn);
            $values = Tuple::parse($rawValue, count($columns))->toArray();

            // explicit function definition by tuple
            if ($columns->first() === self::FUNCTION_COLUMN) {
                $columns = $columns->toArray();

                array_shift($columns);  // just get rid of the first parameter
                $functionName = array_shift($values);

                yield from $this->parseFunction($functionName);
                foreach ($this->functions->getParametersFor($functionName) as $parameter) {
                    yield from $this->parseFunctionParameter($parameter, array_shift($values));
                }
            } else {
                // implicit function definition by tuple
                $columns = $columns->toArray();

                foreach ($this->functions->getFunctionNamesByAllParameters($columns) as $functionName) {
                    yield from $this->parseFunction($functionName);
                }

                foreach ($columns as $parameter) {
                    yield from $this->parseFunctionParameter($parameter, array_shift($values));
                }
            }
        }
    }

    private function isThereAnyExplicitFunctionDefinition(): bool
    {
        return !$this->isParsed(self::FUNCTION_COLUMN)
            && array_key_exists(self::FUNCTION_COLUMN, $this->queryParameters);
    }

    private function isParsed(string $key): bool
    {
        return array_key_exists($key, $this->alreadyParsedQueryParameters);
    }

    private function parseFunction(string $functionName): iterable
    {
        Assertion::keyNotExists($this->alreadyParsedFunctions, $functionName, self::ERROR_MULTIPLE_FUNCTION_CALL);

        $this->alreadyParsedFunctions[$functionName] = true;

        yield $this->createFilter(
            $functionName,
            Filter::FUNCTION,
            $this->functions->getFunction($functionName)
        );
    }

    private function parseFunctionParameter(string $parameter, $value): iterable
    {
        if (!$this->isParsed($parameter)) {
            $this->alreadyParsedQueryParameters[$parameter] = true;

            yield $this->createFilter($parameter, Filter::FUNCTION_PARAMETER, $value);
        }
    }
}
