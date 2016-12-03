<?php

namespace Notamedia\ConsoleJedi\Schema;

use Bitrix\Main\Loader;
use Notamedia\ConsoleJedi\Schema\Exception\ExportException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Export
 * @package Notamedia\ConsoleJedi\Schema
 */
class Export implements ActionInterface
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    private $session = [];

    /**
     * @var \CIBlockCMLExport
     */
    protected $export;

    /**
     * @var string
     */
    protected $prefix = '.tmp';

    public function __construct()
    {
        $this->config = [
            'id' => '',
            'path' => '',
            'sections' => 'none',
            'elements' => 'none',
            'interval' => 30
        ];

        Loader::includeModule('iblock');
        $this->export = new \CIBlockCMLExport();
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->config['id'] = intval($id);
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->config['path'] = $path;
        return $this;
    }

    /**
     * @param string $sections
     * @return $this
     */
    public function setSections($sections)
    {
        $this->config['sections'] = $sections;
        return $this;
    }

    /**
     * @param string $elements
     * @return $this
     */
    public function setElements($elements)
    {
        $this->config['elements'] = $elements;
        return $this;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $this->session = [
            "property_map" => false,
            "section_map" => false,
            "work_dir" => false,
            "file_dir" => false,
        ];

        $this->export();
        return $this;
    }

    /**
     * @return $this
     * @throws ExportException
     */
    protected function export()
    {
        $filesystem = new Filesystem();
        $handle = fopen($this->config['path'] . $this->prefix, "w");

        $checkPermissions = true;
        if (PHP_SAPI == 'cli') {
            $checkPermissions = false;
        }

        if (!$this->export->Init(
            $handle,
            $this->config["id"],
            false,
            true,
            $this->session["work_dir"],
            $this->session["file_dir"],
            $checkPermissions
        )
        ) {
            throw new ExportException('Failed to initialize export');
        }

        $this->export->DoNotDownloadCloudFiles();
        $this->export->StartExport();

        $this->export->StartExportMetadata();
        $this->export->ExportProperties($this->session["property_map"]);
        $this->export->ExportSections(
            $this->session["section_map"],
            time(),
            $this->config['interval'],
            $this->config["sections"],
            $this->session["property_map"]
        );
        $this->export->EndExportMetadata();

        $this->export->StartExportCatalog();
        $this->export->ExportElements(
            $this->session["property_map"],
            $this->session["section_map"],
            time(),
            $this->config['interval'],
            0,
            $this->config["elements"]
        );
        $this->export->EndExportCatalog();

        $this->export->ExportProductSets();
        $this->export->EndExport();

        fclose($handle);
        $filesystem->remove($this->config['path']);
        $filesystem->rename($this->config['path'] . $this->prefix, $this->config['path'], true);
    }
}