<?php

namespace abc\core\lib;


class BaseIncentiveCondition
{
    protected string $relatedTo;
    protected array $incentiveInfo = [];
    protected string $key;
    protected string $section;

    protected array $data = [];

    /**
     * We should to store incentive details for probable usage inside Conditions and bonuses,
     * such as date range, number of usage etc.
     * @param array $info
     * @return void
     */
    public function setIncentiveInfo(array $info)
    {
        $this->incentiveInfo = $info;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function getRelatedTo(): string
    {
        return $this->relatedTo;
    }

    public function getSubFormLabel(): string
    {
        return $this->getName();
    }

    public function getName($languageId = null): string
    {
        return 'basicIncentiveCondition';
    }

    public function renderSubForm(array $inData, ?string $adminTpl = ''): array
    {
        return [];
    }

    /**
     * @param CheckoutBase|array $checkout
     * @param array|null $params
     * @return bool
     */
    public function check(CheckoutBase|array $checkout, ?array $params = []): bool
    {
        return false;
    }

    public function matchedItems()
    {
        return $this->data['matchedItems'];
    }

    /**
     * @param mixed $value1
     * @param mixed $value2
     * @param string $operator
     *
     * @return bool
     */
    protected function runComparison($value1, $value2, $operator)
    {
        switch ($operator) {
            // equal
            case 'eq':
                return ($value1 == $value2);
            //not equal
            case 'neq':
                return ($value1 != $value2);
            // equal or less
            case 'eqlt':
                return ($value1 <= $value2);
            //equal or greater
            case 'eqgt':
                return ($value1 >= $value2);
            //less
            case 'lt':
                return ($value1 < $value2);
            //greater
            case 'gt':
                return ($value1 > $value2);
            //in list
            case 'in':
                $value2 = (array)$value2;
                if (!is_array($value1)) {
                    return in_array($value1, $value2);
                } else {
                    return (bool)array_intersect($value1, $value2);
                }
            // not in list
            case 'notin':
                $value2 = (array)$value2;
                if (!is_array($value1)) {
                    return !in_array($value1, $value2);
                } else {
                    return !array_intersect($value1, $value2);
                }
            // contains
            case 'ctn':
                return str_contains((string)$value1, (string)$value2);
            //not contains
            case 'nctn':
                return !str_contains((string)$value1, (string)$value2);
            default:
                return false;
        }
    }
}