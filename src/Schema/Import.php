<?php

namespace Notamedia\ConsoleJedi\Schema;

use Bitrix\Main\Loader;
use Bitrix\Main\IO\Path;
use Notamedia\ConsoleJedi\Schema\Exception\ImportException;

/**
 * Class Import
 * @package Notamedia\ConsoleJedi\Schema
 */
class Import implements ActionInterface
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
     * @var \CIBlockXMLFile
     */
    protected $xml;

    /**
     * @var \CIBlockCMLImport
     */
    protected $import;

    public function __construct()
    {
        $this->config = [
            'type' => '',
            'lids' => [],
            'path' => '',
            'action_section' => 'A',
            'action_element' => 'A',
            'preview' => 'Y',
            'interval' => 0
        ];

        Loader::includeModule('iblock');
        $this->xml = new \CIBlockXMLFile();
        $this->import = new \CIBlockCMLImport();

    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->config['type'] = $type;
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
     * @param array $lids
     * @return $this
     */
    public function setSites($lids)
    {
        $this->config['lids'] = $lids;
        return $this;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setActionSection($action)
    {
        $this->config['action_section'] = $action;
        return $this;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setActionElement($action)
    {
        $this->config['action_element'] = $action;
        return $this;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $absFilename = Path::convertSiteRelativeToAbsolute($this->config['path']);

        $this->session = [
            "section_map" => false,
            "prices_map" => false,
            "work_dir" => pathinfo($absFilename, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR
        ];

        $this->read();
        $this->import();

        return $this;
    }

    /**
     * @throws ImportException
     */
    protected function read()
    {
        $handle = fopen($this->config['path'], "r");

        if (!$handle)
            throw new ImportException('Unable to open file, or file not exist');

        if (!$this->import->CheckIfFileIsCML($this->config['path'])) {
            throw new ImportException('File is not valid');
        }

        $this->xml->DropTemporaryTables();
        $this->xml->CreateTemporaryTables();
        $this->xml->ReadXMLToDatabase($handle, $this->session, $this->config['interval']);
        $this->xml->IndexTemporaryTables();
    }

    /**
     *
     */
    protected function import()
    {
        $this->import->Init(
            $this->config,
            $this->session['work_dir'],
            true,
            $this->config["preview"],
            false,
            true
        );
        $this->import->ImportMetaData([1, 2], $this->config["type"], $this->config["lids"]);

        $this->import->ImportSections();
        $this->import->DeactivateSections($this->config["action_section"]);

        $this->import->ReadCatalogData($this->session["section_map"], $this->session["prices_map"]);
        $this->import->ImportElements(time(), $this->config["interval"]);
        $this->import->DeactivateElement($this->config["action_element"], time(), $this->config["interval"]);

        $this->import->ImportProductSets();
    }
}