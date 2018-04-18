<?php echo $header; ?>
<div class="row">
    <div class="col-md-9">
    <div class="panel panel-default">

    <ul class="nav nav-tabs" role="tablist">
      <li class="active"><a href="#">Please Initiate</a></li>
    </ul>

    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
    <div class="panel-heading">
    <h2><i class="fa fa-coffee fa-fw"></i> Initialization Required</h2>
    </div>

    <div class="panel-body">

        <?php if ($error == 'not-initiated') { ?>
        <div >
            Thank you for choosing AbanteCart Ecommerce Software.</br>
            To proceed install you should to initiate project first.
            <br>
            <br>
            Just open terminal and run composer install inside abc directory</br>
			See more details on <a href="https://getcomposer.org/doc/" target="_blank">https://getcomposer.org/doc/</a>
        </div>
        <?php } ?>

    </div>

    <div class="panel-footer">
        <div class="form-inline form-group">

        </div>
    </div>

    </div>
    </form>
    </div>
    <div class="col-md-3">
        <div class="panel panel-default">
            <div class="panel-heading">
            <h4><i class="fa fa-info-circle fa-fw"></i> AbanteCart Tips</h4>
            </div>
            <div class="panel-body">
                <h5 class="tip_heading">Completely free</h5>
                <p>AbanteCart is completely free to use and modify as long as modifications comply with <a onclick="window.open('http://www.opensource.org/licenses/OSL-3.0');">OSL 3.0</a> </p>
                <h5 class="tip_heading">Powerful ecommerce</h5>
                <p>Loaded with many enterprise grade features and tools</p>
                <h5 class="tip_heading">Secure solution</h5>
                <p>Extended measures are applied in development to comply with PCI and other standards</p>
                <h5 class="tip_heading">Fast and Efficient</h5>
                <p>Database and code well designed and optimized to run fast for heavy traffic sites</p>
                <h5 class="tip_heading">Flexible and Expandable</h5>
                <p>Flexible architecture and extension API allows fast and inexpensive expansion</p>
            </div>

        </div>
    </div>

</div>
<?php echo $footer; ?>