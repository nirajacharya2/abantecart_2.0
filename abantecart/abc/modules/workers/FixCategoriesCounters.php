<?php

namespace abc\modules\workers;

use abc\core\ABC;
use abc\models\catalog\Category;

class FixCategoriesCounters extends ABaseWorker
{

    private $lockFile = 'FixCategoriesCountersWorker.lock';

    public function __construct()
    {
        parent::__construct();
    }

    public function getModuleMethods()
    {
        return ['main'];
    }

    public function postProcessing()
    {
        @unlink($this->lockFile);
    }

    // php abcexec job:run --worker=FixCategoriesCounters
    public function main()
    {
        $this->init();
        Category::chunk(
            1000,
            static function ($categories) {
                foreach ($categories as $category) {
                        $category = Category::find($category->category_id);
                        $category->touch();
                }
            }
        );
    }

    private function init(): bool
    {
        $this->lockFile = ABC::env('DIR_SYSTEM').$this->lockFile;
        if (is_file($this->lockFile)) {
            $pid = file_get_contents($this->lockFile);
            exit ('Another worker with process ID '.$pid.' is running. Skipped.');
        }

        return true;
    }

    /**
     * @param string $text
     *
     * @void
     */
    public function echoCli($text)
    {
        if ($this->outputType == 'cli') {
            echo $text.$this->EOF;
        } else {
            $this->output[] = $text;
        }
    }
}