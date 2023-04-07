<?php
echo implode('', $fields);
?>
<script type="application/javascript">
    $('#<?php echo $fields['value']->element_id; ?>').on('blur', function () {
        let val = $(this).val();
        val = val.replace(/\s/g, "").replace(/(,,)/g, ',');
        $(this).val(val);
    });
</script>
