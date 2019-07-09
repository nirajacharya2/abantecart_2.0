<?php

use abc\modules\reports\Sales\customers\Customers;

require_once __DIR__.DS."Customers.php";
$report = new Customers;
return $report->run()->render(null, true);