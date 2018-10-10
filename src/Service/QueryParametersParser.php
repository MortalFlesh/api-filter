<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Filters\Filters;
use Lmc\ApiFilter\Filters\FiltersInterface;
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

    public function __construct(FilterFactory $filterFactory)
    {
        $this->parsers = new PrioritizedCollection(ParserInterface::class);
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

    public function parseFilters(array $queryParameters): iterable
    {
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
