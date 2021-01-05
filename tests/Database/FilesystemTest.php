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

use PHPUnit\Framework\TestCase;
use Blockchain\Database\Filesystem;
use Blockchain\Exception\NotFoundException;

class FilesystemTest extends TestCase
{
    public function testCheckFileExists()
    {
        $fs = new Filesystem();
        $this->expectException(NotFoundException::class);
        $fs->read('fooz');
    }

    public function testSearch()
    {
        $fs = new Filesystem();
        $path = dirname(__DIR__, 2) . '/composer.json';

        $this->assertStringContainsString(
            '"name": "jamielsharief/blockchain"',
            $fs->search($path, 'jamielsharief/blockchain')
        );
    }
}
