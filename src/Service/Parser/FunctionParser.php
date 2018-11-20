<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Assert\Assertion;
use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Service\FilterFactory;
use Lmc\ApiFilter\Service\Functions;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionByTupleParser;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionInValueParser;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionParser;
use Lmc\ApiFilter\Service\Parser\FunctionParser\FunctionParserInterface;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ImplicitFunctionDefinitionByTupleParser;
use MF\Collection\Immutable\Tuple;
use MF\Collection\Mutable\Generic\Map;
use MF\Collection\Mutable\Generic\PrioritizedCollection;

class FunctionParser extends AbstractParser
{
    private const FUNCTION_COLUMN = 'fun';
    private const ERROR_MULTIPLE_FUNCTION_CALL = 'It is not allowed to call one function multiple times.';

    /** @var bool */
    private $isQueryParametersSet = false;
    /** @var PrioritizedCollection|FunctionParserInterface[] */
    private $parsers;

    public function __construct(FilterFactory $filterFactory, Functions $functions)
    {
        parent::__construct($filterFactory);

        $this->parsers = new PrioritizedCollection(FunctionParserInterface::class);
        $this->parsers->add(
            new ExplicitFunctionDefinitionInValueParser($filterFactory, $functions),
            Priority::HIGHEST
        );
        $this->parsers->add(new ExplicitFunctionDefinitionParser($filterFactory, $functions), Priority::HIGHER);
        $this->parsers->add(new ExplicitFunctionDefinitionByTupleParser($filterFactory, $functions), Priority::LOW);
        $this->parsers->add(new ImplicitFunctionDefinitionByTupleParser($filterFactory, $functions), Priority::LOWER);
    }

    public function setQueryParameters(array $queryParameters): void
    {
        $this->isQueryParametersSet = true;
        $alreadyParsedFunctions = new Map('string', 'bool');
        $alreadyParsedQueryParameters = new Map('string', 'bool');

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

        // 4 is an implicit function definition by value
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
        foreach ($this->parsers as $parser) {
            if ($parser->supports($rawColumn, $rawValue)) {
                yield from $parser->parse($rawColumn, $rawValue);
            }
        }

        // 2? explicit function definitions
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
