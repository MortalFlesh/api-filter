<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;

class FilterFactoryTest extends AbstractTestCase
{
    /** @var FilterFactory */
    private $filterFactory;

    protected function setUp(): void
    {
        $this->filterFactory = new FilterFactory();
    }

    /**
     * @param mixed $rawValue
     * @param mixed $expectedValue
     *
     * @test
     * @dataProvider provideFilters
     */
    public function shouldCreateFilter(
        string $filter,
        string $expectedTitle,
        $rawValue,
        $expectedValue,
        string $expectedFilterClass
    ): void {
        $column = 'column';

        $result = $this->filterFactory->createFilter($column, $filter, new Value($rawValue));

        $this->assertInstanceOf(FilterInterface::class, $result);
        $this->assertSame($column, $result->getColumn());
        $this->assertSame($expectedValue, $result->getValue()->getValue());
        $this->assertSame($expectedTitle, $result->getTitle());
        $this->assertInstanceOf($expectedFilterClass, $result);
    }

    public function provideFilters(): array
    {
        return [
            // filter, expectedTitle, expectedFilterClass
            'eq' => ['eq', 'eq', 'value', 'value', FilterWithOperator::class],
            'gt' => ['gt', 'gt', 'value', 'value', FilterWithOperator::class],
            'gte' => ['gte', 'gte', 'value', 'value', FilterWithOperator::class],
            'lt' => ['lt', 'lt', 'value', 'value', FilterWithOperator::class],
            'lte' => ['lte', 'lte', 'value', 'value', FilterWithOperator::class],
            'in' => ['in', 'in', 'value', ['value'], FilterIn::class],
            // by upper case filter
            'eq - upper case' => ['EQ', 'eq', 'value', 'value', FilterWithOperator::class],
            'gt - upper case' => ['GT', 'gt', 'value', 'value', FilterWithOperator::class],
            'gte - upper case' => ['GTE', 'gte', 'value', 'value', FilterWithOperator::class],
            'lt - upper case' => ['LT', 'lt', 'value', 'value', FilterWithOperator::class],
            'lte - upper case' => ['LTE', 'lte', 'value', 'value', FilterWithOperator::class],
            'in - upper case' => ['IN', 'in', 'value', ['value'], FilterIn::class],
        ];
    }

    /**
     * @test
     */
    public function shouldNotCreateUnknownFilter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter "unknown" is not implemented. For column "column" with value "foo".');

        $this->filterFactory->createFilter('column', 'unknown', new Value('foo'));
    }
}
