<?php

use abc\core\ABC;

include(ABC::env('DIR_APP') . ABC::env('RDIR_TEMPLATE') . 'common' . DS . 'action_confirm.tpl');
echo implode('', $fields);
?>