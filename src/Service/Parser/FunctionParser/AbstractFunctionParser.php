<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Service\FilterFactory;
use Lmc\ApiFilter\Service\Functions;
use Lmc\ApiFilter\Service\Parser\AbstractParser;
use MF\Collection\Mutable\Generic\IMap;
use MF\Collection\Mutable\Generic\Map;

abstract class AbstractFunctionParser extends AbstractParser implements FunctionParserInterface
{
    protected const FUNCTION_COLUMN = 'fun';
    private const ERROR_MULTIPLE_FUNCTION_CALL = 'It is not allowed to call one function multiple times.';

    /** @var Functions */
    protected $functions;
    /** @var ?array */
    private $queryParameters;
    /** @var Map<string,bool>|IMap|null */
    protected $alreadyParsedFunctions;
    /** @var Map<string,bool>|IMap|null */
    protected $alreadyParsedColumns;

    public function __construct(FilterFactory $filterFactory, Functions $functions)
    {
        parent::__construct($filterFactory);
        $this->functions = $functions;
    }

    public function setCommonValues(
        array $queryParameters,
        IMap $alreadyParsedFunctions,
        IMap $alreadyParsedColumns
    ): void {
        $this->queryParameters = $queryParameters;
        $this->alreadyParsedFunctions = $alreadyParsedFunctions;
        $this->alreadyParsedColumns = $alreadyParsedColumns;
    }

    protected function assertQueryParameters(): array
    {
        Assertion::notNull($this->queryParameters, 'Query parameters must be set to FunctionParser.');

        return $this->queryParameters;
    }

    protected function isThereAnyExplicitFunctionDefinition(array $queryParameters): bool
    {
        return !$this->isParsed(self::FUNCTION_COLUMN)
            && array_key_exists(self::FUNCTION_COLUMN, $queryParameters);
    }

    protected function isParsed(string $column): bool
    {
        return $this->alreadyParsedColumns !== null
            && $this->alreadyParsedColumns->containsKey($column);
    }

    protected function markColumnAsParsed(string $column): void
    {
        Assertion::notNull($this->alreadyParsedColumns, 'Already parsed query parameters must be set before parsing.');
        $this->alreadyParsedColumns[$column] = true;
    }

    protected function assertParameterExists(array $queryParameters, string $parameter, string $functionName): void
    {
        Assertion::keyExists(
            $queryParameters,
            $parameter,
            sprintf('There is a missing parameter %s for a function %s.', $parameter, $functionName)
        );
    }

    protected function parseFunction(string $functionName): iterable
    {
        Assertion::true($this->alreadyParsedFunctions->containsKey($functionName), self::ERROR_MULTIPLE_FUNCTION_CALL);

        $this->alreadyParsedFunctions[$functionName] = true;

        yield $this->createFilter(
            $functionName,
            Filter::FUNCTION,
            $this->functions->getFunction($functionName)
        );
    }

    protected function parseFunctionParameter(string $parameter, $value): iterable
    {
        if (!$this->isParsed($parameter)) {
            $this->alreadyParsedColumns[$parameter] = true;

            yield $this->createFilter($parameter, Filter::FUNCTION_PARAMETER, $value);
        }
    }
}
