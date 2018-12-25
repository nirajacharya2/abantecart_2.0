<?php

namespace abc\modules\injections\phpabac;

use PhpAbac\Configuration\ConfigurationInterface;
use PhpAbac\Loader\YamlLoader;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;

use PhpAbac\Loader\JsonLoader;


class Configuration implements ConfigurationInterface
{
    protected $loaders = [];
    /** @var array * */
    protected $rules = [];
    /** @var array * */
    protected $attributes = [];
    /** @var array List of File Already Loaded */
    protected $loadedFiles = [];
    
    const LOADERS = [
        JsonLoader::class,
        YamlLoader::class,
        PhpConfigLoader::class
    ];
    
    public function __construct(array $configurationFiles, string $configDir = null)
    {

        $configDir = $configDir && substr($configDir,-1) != DS ? $configDir.DS : $configDir;

        $this->initLoaders($configDir);
        //seek files with policyRules recursively inside configDir
        //This means we loads all policies for some user type, such as system or admin or customer etc
        $configDirFiles = $this->findConfigs($configDir);

        $all_files = array_merge($configurationFiles,$configDirFiles);

        $this->parseFiles($all_files);

    }
    
    protected function initLoaders(string $configDir = null)
    {
        $locator = new FileLocator($configDir);
        foreach (self::LOADERS as $loaderClass) {
            /**
             * @var PhpConfigLoader $loader
             */
            $loader = new $loaderClass($locator);
            $loader->setCurrentDir($configDir);
            $this->loaders[] = $loader;
        }
    }

    protected function findConfigs(string $dir):array
    {
        $output = [];
        if(!is_dir($dir)){
            return [];
        }

        $iteration = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
        );

        foreach ($iteration as $path => $dir) {
            if ($dir->isFile()) {
                $output[] = (string)$dir;
            }
        }

        return $output;
    }
    
    protected function parseFiles(array $configurationFiles)
    {

        foreach ($configurationFiles as $configurationFile) {

            $config = $this
                ->getLoader($configurationFile)
                ->import($configurationFile, pathinfo($configurationFile, PATHINFO_EXTENSION));
            
            if (in_array($configurationFile, $this->loadedFiles)) {
                continue;
            }
            
            $this->loadedFiles[] = $configurationFile;
            
            if (isset($config['@import'])) {
                $this->parseFiles($config['@import']);
                unset($config['@import']);
            }
            
            if (isset($config['attributes'])) {
                $this->attributes = array_merge_recursive($this->attributes, $config['attributes']);
            }
            if (isset($config['rules'])) {
                $this->rules = array_merge_recursive($this->rules, $config['rules']);
            }
        }
    }

    /**
     * @param string $configurationFile
     *
     * @return PhpConfigLoader
     * @throws \Exception
     */
    protected function getLoader(string $configurationFile): LoaderInterface
    {
        foreach ($this->loaders as $abacLoader) {
            /**
             * @var PhpConfigLoader $abacLoader
             */
            if ($abacLoader->supports($configurationFile)) {
                return $abacLoader;
            }
        }
        throw new \Exception('Loader not found for the file ' . $configurationFile);
    }
    
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    public function getRules(): array
    {
        return $this->rules;
    }
}
