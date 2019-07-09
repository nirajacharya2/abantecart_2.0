<?php

use \koolreport\widgets\koolphp\Table;
use \koolreport\widgets\google\BarChart;

?>

<div class="report-content">
	<div class="text-center">
		<h1>Customers List</h1>
		<p class="lead">Just a test customer sales</p>
	</div>


    <?php
    Table::create([
        "dataStore" => $this->dataStore('customers'),
        "columns"   => [
            "customer_id" => [
                "label" => "Customer ID",
            ],
            "firstname"   => [
                "label" => "First Name",
            ],
            "lastname"    => [
                "label" => "Last Name",
            ],
            "total"       => [
                "label" => "Total Amount",
            ],
        ],
        "cssClass"  => [
            "table" => "table table-bordered table-striped",
        ],
    ]);
    ?>

    <?php
    BarChart::create([
        "dataStore" => $this->dataStore('customers'),
        "width"     => "100%",
        "height"    => "500px",
        "columns"   => [
            "lastname" => [
                "label" => "Customer",
            ],
            "total"    => [
                "type"     => "number",
                "label"    => "Amount",
                "prefix"   => "$",
                "emphasis" => true,
            ],
        ],
        "options"   => [
            "title" => "Sales By Customer",
        ],
    ]);
    ?>
</div>

