<input id="<?php echo $id ?>"
       name="<?php echo $name ?>"
       type="<?php echo $type ?>"
       value="<?php echo $value; ?>"
       data-orgvalue="<?php echo $value; ?>"
    <?php echo $attr; ?>
       class="form-control adate <?php echo $style; ?>"
       placeholder="<?php echo $placeholder ?>"
    <?php if ($required) {
        echo 'required';
    } ?>/>

<?php if ($required || $help_url) { ?>
    <span class="input-group-addon">
	<?php if ($required) { ?>
        <span class="required">*</span>
    <?php }
    if ($help_url) { ?>
        <span class="help_element">
        <a href="<?php echo $help_url; ?>" target="new"><i class="fa fa-question-circle fa-lg"></i></a>
    </span>
    <?php } ?>
	</span>
<?php } ?>

<script type="text/javascript">
    $(document).ready(
        function () {
            $('#<?php echo $id ?>').datepicker(
                <?php // merge two js-objects ?>
                {
                    ...{
                        dateFormat: '<?php echo $dateformat ?>',
                    },
                    <?php echo $extra ? '...' . json_encode($extra) : '';?>
                }
            );
            <?php if ( $highlight == 'past' ){ ?>
            var startdate = $('#<?php echo $id ?>').val();
            if (new Date(startdate).getTime() < new Date().getTime()) {
                $('#<?php echo $id ?>').closest('.afield').addClass('focus');
            }
            <?php }
            if ( $highlight == 'future' ){ ?>
            var startdate = $('#<?php echo $id ?>').val();
            if ((new Date(startdate).getTime() > new Date().getTime())) {
                $('#<?php echo $id ?>').closest('.afield').addClass('focus');
            }
            <?php } ?>
        }
    );
</script>