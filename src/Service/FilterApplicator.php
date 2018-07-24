<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filters\FiltersInterface;

class FilterApplicator
{
    /** @var Storage|ApplicatorInterface[] */
    private $applicators;

    public function __construct()
    {
        $this->applicators = new Storage(ApplicatorInterface::class);
    }

    public function registerApplicator(ApplicatorInterface $applicator, int $priority): void
    {
        $this->applicators->addItem($applicator, $priority);
    }

    public function apply(FilterInterface $filter, Filterable $filterable): Filterable
    {
        return $this
            ->findApplicatorFor($filterable)
            ->applyTo($filter, $filterable);
    }

    private function findApplicatorFor(Filterable $filterable): ApplicatorInterface
    {
        foreach ($this->applicators as $applicator) {
            if ($applicator->supports($filterable)) {
                return $applicator;
            }
        }

        $filterableValue = $filterable->getValue();
        throw new \InvalidArgumentException(
            sprintf(
                'Unsupported filterable of type "%s".',
                is_object($filterableValue) ? get_class($filterableValue) : gettype($filterableValue)
            )
        );
    }

    public function getPreparedValue(FilterInterface $filter, Filterable $filterable): array
    {
        [$column, $value] = $this
            ->findApplicatorFor($filterable)
            ->getPreparedValue($filter);

        return [$column => $value];
    }

    public function applyAll(FiltersInterface $filters, Filterable $filterable): Filterable
    {
        return $filters->applyAllTo($filterable, $this);
    }

    public function getPreparedValues(FiltersInterface $filters, Filterable $filterable): array
    {
        return $filters->getPreparedValues($this->findApplicatorFor($filterable));
    }
}
