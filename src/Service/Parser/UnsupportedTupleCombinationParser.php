<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

class UnsupportedTupleCombinationParser extends AbstractParser
{
    public function supports($rawColumn, $rawValue): bool
    {
        return $this->isTuple($rawColumn) || $this->isTuple($rawValue);
    }

    public function parse($rawColumn, $rawValue): iterable
    {
        throw new \InvalidArgumentException(sprintf(
            'Invalid combination of a tuple and a scalar. Column %s and value %s.',
            $this->formatForException($rawColumn),
            $this->formatForException($rawValue)
        ));
    }

    private function formatForException($value): string
    {
        if (is_array($value)) {
            $formatted = [];
            foreach ($value as $key => $val) {
                $formatted[] = is_string($key)
                    ? sprintf('%s => %s', $key, $this->formatForException($val))
                    : $this->formatForException($val);
            }
            $value = sprintf('[%s]', implode(', ', $formatted));
        }

        return (string) $value;
    }
}
