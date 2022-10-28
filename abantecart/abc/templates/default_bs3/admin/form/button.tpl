<?php if ($href || $href_class) { ?>
    <a id="<?php echo $id ?>"
       class="<?php echo($style ?: 'btn btn-default'); ?>" <?php echo($href ? 'href="' . $href . '"' : ''); ?>
       title="<?php echo($title ?: $text); ?>" <?php echo($target ? 'target="' . $target . '"' : '');
    echo $attr; ?>>
        <?php if ($icon) { ?>
            <i class="<?php echo $icon; ?>"></i>
        <?php }
        echo $text ?></a>
<?php } else { ?>
    <button id="<?php echo $id ?>" class="<?php echo($style ?: 'btn btn-default'); ?>"
            title="<?php echo($title ?: $text); ?>" <?php echo $attr ?>>
        <?php if ($icon) { ?>
            <i class="<?php echo $icon; ?>"></i>
        <?php }
        echo $text ?></button>
<?php } ?>