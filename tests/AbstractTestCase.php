<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Service\FilterFactory;
use Lmc\ApiFilter\Service\Parser\Fixtures\SimpleFilter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUpQueryBuilder($alias = 't'): QueryBuilder
    {
        $queryBuilder = new QueryBuilder(m::mock(EntityManagerInterface::class));
        $queryBuilder->from('table', $alias);

        return $queryBuilder;
    }

    protected function assertDqlWhere(?array $expectedDqlWhere, QueryBuilder $queryBuilder): void
    {
        /** @var Andx $where */
        $where = $queryBuilder->getDQLPart('where');

        if ($expectedDqlWhere === null) {
            $this->assertNull($where);
        } else {
            $this->assertSameValues($expectedDqlWhere, $where->getParts());
        }
    }

    protected function assertSameValues(array $expected, array $actual): void
    {
        if (!empty($expected)) {
            [$key] = array_keys($expected);
            if (is_int($key)) {
                sort($expected);
                sort($actual);
            } else {
                asort($expected);
                asort($actual);
            }
        }

        $this->assertSame($expected, $actual);
    }

    protected function mockFilterFactory(): FilterFactory
    {
        $filterFactory = m::mock(FilterFactory::class);
        $filterFactory->shouldReceive('createFilter')
            ->andReturnUsing(function (string $column, string $filter, Value $value) {
                return new SimpleFilter($column, $filter, $value->getValue());
            });

        return $filterFactory;
    }

    protected function createBlankCallback(string $name): callable
    {
        return function () use ($name): void {
            throw new \Exception(sprintf('Function "%s" is not meant to be called.', $name));
        };
    }
}
