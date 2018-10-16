<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\QueryBuilder;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
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
            $this->assertSame($expectedDqlWhere, $where->getParts());
        }
    }

    protected function createBlankCallback(string $name): callable
    {
        return function () use ($name) {
            throw new \Exception(sprintf('Function "%s" is not meant to be called.', $name));
        };
    }
}
