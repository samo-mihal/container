<?php

declare(strict_types=1);

namespace B13\Container\Domain\Service;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Domain\Model\Container;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContainerService implements SingletonInterface
{
    /**
     * @var Registry
     */
    protected $tcaRegistry;

    public function __construct(Registry $tcaRegistry = null)
    {
        $this->tcaRegistry = $tcaRegistry ?? GeneralUtility::makeInstance(Registry::class);
    }

    public function getNewContentElementAtTopTargetInColumn(Container $container, int $targetColPos): int
    {
        $containerRecord = $container->getContainerRecord();
        $target = -$containerRecord['uid'];
        $previousRecord = null;
        $allColumns = $this->tcaRegistry->getAllAvailableColumnsColPos($container->getCType());
        foreach ($allColumns as $colPos) {
            if ($colPos === $targetColPos && $previousRecord !== null) {
                $target = -$previousRecord['uid'];
            }
            $children = $container->getChildrenByColPos($colPos);
            if (!empty($children)) {
                $last = array_pop($children);
                $previousRecord = $last;
            }
        }
        return $target;
    }
}
