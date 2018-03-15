<?php
namespace abc\core\backend\jobs;

class APackageInstallerJob implements ABackgroundJobInterface
{
    public $package_info;
    public function __construct( $package_info )
    {
        $this->package_info = $package_info;
    }
    public function scheduleJob(){
        if(!$this->package_info){

            return false;
        }
        //??????
    }
}