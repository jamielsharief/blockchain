<?php
/**
 * Blockchain
 * Copyright 2020-2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Blockchain\Test\Database;

use Blockchain\Database\Index;
use PHPUnit\Framework\TestCase;

class MockIndex extends Index
{
    public function disableRecentRecordsSearch()
    {
        $this->recentRecords = 0;
    }
}

class IndexTest extends TestCase
{
    public function testSearchForward()
    {
        $index = new Index(dirname(__DIR__) . '/Fixture/php-coin');
        $this->assertEquals(
            3, $index->search('00003d5d04344089103dcd1580d49f4759bd1a7c274801890a564bc685cada21')
        );
    }
}
