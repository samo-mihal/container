<?php

declare(strict_types=1);
namespace B13\Container\Tests\Functional\Hooks;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Hooks\UsedRecords;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class UsedRecordsTest extends FunctionalTestCase
{

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/container',
        'typo3conf/ext/container_example'
    ];

    /**
     * @test
     */
    public function addContainerChildrenReturnsTrueIfChildrenInContainer(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Hooks/Fixtures/UsedRecords/children_in_container.xml');
        $pageLayout = new PageLayoutView();
        $usedRecords = GeneralUtility::makeInstance(UsedRecords::class);
        $record = $this->fetchOneRecordByUid(2);
        $res = $usedRecords->addContainerChildren(['record' => $record, 'used' => false], $pageLayout);
        self::assertTrue($res);
    }

    /**
     * @test
     */
    public function addContainerChildrenReturnsFalseIfChildrenHasWrongPid(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Hooks/Fixtures/UsedRecords/children_in_container_wrong_pid.xml');
        $pageLayout = new PageLayoutView();
        $usedRecords = GeneralUtility::makeInstance(UsedRecords::class);
        $record = $this->fetchOneRecordByUid(2);
        $res = $usedRecords->addContainerChildren(['record' => $record, 'used' => false], $pageLayout);
        self::assertFalse($res);
    }

    /**
     * @test
     */
    public function addContainerChildrenReturnsFalseIfChildrenHasWrongColPos(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Hooks/Fixtures/UsedRecords/children_in_container_wrong_colpos.xml');
        $pageLayout = new PageLayoutView();
        $usedRecords = GeneralUtility::makeInstance(UsedRecords::class);
        $record = $this->fetchOneRecordByUid(2);
        $res = $usedRecords->addContainerChildren(['record' => $record, 'used' => false], $pageLayout);
        self::assertFalse($res);
    }

    /**
     * @test
     */
    public function addContainerChildrenReturnsFalsIfRecordNotInContainer(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Hooks/Fixtures/UsedRecords/children_not_in_container.xml');
        $pageLayout = new PageLayoutView();
        $usedRecords = GeneralUtility::makeInstance(UsedRecords::class);
        $record = $this->fetchOneRecordByUid(2);
        $res = $usedRecords->addContainerChildren(['record' => $record, 'used' => false], $pageLayout);
        self::assertFalse($res);
    }

    /**
     * @param string $field
     * @param int $id
     * @return array
     */
    protected function fetchOneRecordByUid(int $uid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $row = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        self::assertIsArray($row);
        return $row;
    }

}
