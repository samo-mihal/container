<?php

declare(strict_types=1);

namespace B13\Container\Integrity;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Integrity\Error\NonExistingParentError;
use B13\Container\Integrity\Error\UnusedColPosWarning;
use B13\Container\Integrity\Error\WrongL18nParentError;
use B13\Container\Integrity\Error\WrongPidError;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Integrity implements SingletonInterface
{

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var Registry
     */
    protected $tcaRegistry;

    /**
     * @var string[][]
     */
    protected $res = [
        'errors' => [],
        'warnings' => []
    ];

    /**
     * ContainerFactory constructor.
     * @param Database|null $database
     * @param Registry|null $tcaRegistry
     */
    public function __construct(Database $database = null, Registry $tcaRegistry = null)
    {
        $this->database = $database ?? GeneralUtility::makeInstance(Database::class);
        $this->tcaRegistry = $tcaRegistry ?? GeneralUtility::makeInstance(Registry::class);
    }

    public function run(): array
    {
        $cTypes = $this->tcaRegistry->getRegisteredCTypes();
        $colPosByCType = [];
        foreach ($cTypes as $cType) {
            $columns = $this->tcaRegistry->getAvailableColumns($cType);
            $colPosByCType[$cType] = [];
            foreach ($columns as $column) {
                $colPosByCType[$cType][] = $column['colPos'];
            }
        }
        $this->defaultLanguageRecords($cTypes, $colPosByCType);
        $this->nonDefaultLanguageRecords($cTypes, $colPosByCType);
        return $this->res;
    }

    private function nonDefaultLanguageRecords(array $cTypes, array $colPosByCType): void
    {
        // sys_langauge_uid > 0
        $nonDefaultLanguageChildRecords = $this->database->getNonDefaultLanguageContainerChildRecords();
        $nonDefaultLangaugeContainerRecords = $this->database->getNonDefaultLanguageContainerRecords($cTypes);
        $defaultLanguageContainerRecords = $this->database->getContainerRecords($cTypes);
        foreach ($nonDefaultLanguageChildRecords as $nonDefaultLanguageChildRecord) {
            if ($nonDefaultLanguageChildRecord['l18n_parent'] > 0) {
                // connected mode
                // tx_container_parent should be default container record uid
                if (!isset($defaultLanguageContainerRecords[$nonDefaultLanguageChildRecord['tx_container_parent']])) {
                    $this->res['errors'][] = new NonExistingParentError($nonDefaultLanguageChildRecord);
                } elseif (isset($nonDefaultLangaugeContainerRecords[$nonDefaultLanguageChildRecord['tx_container_parent']])) {
                    $this->res['errors'][] = new WrongL18nParentError($nonDefaultLanguageChildRecord, $nonDefaultLangaugeContainerRecords[$nonDefaultLanguageChildRecord['tx_container_parent']]);
                }
            } else {
                // free mode, can be created direct, or by copyToLanguage
                // tx_container_parent should be nonDefaultLanguage container record uid
                if (!isset($nonDefaultLangaugeContainerRecords[$nonDefaultLanguageChildRecord['tx_container_parent']])) {
                    $this->res['errors'][] = new NonExistingParentError($nonDefaultLanguageChildRecord);
                } elseif (isset($defaultLanguageContainerRecords[$nonDefaultLanguageChildRecord['tx_container_parent']])) {
                    $this->res['errors'][] = new WrongL18nParentError($nonDefaultLanguageChildRecord, $defaultLanguageContainerRecords[$nonDefaultLanguageChildRecord['tx_container_parent']]);
                } else {
                    $containerRecord = $nonDefaultLangaugeContainerRecords[$nonDefaultLanguageChildRecord['tx_container_parent']];
                    if ($containerRecord['pid'] !== $nonDefaultLanguageChildRecord['pid']) {
                        $this->res['errors'][] = new WrongPidError($nonDefaultLanguageChildRecord, $containerRecord);
                    }
                    if ($containerRecord['sys_language_uid'] !== $nonDefaultLanguageChildRecord['sys_language_uid']) {
                        $this->res['errors'][] = new WrongL18nParentError($nonDefaultLanguageChildRecord, $containerRecord);
                    }
                    if (!in_array($nonDefaultLanguageChildRecord['colPos'], $colPosByCType[$containerRecord['CType']])) {
                        $this->res['warnings'][] = new UnusedColPosWarning($nonDefaultLanguageChildRecord, $containerRecord);
                    }
                }
            }
        }
    }

    /**
     * @param array $cTypes
     * @param array $colPosByCType
     */
    private function defaultLanguageRecords(array $cTypes, array $colPosByCType): void
    {
        $containerRecords = $this->database->getContainerRecords($cTypes);
        $containerChildRecords = $this->database->getContainerChildRecords();
        foreach ($containerChildRecords as $containerChildRecord) {
            if (!isset($containerRecords[$containerChildRecord['tx_container_parent']])) {
                $this->res['errors'][] = new NonExistingParentError($containerChildRecord);
            } else {
                $containerRecord = $containerRecords[$containerChildRecord['tx_container_parent']];
                if ($containerRecord['pid'] !== $containerChildRecord['pid']) {
                    $this->res['errors'][] = new WrongPidError($containerChildRecord, $containerRecord);
                }
                if (!in_array($containerChildRecord['colPos'], $colPosByCType[$containerRecord['CType']])) {
                    $this->res['warnings'][] = new UnusedColPosWarning($containerChildRecord, $containerRecord);
                }
            }
        }
    }
}
