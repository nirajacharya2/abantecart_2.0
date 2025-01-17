<?php include($tpl_common_dir . 'action_confirm.tpl');
echo $tabs; ?>
<div class="tab-content">
    <div class="panel-heading">
        <div class="pull-right">
            <div class="btn-group mr10 toolbar">
                <?php if (!empty ($help_url)) : ?>
                    <a class="btn btn-white tooltips" href="<?php echo $help_url; ?>" target="new"
                       data-toggle="tooltip" title="" data-original-title="Help">
                        <i class="fa fa-question-circle fa-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php echo $form_language_switch; ?>
        </div>
    </div>

    <?php echo $form['form_open']; ?>
    <div class="panel-body panel-body-nopadding">
        <label class="h4 heading"><?php echo $form_title; ?></label>
        <?php foreach ($form['fields'] as $name => $field) {
            //Logic to calculate fields width
            $widthClasses = "col-sm-7";
            if (is_int(stripos($field->style, 'large-field'))) {
                $widthClasses = "col-sm-7";
            } else if (is_int(stripos($field->style, 'medium-field')) || is_int(stripos($field->style, 'date'))) {
                $widthClasses = "col-sm-5";
            } else if (is_int(stripos($field->style, 'small-field')) || is_int(stripos($field->style, 'btn_switch'))) {
                $widthClasses = "col-sm-3";
            } else if (is_int(stripos($field->style, 'tiny-field'))) {
                $widthClasses = "col-sm-2";
            }
            $widthClasses .= " col-xs-12";
            ?>
            <div class="form-group <?php if (!empty($error[$name])) {
                echo "has-error";
            } ?>">
                <label class="control-label col-sm-3 col-xs-12"
                       for="<?php echo $field->element_id; ?>"><?php echo $form['text'][$name]; ?></label>
                <div class="input-group afield <?php echo $widthClasses; ?> <?php echo($name == 'description' ? 'ml_ckeditor' : '') ?>">
                    <?php echo $field; ?>
                </div>
                <?php if (!empty($error[$name])) { ?>
                    <span class="help-block field_err"><?php echo $error[$name]; ?></span>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <div class="panel-footer">
        <div class="row">
            <div class="col-sm-6 col-sm-offset-3 center">
                <button class="btn btn-primary lock-on-click">
                    <i class="fa fa-save"></i> <?php echo $form['submit']->text; ?>
                </button>&nbsp;
                <a class="btn btn-default" href="<?php echo $cancel; ?>">
                    <i class="fa fa-sync"></i> <?php echo $form['cancel']->text; ?>
                </a>
            </div>
        </div>
    </div>
    </form>
</div>