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
namespace Blockchain\Test\Cryptography;

use PHPUnit\Framework\TestCase;
use function Blockchain\Cryptography\dhash;

class FunctionsTest extends TestCase
{
    public function testDhash()
    {
        $expected = hash('sha256', hash('sha256', 'blockchain'));
        $this->assertEquals($expected, dhash('blockchain'));
    }
}
