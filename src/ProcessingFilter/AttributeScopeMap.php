<?php

namespace SimpleSAML\Module\authoauth2\ProcessingFilter;

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Module;
use Symfony\Component\Filesystem\Filesystem;

class AttributeScopeMap
{
    /**
     * Associative array with the mappings of attribute names.
     * @var array
     */
    private array $map = [];


    /**
     * Initialize this filter, parse configuration
     *
     * @param array &$config Configuration information about this filter.
     *
     * @throws \Exception If the configuration of the filter is wrong.
     */
    public function __construct(array &$config)
    {
        $mapFiles = [];

        foreach ($config as $origName => $newName) {
            if (is_int($origName)) {
                $mapFiles[] = $newName;
                continue;
            }

            if (!is_string($newName) && !is_array($newName)) {
                throw new Exception('Invalid attribute name: ' . var_export($newName, true));
            }

            $this->map[$origName] = $newName;
        }

        // load map files after we determine duplicate or rename
        foreach ($mapFiles as &$file) {
            $this->loadMapFile($file);
        }
    }


    /**
     * Loads and merges in a file with a attribute map.
     *
     * @param string $fileName Name of attribute map file. Expected to be in the attributemap directory in the root
     * of the SimpleSAMLphp installation, or in the root of a module.
     *
     * @throws \Exception If the filter could not load the requested attribute map file.
     */
    private function loadMapFile(string $fileName): void
    {
        $config = Configuration::getInstance();

        $m = explode(':', $fileName);
        if (count($m) === 2) {
            // we are asked for a file in a module
            if (!Module::isModuleEnabled($m[0])) {
                throw new Exception("Module '$m[0]' is not enabled.");
            }
            $filePath = Module::getModuleDir($m[0]) . '/attributemap/' . $m[1] . '.php';
        } else {
            $attributenamemapdir = $config->getPathValue('attributenamemapdir', 'attributemap/') ?: 'attributemap/';
            $filePath = $attributenamemapdir . $fileName . '.php';
        }

        $fileSystem = new Filesystem();
        if (!$fileSystem->exists($filePath)) {
            throw new Exception('Could not find attribute map file: ' . $filePath);
        }

        /** @psalm-var mixed|null $attributemap */
        $attributemap = null;
        include($filePath);
        if (!is_array($attributemap)) {
            throw new Exception('Attribute map file "' . $filePath . '" didn\'t define an attribute map.');
        }

        $this->map = array_merge($this->map, $attributemap);
    }


    /**
     * Apply filter to map attributes to scopes.
     *
     * @param array $scopes The current scopes before mapping.
     */
    public function process(array $scopes): array
    {
        $mapped_attributes = [];

        foreach ($scopes as $scope) {
            if (array_key_exists($scope, $this->map)) {
                if (!is_array($this->map[$scope])) {
                    $mapped_attributes[$this->map[$scope]] = $this->map[$scope];
                } else {
                    foreach ($this->map[$scope] as $to_map) {
                        $mapped_attributes[$to_map] = $to_map;
                    }
                }
            } else {
                $mapped_attributes[$scope] = $scope;
            }
        }

        return $mapped_attributes;
    }
}
