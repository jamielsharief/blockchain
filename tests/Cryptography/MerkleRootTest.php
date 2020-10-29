<?php
/**
 * Blockchain
 * Copyright 2020 Jamiel Sharief.
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
use Blockchain\Cryptography\MerkleRoot;
use function Blockchain\Cryptography\dhash;

/**
 * For the simplest explanation
 * @see https://en.bitcoin.it/wiki/Protocol_documentation#Merkle_Trees
 */
class MerkleRootTest extends TestCase
{
    public function testOne()
    {
        $tree = new MerkleRoot();

        $merkleRoot = $tree->calculate([
            'a'
        ]);

        $this->assertEquals(dhash('aa'), $merkleRoot);
    }

    public function testTwo()
    {
        $tree = new MerkleRoot();

        $merkleRoot = $tree->calculate([
            'a',
            'b'
        ]);

        $this->assertEquals(dhash('ab'), $merkleRoot);
    }

    public function testThree()
    {
        $tree = new MerkleRoot();

        $merkleRoot = $tree->calculate([
            'a',
            'b',
            'c'
        ]);

        $expected = dhash(dhash('ab') . dhash('cc'));
        $this->assertEquals($expected, $merkleRoot);
    }

    public function testFour()
    {
        $tree = new MerkleRoot();

        $merkleRoot = $tree->calculate([
            'a',
            'b',
            'c',
            'd'
        ]);

        $expected = dhash(dhash('ab') . dhash('cd'));
        $this->assertEquals($expected, $merkleRoot);
    }

    public function testFive()
    {
        $tree = new MerkleRoot();

        $merkleRoot = $tree->calculate([
            'a',
            'b',
            'c',
            'd',
            'e',
            'f'
        ]);

        $p1 = dhash('ab');
        $p2 = dhash('cd');
        $p3 = $p4 = dhash('ef');

        $expected = dhash(dhash($p1.$p2) . dhash($p3.$p4));

        $this->assertEquals($expected, $merkleRoot);
    }
}
