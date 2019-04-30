<?php

namespace abc\modules\reports;

use abc\core\ABC;
use abc\models\customer\Customer;
use stdClass;

class CustomerOverviewReport extends BaseReport implements BaseReportInterface
{

    public function getName()
    {
        return 'Customer Overview';
    }

    public function getGridSortName()
    {
        return 'customers.customer_id';
    }

    public function getGridColNames()
    {
        return [
            'customer_id',
            'account_code',
            'loginname',
            'company',
            'firstname',
            'lastname',
            'postcode',
            'customer_type',
            'date_added',
            'advanced_status',
            'consolidation_status_text',
            'children_count',
        ];
    }

    public function getGridColModel()
    {
        return [
            [
                'name'     => 'customer_id',
                'index'    => 'customers.customer_id',
                'align'    => 'center',
                'width'    => 30,
                'sortable' => true,
                'search'   => true,
            ],
            [
                'name'     => 'account_code',
                'index'    => 'customers.account_code',
                'width'    => 30,
                'align'    => 'left',
                'sortable' => true,
                'search'   => true,
            ],
            [
                'name'     => 'loginname',
                'index'    => 'customers.loginname',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => true,
                'search'   => true,
            ],
            [
                'name'     => 'company',
                'index'    => 'addresses.company',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => true,
                'search'   => true,
            ],
            [
                'name'     => 'firstname',
                'index'    => 'customers.firstname',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => true,
                'search'   => true,
            ],
            [
                'name'     => 'lastname',
                'index'    => 'customers.lastname',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => true,
                'search'   => true,
            ],
            [
                'name'     => 'postcode',
                'index'    => 'addresses.postcode',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => true,
                'search'   => true,
            ],
            [
                'name'     => 'customer_type',
                'index'    => 'customers.customer_type',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => true,
                'search'   => true,
            ],
            [
                'name'     => 'date_added',
                'index'    => 'customers.date_added',
                'width'    => 50,
                'align'    => 'center',
                'search'   => false,
                'sortable' => true,
            ],
            [
                'name'     => 'advanced_status',
                'index'    => 'customers.advanced_status',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => true,
                'search'   => true,
            ],
            [
                'name'     => 'consolidation_status_text',
                'index'    => 'consolidation_status_text',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => true,
                'search'   => false,
            ],
            [
                'name'     => 'children_count',
                'index'    => 'children_count',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => true,
                'search'   => false,
            ],
        ];
    }

    public function getGridData(array $get, array $post, bool $export = null)
    {
        $arSelect = [
            $this->db->raw('SQL_CALC_FOUND_ROWS '.$this->db->table_name('customers').'.customer_id'),
            'customers.account_code',
            'customers.loginname',
            'addresses.company',
            'customers.firstname',
            'customers.lastname',
            'addresses.postcode',
            'customers.customer_type',
            'customers.date_added',
            'customers.advanced_status',
            $this->db->raw("CASE ".$this->db->table_name('customers').".consolidation_status
                            WHEN  '1' THEN 'independent'
                            WHEN '2' THEN 'parent'
                            WHEN '3' THEN 'child' 
                            END as consolidation_status_text"),
            $this->db->raw('(SELECT COUNT(*) FROM '.$this->db->table_name('customers').' ch
                            WHERE ch.parent_id = '.$this->db->table_name('customers').'.customer_id)
                            as children_count'),
        ];

        try {
            $results = Customer::select($arSelect)
                ->leftJoin('addresses', 'addresses.customer_id', '=', 'customers.customer_id')
                ->active('customers');

            if (isset($post['rows']) && isset($post['page']) && !$export) {
                $results = $results->limit((int)$post['rows'])
                    ->offset((int)$post['rows'] * (int)$post['page'] - (int)$post['rows']);
            }

            if (isset($post['sidx']) && isset($post['sord'])) {
                $results = $results->orderBy($post['sidx'], $post['sord']);
            }

            if (isset($post['_search'], $post['filters']) && $post['_search'] === 'true') {
                $filters = json_decode($post['filters'], true);
                if (is_array($filters)) {
                    foreach ($filters['rules'] as $rule) {
                        $results = $results->where($rule['field'], 'LIKE', '%'.$rule['data'].'%');
                    }
                }
            }

            if ($export) {
                return $results;
            }


            $results = $results->get();

        } catch (\Exception $e) {
            $this->registry->get('log')->write($e->getMessage());
        }

        $response = new stdClass();
        $response->page = 0;
        $response->records = 0;
        $response->total = 0;

        if ($results) {
            $total = $results->count();
            $response->page = $post['page'];
            $response->records = $this->db->sql_get_row_count();
            if ((int)$post['rows'] > 0) {
                $response->total = ceil($response->records / (int)$post['rows']);
            }
        }

        $i = 0;
        foreach ($results as $result) {
            $response->rows[$i]['id'] = $result->customer_id;
            $response->rows[$i]['cell'] = [
                $result->customer_id,
                $result->account_code,
                $result->loginname,
                $result->company,
                $result->firstname,
                $result->lastname,
                $result->postcode,
                $result->customer_type,
                $result->date_added,
                $result->advanced_status,
                $result->consolidation_status_text,
                $result->children_count,
            ];
            $i++;
        }
        return $response;
    }

}
