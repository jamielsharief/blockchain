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
namespace Blockchain\Test;

use Blockchain\Block;
use Blockchain\Transaction;
use PHPUnit\Framework\TestCase;
use Blockchain\Exception\BlockchainException;

class BlockTest extends TestCase
{
    /**
     * Header is used to generate a hash
     */
    public function testHeader()
    {
        $expected = [
            'version' => 1,
            'previousHash' => '203c304ccd77980936d5171e3ea529b22eb13b9f1a2b70da2d09dd567455da02',
            'merkleRoot' => '406ae2b658f4ea1d33ca47a9b5303998b0289d8f298b5bd8a08bf4c90d8d9226',
            'timestamp' => 1603875529,
            'difficulty' => 0,
            'nonce' => 0
        ];

        $block = new Block($expected);
        $this->assertEquals($expected, $block->header());
    }

    /**
     * @depends testHeader
     */
    public function testHash()
    {
        $block = new Block([
            'version' => 1,
            'previousHash' => '203c304ccd77980936d5171e3ea529b22eb13b9f1a2b70da2d09dd567455da02',
            'merkleRoot' => '406ae2b658f4ea1d33ca47a9b5303998b0289d8f298b5bd8a08bf4c90d8d9226',
            'timestamp' => 1603875529,
            'difficulty' => 0,
            'nonce' => 0
        ]);
        $this->assertEquals('918191e2c06a979f6070647cea34cb13f66aa2473380af5e2ccc359b8892ffc6', $block->calculateHash());
    }

    public function testAddTransaction()
    {
        $block = new Block();
        $transaction1 = new Transaction([
            'date' => '2020-10-25 10:55:23',
            'to' => 'jon',
            'from' => 'tony',
            'amount' => 500
        ]);
        $this->assertEquals(0, $block->noTransactions);
        
        $block->add($transaction1);
        $this->assertEquals([$transaction1], $block->transactions);
        $this->assertEquals(1, $block->noTransactions);

        $transaction2 = new Transaction([
            'date' => '2020-10-25 10:55:24',
            'to' => 'tony',
            'from' => 'clarie',
            'amount' => 456
        ]);

        $block->add($transaction2);
        $this->assertEquals([$transaction1,$transaction2], $block->transactions);
        $this->assertEquals(2, $block->noTransactions);
    }

    public function testAddDuplicateTransaction()
    {
        $transaction = new Transaction([
            'date' => date('Y-m-d H:i:s'),
            'to' => 'jon',
            'from' => 'tony',
            'amount' => 500
        ]);

        $block = new Block();
        $block->add($transaction);
        $this->expectException(BlockchainException::class);
        $block->add($transaction);
    }

    public function testIsValid()
    {
        $transaction = new Transaction([
            'date' => date('Y-m-d H:i:s'),
            'to' => 'jon',
            'from' => 'tony',
            'amount' => 500
        ]);
        $block = new Block();
        $block->add($transaction);

        $block(0, '0000000000000000000000000000000000000000000000000000000000000000', 4, 1); // Invoke for use

        $this->assertTrue($block->isValid());

        /**
         * Test Hashing element
         */
        $copy = clone $block;
        $copy->timestamp = strtotime('+1 day');

        $this->assertFalse($copy->isValid());

        /**
         * Test Transaction modification
         */
        $copy = clone $block;
        $copy->transactions[0]->data['amount'] = 5000;
        $this->assertFalse($copy->isValid());
    }

    public function testToJson()
    {
        $block = $this->blockFixture();
        $this->assertEquals(
            '{"hash":"16e3cb59ed8cd1a0e742cd5526bc8d38c90bb4a3992aa832a53c829c7462b37c","version":1,"previousHash":"0000000000000000000000000000000000000000000000000000000000000000","merkleRoot":"19025cfb2e48ef0b51e60eb2954e76a4b89c9921089c92f391f675f196c65a91","timestamp":1604145600,"difficulty":0,"nonce":0,"index":0,"noTransactions":1,"transactions":[{"hash":"c4bd970c8e32582baabae94714dfb137e93aecb0e11999bc57ce018a401836b7","data":{"date":"2020-10-25 10:55:23","to":"jon","from":"tony","amount":500}}]}',
            $block->toJson()
        );

        $this->assertEquals('a83cd0408d37fa89ea901701b9a28c0b', md5($block->toJson(['pretty' => true])));
    }

    public function testToString()
    {
        $block = $this->blockFixture();
        $this->assertEquals(
            '{"hash":"16e3cb59ed8cd1a0e742cd5526bc8d38c90bb4a3992aa832a53c829c7462b37c","version":1,"previousHash":"0000000000000000000000000000000000000000000000000000000000000000","merkleRoot":"19025cfb2e48ef0b51e60eb2954e76a4b89c9921089c92f391f675f196c65a91","timestamp":1604145600,"difficulty":0,"nonce":0,"index":0,"noTransactions":1,"transactions":[{"hash":"c4bd970c8e32582baabae94714dfb137e93aecb0e11999bc57ce018a401836b7","data":{"date":"2020-10-25 10:55:23","to":"jon","from":"tony","amount":500}}]}',
            (string) $block
        );
    }

    protected function blockFixture()
    {
        $block = new Block();
        $transaction = new Transaction([
            'date' => '2020-10-25 10:55:23',
            'to' => 'jon',
            'from' => 'tony',
            'amount' => 500
        ]);
        $block->add($transaction);

        $block->index = 0;
        $block->timestamp = strtotime('2020-10-31 12:00:00');
        $block->previousHash = '0000000000000000000000000000000000000000000000000000000000000000';
        $block->difficulty = 0;
        $block->version = 1;
        $block->hash = $block->calculateHash();
        $block->merkleRoot = $block->calculateMerkleRoot();
      
        return $block;
    }
}
