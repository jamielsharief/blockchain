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
namespace Blockchain;

use Generator;
use InvalidArgumentException;
use Blockchain\Database\Index;
use Blockchain\Database\Filesystem;
use Blockchain\Exception\NotFoundException;
use Blockchain\Exception\BlockchainException;

class Blockchain
{
    protected string $name;
    protected string $path;
    protected int $difficulty;
    protected int $version;

    protected Filesystem $fs;
    protected Index $index;

    protected int $lookback;

    /**
     * Blockchain exists
     */
    protected bool $exists = false;

    /**
     * @param string $name the name of the Blockchain lower case letters numbers, dashes.
     * @param string $path Path to the folder where the Blockchain is stored.
     * @param array $options The following options keys are supported
     *  - difficulty: difficulty level for proof of work, set to 0 to disable
     *  - version: the version number of the Blockchain
     *  - lookback: the number of Blocks to lookback at to see if there are duplicate Transactions.
     */
    public function __construct(string $name, string $path, array $options = [])
    {
        $options += ['difficulty' => 8, 'version' => 1,'lookback' => 25];

        if (! preg_match('/^[a-z0-9-]*$/i', $name)) {
            throw new InvalidArgumentException('Invalid Blockchain name');
        }
        $this->name = $name;
        $this->path = $path . '/' . $name;

        $this->fs = new Filesystem();
        $this->index = new Index($this->path);

        $this->difficulty = $options['difficulty'];
        $this->version = $options['version'];
        $this->lookback = $options['lookback'];

        $this->exists = is_dir($this->path);
    }

    /**
     * Creates the Blockchain
     *
     * @param \Blockchain\Block $block
     * @return bool
     */
    protected function createGenesisBlock(Block $block): bool
    {
        $this->exists = mkdir($this->path, 0775, true);
        
        $block([
            'index' => 0,
            'difficulty' => $this->difficulty,
            'version' => $this->version,
            'previousHash' => '0000000000000000000000000000000000000000000000000000000000000000'
        ]);

        $this->mine($block);
        $this->writeBlock($block);

        return $this->exists($block->index);
    }

    /**
     * Generates a Bitcoin style address using the ripemd160 hashing algo
     *
     * @see https://en.bitcoin.it/wiki/Technical_background_of_version_1_Bitcoin_addresses
     * @see https://en.bitcoin.it/wiki/Protocol_documentation#Hashes
     *
     * @return string $address ee29bdece8a2bc4bf6db636d6b9fe1be15709e8f
     */
    public function generateAddress(): string
    {
        $random = bin2hex(random_bytes(16));

        return hash('ripemd160', hash('sha256', $random));
    }

    /**
    * Gets a Block by the index in the Blockchain
    *
    * @param integer $index
    * @return \Blockchain\Block
    */
    public function get(int $index): Block
    {
        $block = $this->fetch($index);

        if (! $block->isValid()) {
            throw new BlockchainException("Block #{$block->index} is invalid");
        }

        return $block;
    }

    /**
     * Fetchs a Block from the Blockchain
     *
     * @param integer $index
     * @return \Blockchain\Block
     */
    protected function fetch(int $index): Block
    {
        $path = $this->blockPath($index);

        try {
            $block = Block::deserialize($this->fs->read($path));
        } catch (NotFoundException $exception) {
            throw new NotFoundException("Block #{$index} not found");
        }

        return $block;
    }

    /**
     * Checks if a Block exists in the Blockchain
     *
     * @param integer $index
     * @return bool
     */
    public function exists(int $index): bool
    {
        return file_exists($this->blockPath($index));
    }

    /**
     * Gets the number of Blocks in the Blockchain
     *
     * @return integer
     */
    public function count(): int
    {
        return $this->index->last() + 1;
    }

    /**
     * Gets a list of Block hashes and index number
     *
     * @return array
     */
    public function list(int $count = 10): array
    {
        return $this->index->list($count);
    }

    /**
     * Finds a Block using a Hash
     *
     * @param string $hash
     * @return \Blockchain\Block
     */
    public function find(string $hash): Block
    {
        $index = $this->index->search($hash);

        if (! $index) {
            throw new NotFoundException("Block with hash {$hash} not found");
        }

        return $this->get($index);
    }

    /**
     * Gets the last Block in the Blockchain
     *
     * @return \Blockchain\Block
     */
    public function last(): Block
    {
        return $this->get($this->index->last());
    }

    /**
     * Runs through each Block in the Blockchain in a memory efficient way
     *
     * @param array $options the following option keys are supported
     *  - start: default:0 The Block number to start from
     *  - finish: default:<lastblock> The Block number to end at
     *  - reverse: default: true. Reverses the Blocknumbers so higher block numbers are
     * processed first
     * @return \Generator
     */
    public function all(array $options = []): Generator
    {
        $max = $this->index->last();

        $options += ['start' => 0,'finish' => $max,'reverse' => true];

        if ($options['start'] < 0 || $options['finish'] > $max || $options['start'] >= $options['finish']) {
            throw new InvalidArgumentException('Invalid start finish range');
        }

        $numbers = range($options['start'], $options['finish']);
        $numbers = $options['reverse'] ? array_reverse($numbers) : $numbers;

        foreach ($numbers as $index) {
            yield $this->get($index);
        }
    }
  
    /**
     * Inserts a new Block into the Blockchain
     *
     * @param \Blockchain\Block $block
     * @return bool
     */
    public function insert(Block $block): bool
    {
        if (! $this->exists) {
            return $this->createGenesisBlock($block);
        }

        if ($block->index !== null || $this->hasDuplicateTransactions($block)) {
            return false;
        }

        $previousBlock = $this->last();

        $block([
            'index' => $previousBlock->index + 1,
            'difficulty' => $this->difficulty,
            'version' => $this->version,
            'previousHash' => $previousBlock->hash
        ]);

        $this->mine($block);
        $this->writeBlock($block);

        return $this->exists($block->index);
    }
    /**
     * Runs through the whole Blockchain validating each Block and its transactions.
     *
     * @internal on my MacBook 2012 , it took approx 9 minutes to validate a Blockchain
     * with 1,294,691 Blocks.
     *
     * @return boolean
     */
    public function validate(): bool
    {
        $blocks = $this->count();
        $previousBlock = $this->fetch(0);

        for ($i = 1; $i < $blocks; $i ++) {
            $currentBlock = $this->fetch($i);
           
            // check hash and merkleroot
            if ($currentBlock->isValid() === false) {
                return false;
            }
            // check in chain
            if ($currentBlock->previousHash !== $previousBlock->hash) {
                return false;
            }

            $previousBlock = $currentBlock;
        }

        return true;
    }

    /**
    * Mines a block before it can be added to Blockchain
    * Increments the nonce value and rehashes until the leading characters match the difficulty * 0
    *
    * @param \Blockchain\Block $block
    * @return void
    */
    protected function mine(Block $block): void
    {
        $target = str_repeat('0', $this->difficulty);
        while (substr($block->hash, 0, $this->difficulty) !== $target) {
            $block->nonce++;
            $block->hash = $block->calculateHash();
        }
    }

    /**
     * Check if any of the Transactions submitted in the Block exist
     * in the last X Blocks
     *
     * @param \Blockchain\Block $block
     * @return boolean
     */
    protected function hasDuplicateTransactions(Block $block)
    {
        $hashes = [];
        foreach ($block->transactions as $transaction) {
            $hashes[] = $transaction->hash;
        }

        $lastestBlock = $this->last();
        for ($i = 0;$i < $this->lookback;$i++) {
            if ($lastestBlock->index === 0) {
                break;
            }
            foreach ($lastestBlock->transactions as $transaction) {
                if (in_array($transaction->hash, $hashes)) {
                    return true;
                }
            }
            $lastestBlock = $this->get($lastestBlock->index - 1);
        }
      
        return false;
    }

    /**
     * Writes the Block to disk
     *
     * @param \Blockchain\Block $block
     * @return void
     */
    protected function writeBlock(Block $block): void
    {
        $path = $this->blockPath($block->index);

        if (! $this->fs->write($path, $block->toJson(), true)) {
            throw new BlockchainException('Error writing Block');
        }
        $this->index->add($block);
    }

    /**
     * Gets the path where to store the block file
     *
     * @internal ls/find uses readdir which only lists 32k files at time.
     *
     * @param integer $index
     * @return string
     */
    protected function blockPath(int $index): string
    {
        $prefix = floor($index / 32000);

        return $this->path . '/' . $prefix . '/' . $index . '.json';
    }
}
