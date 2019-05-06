<?php

namespace abc\modules\reports\Sales\customers;

use abc\modules\reports\BaseReport;

class Customers extends baseReport
{
    protected function setup()
    {
        $this->src($this->db_config['DB_NAME'])
             ->query("SELECT c.customer_id, c.firstname, c.lastname, SUM(o.total) as total 
                      FROM ".$this->db_config['DB_PREFIX']."customers c
                      LEFT JOIN ".$this->db_config['DB_PREFIX']."orders o
                      ON o.customer_id = c.customer_id
                      GROUP BY customer_id, firstname, lastname
                      ORDER BY  SUM(o.total) DESC
                      LIMIT 0,10
                      ")
             ->pipe($this->dataStore("customers"));
    }

}