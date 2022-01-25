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

use B13\Container\Integrity\Error\NonExistingParentWarning;
use B13\Container\Integrity\Error\UnusedColPosWarning;
use B13\Container\Integrity\Error\WrongL18nParentError;
use B13\Container\Integrity\Error\WrongPidError;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IntegrityFix implements SingletonInterface
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
     * ContainerFactory constructor.
     * @param Database|null $database
     * @param Registry|null $tcaRegistry
     */
    public function __construct(Database $database = null, Registry $tcaRegistry = null)
    {
        $this->database = $database ?? GeneralUtility::makeInstance(Database::class);
        $this->tcaRegistry = $tcaRegistry ?? GeneralUtility::makeInstance(Registry::class);
    }

    public function deleteChildrenWithWrongPid(WrongPidError $wrongPidError): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->enableLogging = false;
        $childRecord = $wrongPidError->getChildRecord();
        $cmd = ['tt_content' => [$childRecord['uid'] => ['delete' => 1]]];
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();
    }
}
