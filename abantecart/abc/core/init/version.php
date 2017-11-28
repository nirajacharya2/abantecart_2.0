<?php
use abc\ABC;

ABC::env('MASTER_VERSION', '2');
ABC::env('MINOR_VERSION', '0');
ABC::env('VERSION_BUILT', '0');
ABC::env('VERSION', ABC::env('MASTER_VERSION').'.'.ABC::env('MINOR_VERSION').'.'.ABC::env('VERSION_BUILT'));

