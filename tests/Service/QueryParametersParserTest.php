<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterFunction;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filter\FunctionParameter;
use Lmc\ApiFilter\Filters\Filters;

class QueryParametersParserTest extends AbstractTestCase
{
    /** @var QueryParametersParser */
    private $queryParametersParser;
    /** @var Functions */
    private $functions;

    protected function setUp(): void
    {
        $this->functions = new Functions();

        $this->queryParametersParser = new QueryParametersParser(
            new FilterFactory(),
            $this->functions
        );
    }

    /**
     * @test
     * @dataProvider provideQueryParameters
     */
    public function shouldParseQueryParameters(
        array $queryParameters,
        array $expectedFilters,
        array $functionsToRegister = []
    ): void {
        foreach ($functionsToRegister as $function) {
            $this->functions->register(...$function);
        }

        $expectedFilters = Filters::from($expectedFilters);

        $result = $this->queryParametersParser->parse($queryParameters);

        $this->assertEquals($expectedFilters, $result);
    }

    public function provideQueryParameters(): array
    {
        return [
            // queryParameters, expectedFilters, functionsToRegister
            'empty' => [[], []],
            'simple - implicit eq' => [
                ['title' => 'foo'],
                [new FilterWithOperator('title', new Value('foo'), '=', 'eq')],
            ],
            'two cols - implicit eq' => [
                ['title' => 'foo', 'value' => 'bar'],
                [
                    new FilterWithOperator('title', new Value('foo'), '=', 'eq'),
                    new FilterWithOperator('value', new Value('bar'), '=', 'eq'),
                ],
            ],
            'implicit EQ + explicit filter' => [
                ['name' => 'Jon', 'age' => ['gt' => 20]],
                [
                    new FilterWithOperator('name', new Value('Jon'), '=', 'eq'),
                    new FilterWithOperator('age', new Value(20), '>', 'gt'),
                ],
            ],
            'explicit eq' => [
                ['title' => ['eq' => 'foo']],
                [new FilterWithOperator('title', new Value('foo'), '=', 'eq')],
            ],
            'one col more filters - explicit' => [
                ['title' => ['eq' => 'foo', 'gt' => 'abc']],
                [
                    new FilterWithOperator('title', new Value('foo'), '=', 'eq'),
                    new FilterWithOperator('title', new Value('abc'), '>', 'gt'),
                ],
            ],
            'one col more filters + other col - explicit/implicit' => [
                ['title' => ['gt' => '0', 'lt' => '10'], 'value' => 'foo'],
                [
                    new FilterWithOperator('title', new Value('0'), '>', 'gt'),
                    new FilterWithOperator('title', new Value('10'), '<', 'lt'),
                    new FilterWithOperator('value', new Value('foo'), '=', 'eq'),
                ],
            ],
            'one col - between - explicit' => [
                ['title' => ['gte' => '0', 'lte' => '10']],
                [
                    new FilterWithOperator('title', new Value('0'), '>=', 'gte'),
                    new FilterWithOperator('title', new Value('10'), '<=', 'lte'),
                ],
            ],
            'explicit in' => [
                ['color' => ['in' => ['red', 'green', 'blue']]],
                [
                    new FilterIn('color', new Value(['red', 'green', 'blue'])),
                ],
            ],
            'implicit eq + explicit in' => [
                ['allowed' => true, 'id' => ['in' => [1, 2, 3]]],
                [
                    new FilterWithOperator('allowed', new Value(true), '=', 'eq'),
                    new FilterIn('id', new Value([1, 2, 3])),
                ],
            ],
            'tuple - implicit eq + explicit in' => [
                ['(zone,bucket)' => '(lmc,all)', 'id' => ['in' => [1, 2, 3]]],
                [
                    new FilterWithOperator('zone', new Value('lmc'), '=', 'eq'),
                    new FilterWithOperator('bucket', new Value('all'), '=', 'eq'),
                    new FilterIn('id', new Value([1, 2, 3])),
                ],
            ],
            'tuple - between - explicit in values' => [
                ['(number,alpha)' => ['gte' => '(0, a)', 'lt' => '(10, z)']],
                [
                    new FilterWithOperator('number', new Value('0'), '>=', 'gte'),
                    new FilterWithOperator('alpha', new Value('a'), '>=', 'gte'),
                    new FilterWithOperator('number', new Value('10'), '<', 'lt'),
                    new FilterWithOperator('alpha', new Value('z'), '<', 'lt'),
                ],
            ],
            'tuple - between - explicit in columns' => [
                ['(age[gt],age[lt])' => '(18, 30)'],
                [
                    new FilterWithOperator('age', new Value(18), '>', 'gt'),
                    new FilterWithOperator('age', new Value(30), '<', 'lt'),
                ],
            ],
            'tuple - implicit eq + in' => [
                ['(name,size)' => '(foo, [DD; D])'],
                [
                    new FilterWithOperator('name', new Value('foo'), '=', 'eq'),
                    new FilterIn('size', new Value(['DD', 'D'])),
                ],
            ],
            'ints - between - explicit' => [
                ['age' => ['gt' => 18, 'lt' => 30]],
                [
                    new FilterWithOperator('age', new Value(18), '>', 'gt'),
                    new FilterWithOperator('age', new Value(30), '<', 'lt'),
                ],
            ],
            'explicit between + explicit in' => [
                ['age' => ['gt' => 18, 'lt' => 30], 'size' => ['in' => ['DD', 'D']]],
                [
                    new FilterWithOperator('age', new Value(18), '>', 'gt'),
                    new FilterWithOperator('age', new Value(30), '<', 'lt'),
                    new FilterIn('size', new Value(['DD', 'D'])),
                ],
            ],
            'tuple - explicit between + implicit in and eq in columns' => [
                ['(age[gte], age[lt], size, name)' => '(18, 30, [DD; D], "Jon Snow")'],
                [
                    new FilterWithOperator('age', new Value(18), '>=', 'gte'),
                    new FilterWithOperator('age', new Value(30), '<', 'lt'),
                    new FilterIn('size', new Value(['DD', 'D'])),
                    new FilterWithOperator('name', new Value('Jon Snow'), '=', 'eq'),
                ],
            ],
            'tuple - explicit between + implicit in and eq in columns + other implicit eq' => [
                ['(age[gte], age[lt], size, name)' => '(18, 30, [DD; D], "Jon Snow")', 'version' => 'latest'],
                [
                    new FilterWithOperator('age', new Value(18), '>=', 'gte'),
                    new FilterWithOperator('age', new Value(30), '<', 'lt'),
                    new FilterIn('size', new Value(['DD', 'D'])),
                    new FilterWithOperator('name', new Value('Jon Snow'), '=', 'eq'),
                    new FilterWithOperator('version', new Value('latest'), '=', 'eq'),
                ],
            ],
            'function - fullName' => [
                ['fullName' => '(Jon, Snow)'],
                [
                    new FilterFunction('fullName', new Value($this->createBlankCallback('fullName'))),
                    new FunctionParameter('firstName', new Value('Jon')),
                    new FunctionParameter('surname', new Value('Snow')),
                ],
                [
                    ['fullName', ['firstName', 'surname'], $this->createBlankCallback('fullName')],
                ],
            ],
            'function - perfectWife + spot + name' => [
                ['perfectWife' => '(18, 30, [DD; D])', '(zone,bucket)' => '(all,common)', 'name' => 'Jon'],
                [
                    new FilterFunction('perfectWife', new Value($this->createBlankCallback('perfectWife'))),
                    new FunctionParameter('ageFrom', new Value(18)),
                    new FunctionParameter('ageTo', new Value(30)),
                    new FunctionParameter('size', new Value(['DD', 'D'])),
                    new FilterFunction('spot', new Value($this->createBlankCallback('spot'))),
                    new FunctionParameter('zone', new Value('all')),
                    new FunctionParameter('bucket', new Value('common')),
                    new FilterWithOperator('name', new Value('Jon'), '=', 'eq'),
                ],
                [
                    ['perfectWife', ['ageFrom', 'ageTo', 'size'], $this->createBlankCallback('perfectWife')],
                    ['spot', ['zone', 'bucket'], $this->createBlankCallback('spot')],
                ],
            ],
            'explicit - function - perfectWife + spot + name' => [
                ['perfectWife' => '(18, 30, [DD; D])', '(fun,zone,bucket)' => '(spot,all,common)', 'name' => 'Jon'],
                [
                    new FilterFunction('perfectWife', new Value($this->createBlankCallback('perfectWife'))),
                    new FunctionParameter('ageFrom', new Value(18)),
                    new FunctionParameter('ageTo', new Value(30)),
                    new FunctionParameter('size', new Value(['DD', 'D'])),
                    new FilterFunction('spot', new Value($this->createBlankCallback('spot'))),
                    new FunctionParameter('zone', new Value('all')),
                    new FunctionParameter('bucket', new Value('common')),
                    new FilterWithOperator('name', new Value('Jon'), '=', 'eq'),
                ],
                [
                    ['perfectWife', ['ageFrom', 'ageTo', 'size'], $this->createBlankCallback('perfectWife')],
                    ['spot', ['zone', 'bucket'], $this->createBlankCallback('spot')],
                ],
            ],
            'implicit by values - function - perfectWife + spot + name' => [
                [
                    'ageFrom' => 18,
                    'ageTo' => 30,
                    'name' => 'Jon',
                    'size' => ['DD', 'D'],
                    'zone' => 'all',
                    'bucket' => 'common',
                ],
                [
                    new FilterFunction('perfectWife', new Value($this->createBlankCallback('perfectWife'))),
                    new FunctionParameter('ageFrom', new Value(18)),
                    new FunctionParameter('ageTo', new Value(30)),
                    new FunctionParameter('size', new Value(['DD', 'D'])),
                    new FilterFunction('spot', new Value($this->createBlankCallback('spot'))),
                    new FunctionParameter('zone', new Value('all')),
                    new FunctionParameter('bucket', new Value('common')),
                    new FilterWithOperator('name', new Value('Jon'), '=', 'eq'),
                ],
                [
                    ['perfectWife', ['ageFrom', 'ageTo', 'size'], $this->createBlankCallback('perfectWife')],
                    ['spot', ['zone', 'bucket'], $this->createBlankCallback('spot')],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidQueryParameters
     */
    public function shouldThrowInvalidArgumentExceptionOnUnknownFilter(
        array $queryParameters,
        string $expectedMessage
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->queryParametersParser->parse($queryParameters);
    }

    public function provideInvalidQueryParameters(): array
    {
        return [
            // queryParameters, expected message
            'empty filter' => [
                ['column' => ['' => 'value']],
                'Filter "" is not implemented. For column "column" with value "value".',
            ],
            'unknown filter' => [
                ['column' => ['unknown' => 'value']],
                'Filter "unknown" is not implemented. For column "column" with value "value".',
            ],
            'undefined function' => [
                ['function' => '(arg1, arg2)'],
                'Invalid combination of a tuple and a scalar. Column function and value (arg1, arg2).',
            ],
            'tuple columns and a single value' => [
                ['(col1, col2)' => 'value'],
                'Invalid combination of a tuple and a scalar. Column (col1, col2) and value value.',
            ],
            'more columns than values' => [
                ['(col1, col2, col3)' => '(val1, val2)'],
                'Number of given columns (3) and values (2) in tuple are not same.',
            ],
            'more values than columns' => [
                ['(col1, col2)' => '(val1, val2, val3)'],
                'Number of given columns (2) and values (3) in tuple are not same.',
            ],
            'invalid tuple - explicit filters' => [
                ['(id,name)' => ['eq' => '(42,foo,bar)']],
                'Number of given columns (2) and values (3) in tuple are not same.',
            ],
            'invalid tuple - filter definition in columns and values' => [
                ['(first[gt],second[lt])' => ['eq' => '(1,2)']],
                'Filters can be specified either in columns or in values - not in both',
            ],
            'tuples in IN filter' => [
                ['(id, name)' => ['in' => ['(1,one)', '(2,two)']]],
                'Tuples are not allowed in IN filter.',
            ],
        ];
    }
}
