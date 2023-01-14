<?php

namespace abc\core\lib;


class BaseIncentiveBonus
{
    protected string $key;
    protected string $section;

    protected array $data = [];

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSection(): string
    {
        return $this->section;
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

    public function getBonus(CheckoutBase $checkout, ?array $params): float|int
    {
        return 0.0;
    }

    /**
     * @param $operator
     * @param $price
     * @param $value
     * @return float|int
     */
    public function calculateDiscount($operator, $price, $value)
    {

        $discount = 0.0;

        if ($operator == 'to_prc') {
            $discount = $price * (100 - $value) / 100;
        } elseif ($operator == 'by_prc') {
            $discount = $price * $value / 100;
        } elseif ($operator == 'to_fixed') {
            $discount = $price - $value;
        } elseif ($operator == 'by_fixed') {
            $discount = $value;
        }

        if ($discount < 0) {
            $discount = 0.0;
        }

        return $discount;
    }
}