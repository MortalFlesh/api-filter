<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;

class FilterFactoryTest extends AbstractTestCase
{
    private const COLUMN = 'column';

    /** @var FilterFactory */
    private $filterFactory;

    protected function setUp(): void
    {
        $this->filterFactory = new FilterFactory();
    }

    /**
     * @param mixed $rawValue of type <T>
     * @param mixed $expectedValue of type <T>
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
        $result = $this->filterFactory->createFilter(self::COLUMN, $filter, new Value($rawValue));

        $this->assertInstanceOf(FilterInterface::class, $result);
        $this->assertSame(self::COLUMN, $result->getColumn());
        $this->assertSame($expectedValue, $result->getValue()->getValue());
        $this->assertSame($expectedTitle, $result->getTitle());
        $this->assertInstanceOf($expectedFilterClass, $result);
    }

    public function provideFilters(): array
    {
        return [
            // filter, expectedTitle, rawValue, expectedValue, expectedFilterClass
            'eq' => ['eq', self::COLUMN . '_eq', 'value', 'value', FilterWithOperator::class],
            'gt' => ['gt', self::COLUMN . '_gt', 'value', 'value', FilterWithOperator::class],
            'gte' => ['gte', self::COLUMN . '_gte', 'value', 'value', FilterWithOperator::class],
            'lt' => ['lt', self::COLUMN . '_lt', 'value', 'value', FilterWithOperator::class],
            'lte' => ['lte', self::COLUMN . '_lte', 'value', 'value', FilterWithOperator::class],
            'in' => ['in', self::COLUMN . '_in', 'value', ['value'], FilterIn::class],
            // by upper case filter
            'eq - upper case' => ['EQ', self::COLUMN . '_eq', 'value', 'value', FilterWithOperator::class],
            'gt - upper case' => ['GT', self::COLUMN . '_gt', 'value', 'value', FilterWithOperator::class],
            'gte - upper case' => ['GTE', self::COLUMN . '_gte', 'value', 'value', FilterWithOperator::class],
            'lt - upper case' => ['LT', self::COLUMN . '_lt', 'value', 'value', FilterWithOperator::class],
            'lte - upper case' => ['LTE', self::COLUMN . '_lte', 'value', 'value', FilterWithOperator::class],
            'in - upper case' => ['IN', self::COLUMN . '_in', 'value', ['value'], FilterIn::class],
        ];
    }

    /**
     * @test
     */
    public function shouldNotCreateUnknownFilter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter "unknown" is not implemented. For column "column" with value "foo".');

        $this->filterFactory->createFilter(self::COLUMN, 'unknown', new Value('foo'));
    }
}
