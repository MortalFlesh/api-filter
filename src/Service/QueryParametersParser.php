<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Filters\Filters;
use Lmc\ApiFilter\Filters\FiltersInterface;
use Lmc\ApiFilter\Service\Parser\FunctionParser;
use Lmc\ApiFilter\Service\Parser\ParserInterface;
use Lmc\ApiFilter\Service\Parser\SingleColumnArrayValueParser;
use Lmc\ApiFilter\Service\Parser\SingleColumnSingleValueParser;
use Lmc\ApiFilter\Service\Parser\TupleColumnArrayValueParser;
use Lmc\ApiFilter\Service\Parser\TupleColumnTupleValueParser;
use Lmc\ApiFilter\Service\Parser\UnsupportedTupleCombinationParser;
use MF\Collection\Mutable\Generic\PrioritizedCollection;

class QueryParametersParser
{
    /** @var PrioritizedCollection|ParserInterface[] */
    private $parsers;
    /** @var FunctionParser */
    private $functionParser;

    public function __construct(FilterFactory $filterFactory, Functions $functions)
    {
        $this->functionParser = new FunctionParser($filterFactory, $functions);

        $this->parsers = new PrioritizedCollection(ParserInterface::class);
        $this->parsers->add($this->functionParser, 6);
        $this->parsers->add(new TupleColumnTupleValueParser($filterFactory), 5);
        $this->parsers->add(new TupleColumnArrayValueParser($filterFactory), 4);
        $this->parsers->add(new UnsupportedTupleCombinationParser($filterFactory), 3);
        $this->parsers->add(new SingleColumnArrayValueParser($filterFactory), 2);
        $this->parsers->add(new SingleColumnSingleValueParser($filterFactory), 1);
    }

    public function parse(array $queryParameters): FiltersInterface
    {
        $filters = new Filters();
        foreach ($this->parseFilters($queryParameters) as $filter) {
            $filters->addFilter($filter);
        }

        return $filters;
    }

    private function parseFilters(array $queryParameters): iterable
    {
        $this->functionParser->setQueryParameters($queryParameters);

        foreach ($queryParameters as $rawColumn => $rawValue) {
            foreach ($this->parsers as $parser) {
                if ($parser->supports($rawColumn, $rawValue)) {
                    yield from $parser->parse($rawColumn, $rawValue);

                    // continue to next query parameter
                    continue 2;
                }
            }
        }
    }
}
