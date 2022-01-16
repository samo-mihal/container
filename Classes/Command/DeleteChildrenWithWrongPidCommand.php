<?php

declare(strict_types=1);

namespace B13\Container\Command;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Integrity\Error\WrongPidError;
use B13\Container\Integrity\Integrity;
use B13\Container\Integrity\IntegrityFix;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DeleteChildrenWithWrongPidCommand extends Command
{

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initializeBackendAuthentication();
        Bootstrap::initializeLanguageObject();
        $integrity = GeneralUtility::makeInstance(Integrity::class);
        $integrityFix = GeneralUtility::makeInstance(IntegrityFix::class);
        $res = $integrity->run();
        if (count($res['errors']) > 0) {
            foreach ($res['errors'] as $error) {
                if ($error instanceof WrongPidError) {
                    $integrityFix->deleteChildrenWithWrongPid($error);
                }
            }
        }
        return 0;
    }
}
