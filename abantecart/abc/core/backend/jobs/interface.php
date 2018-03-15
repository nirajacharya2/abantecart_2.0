<?php
namespace abc\core\backend\jobs;
interface ABackgroundJobInterface
{
    public function scheduleJob();
}