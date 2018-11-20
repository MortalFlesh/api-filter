<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Assert\Assertion;
use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Service\FilterFactory;
use Lmc\ApiFilter\Service\Functions;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionFunctionParser;
use Lmc\ApiFilter\Service\Parser\FunctionParser\FunctionParserInterface;
use MF\Collection\Immutable\Tuple;
use MF\Collection\Mutable\Generic\Map;
use MF\Collection\Mutable\Generic\PrioritizedCollection;

class FunctionParser extends AbstractParser
{
    private const FUNCTION_COLUMN = 'fun';
    private const ERROR_MULTIPLE_FUNCTION_CALL = 'It is not allowed to call one function multiple times.';

    /** @var bool */
    private $isQueryParametersSet = false;
    /** @var ?bool */
    private $isAllImplicitFunctionDefinitionsChecked;
    /** @var PrioritizedCollection|FunctionParserInterface[] */
    private $parsers;

    public function __construct(FilterFactory $filterFactory, Functions $functions)
    {
        parent::__construct($filterFactory);

        $this->parsers = new PrioritizedCollection(FunctionParserInterface::class);
        $this->parsers->add(
            new ExplicitFunctionDefinitionFunctionParser($filterFactory, $functions),
            Priority::HIGHEST
        );
    }

    public function setQueryParameters(array $queryParameters): void
    {
        $this->isQueryParametersSet = true;
        $alreadyParsedFunctions = new Map('string', 'bool');
        $alreadyParsedQueryParameters = new Map('string', 'bool');
        $this->isAllImplicitFunctionDefinitionsChecked = false;

        foreach ($this->parsers as $parser) {
            $parser->setCommonValues($queryParameters, $alreadyParsedFunctions, $alreadyParsedQueryParameters);
        }
    }

    public function supports(string $rawColumn, $rawValue): bool
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($rawColumn, $rawValue)) {
                return true;
            }
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
                if (!array_key_exists($parameter, $queryParameters)) {
                    // check next function
                    continue 2;
                }
            }

            // at least one function has all parameters -> no more searching is required
            return true;
        }

        return false;
    }

    public function parse(string $rawColumn, $rawValue): iterable
    {
        foreach($this->parsers as $parser) {
            if ($parser->supports($rawColumn, $rawValue)) {
                yield from $parser->parse($rawColumn, $rawValue);
            }
        }

        // all explicit function definitions by values
        if ($this->isThereAnyExplicitFunctionDefinition($queryParameters)) {
            $this->alreadyParsedQueryParameters[self::FUNCTION_COLUMN] = true;
            $functionNames = $queryParameters[self::FUNCTION_COLUMN];

            Assertion::isArray(
                $functionNames,
                'Explicit function definition by values must be an array of functions. %s given.'
            );

            foreach ($functionNames as $functionName) {
                yield from $this->parseFunction($functionName);

                foreach ($this->functions->getParametersFor($functionName) as $parameter) {
                    Assertion::keyExists(
                        $queryParameters,
                        $parameter,
                        sprintf('There is a missing parameter %s for a function %s.', $parameter, $functionName)
                    );

                    yield from $this->parseFunctionParameter($parameter, $queryParameters[$parameter]);
                }
            }
        }

        // all implicit function definitions by values
        if (!$this->isAllImplicitFunctionDefinitionsChecked) {
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

        // explicit function definitions
        if ($this->functions->isFunctionRegistered($rawColumn)) {
            yield from $this->parseFunction($rawColumn);

            $parameters = $this->functions->getParametersFor($rawColumn);
            if (count($parameters) === 1) {
                Assertion::false(
                    $this->isTuple($rawValue) || is_array($rawValue),
                    'A single parameter function definition must have a single value.'
                );

                yield from $this->parseFunctionParameter(array_shift($parameters), $rawValue);
            } else {
                Assertion::true($this->isTuple($rawValue), 'Direct function definition must have a tuple value.');

                $values = Tuple::parse($rawValue, count($parameters))->toArray();
                foreach ($parameters as $parameter) {
                    yield from $this->parseFunctionParameter($parameter, array_shift($values));
                }
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

    private function isThereAnyExplicitFunctionDefinition(array $queryParameters): bool
    {
        return !$this->isParsed(self::FUNCTION_COLUMN)
            && array_key_exists(self::FUNCTION_COLUMN, $queryParameters);
    }

    private function isParsed(string $key): bool
    {
        return $this->alreadyParsedQueryParameters !== null
            && $this->alreadyParsedQueryParameters->containsKey($key);
    }

    private function parseFunction(string $functionName): iterable
    {
        Assertion::true($this->alreadyParsedFunctions->containsKey($functionName), self::ERROR_MULTIPLE_FUNCTION_CALL);

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
