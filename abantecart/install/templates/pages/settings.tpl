<?php
$img_good = '<img src="templates/assets/images/good.png" alt="Good" />';
$img_bad = '<img src="templates/assets/images/bad.png" alt="Bad" />';
echo $header;
?>
    <div class="row">
        <div class="col-md-9">
            <div class="panel panel-default">
                <ul class="nav nav-tabs" role="tablist">
                    <li><a href="<?php echo $back; ?>">1: License</a></li>
                    <li class="active"><a href="#" onclick="return false;">2: Compatibility Validation</a></li>
                    <li class="disabled"><a href="#" onclick="return false;">3: Configuration</a></li>
                    <li class="disabled"><a href="#" onclick="return false;">4: Data Load</a></li>
                    <li class="disabled"><a href="#" onclick="return false;">5: Finished</a></li>
                </ul>
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                    <div class="panel-heading">
                        <h2>
                            <i class="fa fa-cogs fa-fw"></i> Compatibility Validation
                            <small class="pull-right">
								<a onclick="document.getElementById('form').submit()"
                                   class="btn btn-primary">Continue <i class="fa fa-arrow-right"></i>
								</a>
							</small>
                        </h2>
                    </div>
                    <div class="panel-body">
                        <?php if ($error_warning) { ?>
                            <div class="warning alert alert-error alert-danger"><?php echo $error_warning; ?></div>
                        <?php } ?>
                        <p>1. Please see if your PHP settings configured to match requirements listed below.</p>
                        <div class="section">
                            <table class="settings_table">
                                <thead>
                                <tr>
                                    <th>PHP Settings</th>
                                    <th>Current Settings</th>
                                    <th>Required Settings</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                        <?php   foreach ($php_ini as $name => $values) { ?>
                                    <tr>
                                        <td><?php echo $name; ?></td>
                                        <td><?php echo $values['current']; ?></td>
                                        <td><?php echo $values['required']; ?></td>
                                        <td align="center"><?php echo $values['status'] ? $img_good : $img_bad; ?></td>
                                    </tr>
                        <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <p>2. Please make sure the extensions listed below are installed.</p>
                        <div class="section">
                            <table class="settings_table">
                                <thead>
                                <tr>
                                    <th>Extension</th>
                                    <th>Current Settings</th>
                                    <th>Required Settings</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                        <?php   foreach ($php_libs as $name => $status) { ?>
                                    <tr>
                                        <td><?php echo $name; ?></td>
                                        <td><?php echo $status ? 'On' : 'Off'; ?></td>
                                        <td>On</td>
                                        <td align="center"><?php echo $status ? $img_good : $img_bad; ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <p>3. Please make sure you have set the correct permissions on the directories list below.</p>
                        <div class="section">
                            <table class="settings_table">
                                <thead>
                                <tr>
                                    <th style="width: 85%">Directories</th>
                                    <th style="width: 15%">Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($directories as $dir) {
                                    $_writable = is_writable($dir); ?>
                                    <tr>
                                        <td><?php echo $dir; ?></td>
                                        <td><?php echo $_writable ? '<span class="good">Writable</span>' : '<span class="bad">Unwritable</span>'; ?></td>
                                    </tr>
                                    <?php if ( ! $_writable) { ?>
                                        <tr>
                                            <td colspan="2">
                                                <span class="bad">Change directory and all directories children permissions to 775 or rwx-rwx-rwx:<br/> chmod -R 775 <?php echo $dir; ?></span>
                                            </td>
                                        </tr>
                                    <?php }
                                } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="panel-footer">
                        <a class="btn btn-default" href="<?php echo $back; ?>"><i class="fa fa-arrow-left"></i> Back</a>
                        <a class="btn btn-primary pull-right" onclick="document.getElementById('form').submit()">Continue
                            <i class="fa fa-arrow-right"></i></a>
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
                    <h5 class="tip_heading">Editing text is made easy</h5>

                    <p>Edit any text in the admin with quick search and text edit feature.</p>
                    <h5 class="tip_heading">Multilingual and Auto-translation</h5>

                    <p>AbanteCart is multilingual and powered with automatic missing text population or translation</p>
                    <h5 class="tip_heading">Quick Save</h5>

                    <p>Editing is made easy with quick save feature. When change a filed quick save button will show</p>
                    <h5 class="tip_heading">Smart search</h5>

                    <p>Navigate administration faster with smart search locating data in all areas of application</p>
                    <h5 class="tip_heading">Media Manager</h5>

                    <p>Convenient interface to manage media files with resource library</p>
                    <h5 class="tip_heading">Flexible Layout</h5>

                    <p>Flexible and quick to edit multi-template layout manager</p>
                    <h5 class="tip_heading">Advanced Import/Export</h5>

                    <p>Fully featured Import/Export in CSV and XML formats</p>
                </div>

            </div>
        </div>

    </div>
<?php echo $footer; ?>