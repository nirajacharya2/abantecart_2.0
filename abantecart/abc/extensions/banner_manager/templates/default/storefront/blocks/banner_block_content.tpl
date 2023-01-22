<?php if (is_array($content) && $content) { ?>
    <?php
    foreach ($content as $banner) {
        if ($banner['banner_type'] == 1 && is_array($banner['images'])) {
            foreach ($banner['images'] as $img) {
                echo '<a href="' . $banner['target_url'] . '" ' . ($banner['blank'] ? ' target="_blank" ' : '') . '>';
                if ($img['origin'] == 'internal') {
                    echo '<img src="' . $img['main_url'] . '" width="' . $img['main_width'] . '" height="' . $img['main_height'] . '" title="' . $img['title'] . '" alt="' . $img['title'] . '">';
                } else {
                    echo $img['main_html'];
                }
                echo '</a>';
            }
        } else {
            echo $banner['description'];
        }
    }
    ?>
<?php } ?>