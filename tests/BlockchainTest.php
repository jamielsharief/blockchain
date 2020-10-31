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

use Generator;
use Blockchain\Block;
use Blockchain\Blockchain;
use Blockchain\Transaction;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Blockchain\Exception\NotFoundException;
use Blockchain\Exception\BlockchainException;

class BlockchainTest extends TestCase
{
    public function testInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        new Blockchain('A', $this->blockchain_path());
    }
   
    /**
     * Create a Blockchain with the Genesis Block
     */
    public function testCreateBlockchain()
    {
        $blockchain = $this->createBlockchain();

        $path = $this->blockchain_path('php-coin');
        $this->assertFileExists($path . '/0/0.json');
        $this->assertFileExists($path . '/blockchain.idx');
        $this->assertEquals(67, strlen(file_get_contents($path . '/blockchain.idx')));
    }

    /**
     * @depends testCreateBlockchain
     */
    public function testInsert()
    {
        $blockchain = $this->createBlockchain();

        $this->addBlocks(1, $blockchain);

        $path = $this->blockchain_path('php-coin');
        $this->assertFileExists($path . '/0/1.json');
        $this->assertEquals(134, strlen(file_get_contents($path . '/blockchain.idx')));
    }

    public function testExists()
    {
        $blockchain = $this->createBlockchain();

        $this->assertTrue($blockchain->exists(0));
        $this->assertFalse($blockchain->exists(1));
    }

    public function testCount()
    {
        $blockchain = $this->createBlockchain();
        $this->assertEquals(1, $blockchain->count());

        $this->addBlocks(2, $blockchain);
        $this->assertEquals(3, $blockchain->count());
    }

    public function testGet()
    {
        $blockchain = $this->createBlockchain();
        $genesisBlock = $blockchain->get(0);
        $this->assertInstanceOf(Block::class, $genesisBlock);
        $this->assertEquals(0, $genesisBlock->index);

        $this->addBlocks(1, $blockchain);
        $lastBlock = $blockchain->get(1);
        $this->assertEquals(1, $lastBlock->index);
    }

    public function testGetDoesNotExist()
    {
        $blockchain = $this->createBlockchain();
        $this->expectException(NotFoundException::class);
        $blockchain->get(123);
    }

    public function testGetInvalidBlock()
    {
        $blockchain = $this->createBlockchain(5);
        $path = $this->blockchain_path('php-coin') . '/0/1.json';
        $block = json_decode(file_get_contents($path), true);
        $block['timestamp'] = strtotime('+ 1 day');
        file_put_contents($path, json_encode($block));
        $this->expectException(BlockchainException::class);
        $blockchain->get(1);
    }

    public function testAll()
    {
        $blockchain = $this->createBlockchain(5);
        $result = $this->generatorToArray($blockchain->all());
        $this->assertCount(6, $result);
        $this->assertEquals(5, key($result));
        $this->assertInstanceOf(Block::class, $result[0]);

        $result = $this->generatorToArray($blockchain->all(['reverse' => false]));
        $this->assertEquals(0, key($result));
    }

    public function testAllInvalidArgs1()
    {
        $blockchain = $this->createBlockchain(5);
        $generator = $blockchain->all(['start' => -1]);
        $this->expectException(InvalidArgumentException::class);
        $this->generatorToArray($generator);
    }

    public function testAllInvalidArgs2()
    {
        $blockchain = $this->createBlockchain(5);
        $generator = $blockchain->all(['finish' => 1025]);
        $this->expectException(InvalidArgumentException::class);
        $this->generatorToArray($generator);
    }

    public function testAllInvalidArgs3()
    {
        $blockchain = $this->createBlockchain(5);
        $generator = $blockchain->all(['start' => 1,'finish' => 1]);
        $this->expectException(InvalidArgumentException::class);
        $this->generatorToArray($generator);
    }

    private function generatorToArray(Generator $generator)
    {
        $out = [];
        foreach ($generator as $item) {
            $out[$item->index] = $item;
        }

        return $out;
    }

    /**
     * Sanity Check
     * @depends testGet
     */
    public function testChaining()
    {
        $blockchain = $this->createBlockchain(2);
 
        $this->assertEquals(
            $blockchain->get(0)->hash, $blockchain->get(1)->previousHash
        );
        $this->assertEquals(
            $blockchain->get(1)->hash, $blockchain->get(2)->previousHash
        );
    }

    /**
     * @depends testGet
     */
    public function testLast()
    {
        $blockchain = $this->createBlockchain();
        $genesisBlock = $blockchain->last();
        $this->assertEquals(0, $genesisBlock->index);

        $this->addBlocks(2, $blockchain);
        $lastBlock = $blockchain->last();
        $this->assertEquals(2, $lastBlock->index);
    }

    /**
     * @depends testLast
     */
    public function testList()
    {
        $blockchain = $this->createBlockchain();
    
        $genesisBlockHash = $blockchain->last()->hash;
        $this->assertEquals([$genesisBlockHash], $blockchain->list());
        
        $this->addBlocks(1, $blockchain);
        $lastHash = $blockchain->last()->hash;

        $this->assertEquals(
            [1 => $lastHash,0 => $genesisBlockHash],
            $blockchain->list()
        );
        $this->assertEquals([1 => $lastHash], $blockchain->list(1));
    }

    /**
     * @depends testLast
     */
    public function testListAmount()
    {
        $blockchain = $this->createBlockchain();
    
        $this->addBlocks(1, $blockchain);
        $lastHash = $blockchain->last()->hash;
        $this->assertEquals([1 => $lastHash], $blockchain->list(1));
    }

    public function testFind()
    {
        $blockchain = $this->createBlockchain(3);

        $hash = $blockchain->list()['3'];

        $block = $blockchain->find($hash);
        $this->assertEquals(3, $block->index);
    }

    public function testFindNotFound()
    {
        $blockchain = $this->createBlockchain();
        $this->expectException(NotFoundException::class);
        $blockchain->find('foo');
    }

    public function testProofOfWorkGenesisBlock()
    {
        $blockchain = new Blockchain('php-coin', $this->blockchain_path(), [
            'difficulty' => 4
        ]);
        $this->addBlocks(1, $blockchain);
        
        $genesisBlock = $blockchain->get(0);
        $this->assertStringStartsWith('0000', $genesisBlock->hash);
        $this->assertGreaterThan(0, $genesisBlock->nonce);
    }

    public function testProofOfWork()
    {
        $blockchain = new Blockchain('php-coin', $this->blockchain_path(), [
            'difficulty' => 4
        ]);

        $this->addBlocks(2, $blockchain);
        $block = $blockchain->get(1);
        $this->assertStringStartsWith('0000', $block->hash);
        $this->assertGreaterThan(0, $block->nonce);
    }

    public function testValidates()
    {
        $blockchain = $this->createBlockchain(5);
        $this->assertTrue($blockchain->validate());
    }

    public function testValidatesThisBlockModified()
    {
        $blockchain = $this->createBlockchain(5);

        $path = $this->blockchain_path('php-coin');
        $path = $path . '/0/4.json';

        /**
         * Modify the "previousHash" value IN THIS Block
         */
        $block = Block::deserialize(file_get_contents($path));

        $this->assertTrue($block->isValid());
        $block->previousHash = 'something-else';
        $this->assertFalse($block->isValid());

        file_put_contents($path, $block->toJson());
        $this->assertFalse($blockchain->validate());
    }

    public function testValidatesLastBlockModified()
    {
        $blockchain = $this->createBlockchain(5);

        $path = $this->blockchain_path('php-coin') . '/0/4.json';

        /**
         * Modify the "hash" IN THE previous Block. In this
         * Block its stored as previousHash.
         */
        $block = Block::deserialize(file_get_contents($path));

        $block->previousHash = 'something-else';
        $block->hash = $block->calculateHash();
        $this->assertTrue($block->isValid());

        file_put_contents($path, $block->toJson());

        // Validate both bocks hashes are valid
        $this->assertTrue($blockchain->get(3)->isValid());
        $this->assertTrue($blockchain->get(4)->isValid());

        // Validate that the chain has been broken as now block4->previous hash does not match $block3->hash
        $this->assertFalse($blockchain->validate());
    }

    public function testValidatesTransactionsBeenTamperedWith()
    {
        $blockchain = $this->createBlockchain(3);
        $path = $this->blockchain_path('php-coin') . '/0/3.json';

        /**
         * Here I am going to modify a value of some data in the
         * Transaction this will be picked up using the Merkle Root
         */
        $block = Block::deserialize(file_get_contents($path));
        $block->transactions[0]->data['amount'] = 123456789.99;

        file_put_contents($path, $block->toJson());
        $this->assertFalse($blockchain->validate());
    }

    public function testDuplicateTransactions()
    {
        $blockchain = $this->createBlockchain();
        $t1 = $this->generateTransaction();
        $t2 = $this->generateTransaction();
        $notUnique = $this->generateTransaction();

        $block = new Block();
        $block->add($t1);
        $block->add($notUnique);
        $block->add($t2);
    
        $this->assertTrue($blockchain->insert($block));

        $t1 = $this->generateTransaction();
        $t2 = $this->generateTransaction();

        $block = new Block();
        $block->add($t1);
        $block->add($notUnique);
        $block->add($t2);

        $this->assertFalse($blockchain->insert($block));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->blockchain_path('php-coin'))) {
            $this->recursiveDelete(
                $this->blockchain_path('php-coin')
            );
        }
    }

    /**
     * HELPER FUNCTIONS
     */

    protected function createBlockchain(int $blocks = 0): Blockchain
    {
        $blockchain = new Blockchain('php-coin', $this->blockchain_path(), [
            'difficulty' => 0
        ]);

        $block = new Block();
        $block->add($this->generateTransaction());
        $blockchain->insert($block);

        if ($blocks) {
            $this->addBlocks($blocks, $blockchain);
        }

        return $blockchain;
    }

    public function testGenerateAddress()
    {
        $blockchain = new Blockchain('php-coin', $this->blockchain_path());
        $address = $blockchain->generateAddress();
        $this->assertEquals(40, strlen($address));
        $this->assertStringMatchesFormat('%x', $address);
    }

    /**
     * Adds Blocks
     *
     * @param integer $amount
     * @param Blockchain $blockchain
     * @return void
     */
    protected function addBlocks(int $amount, Blockchain $blockchain, int $transactionsPerBlock = 3)
    {
        for ($i = 0;$i < $amount;$i++) {
            $this->addTransactions($transactionsPerBlock, $blockchain);
        }
    }

    /**
     * Add a Block with Transactions
     *
     * @param integer $amount
     * @param Blockchain $blockchain
     * @return void
     */
    protected function addTransactions(int $amount, Blockchain $blockchain)
    {
        $block = new Block();
        for ($i = 0;$i < $amount;$i++) {
            $block->add($this->generateTransaction());
        }
        $blockchain->insert($block);
    }

    /**
     * Generates a random transaction
     *
     * @return Transaction
     */
    protected function generateTransaction(): Transaction
    {
        $rand = rand(1, 24);

        return new Transaction([
            'date' => date('Y-m-d H:i:s', strtotime("+ {$rand} hour")),
            'to' => uniqid(),
            'from' => uniqid(),
            'amount' => rand(1, 100000)
        ]);
    }

    /**
     * Recursive delete
     *
     * @param string $path
     * @return void
     */
    protected function recursiveDelete(string $path)
    {
        foreach (glob($path . '/*') as $file) {
            is_dir($file) ? $this->recursiveDelete($file) : unlink($file);
        }
        rmdir($path);
    }

    protected function blockchain_path(string $name = null)
    {
        $path = sys_get_temp_dir()  . '/blockchain-test';

        return $name ?   $path  .'/' . $name : $path;
    }
}
