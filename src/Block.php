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
namespace Blockchain;

use Blockchain\Cryptography\MerkleRoot;
use function Blockchain\Cryptography\dhash;
use Blockchain\Exception\BlockchainException;

class Block
{
    /**
     * BLOCK HEADER
     */
    public ?string $hash = null;
    public ?int $version = null;
    public ?string $previousHash = null;
    public ?string $merkleRoot = null;
    public ?int $timestamp = null;
    public ?int $difficulty = null;
    public int $nonce = 0;

    /**
     * BLOCK VALUES
     */
    public ?int $index = null;
    public int $noTransactions = 0;
    public array $transactions = [];
   
    /**
     * @param array $properties
     */
    public function __construct(array $properties = [])
    {
        foreach ($properties as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Returns the Header for this Block, this is what is hashed, adding more items here
     * does not make it better.
     *
     * version + previous block hash + merkle root + timestamp + bits + nonce
     * @see https://en.bitcoin.it/wiki/Block_hashing_algorithm
     *
     * @return array
     */
    public function header(): array
    {
        return [
            'version' => $this->version,
            'previousHash' => $this->previousHash,
            'merkleRoot' => $this->merkleRoot,
            'timestamp' => $this->timestamp,
            'difficulty' => $this->difficulty,
            'nonce' => $this->nonce
        ];
    }

    /**
     * Adds a Transaction to this Block
     *
     * @param Transaction $transaction
     * @return void
     */
    public function add(Transaction $transaction): void
    {
        $transaction->hash = $transaction->calculateHash();

        foreach ($this->transactions as $tx) {
            if ($transaction->hash === $tx->hash) {
                throw new BlockchainException('Duplicate Transaction');
            }
        }
        $this->transactions[] = $transaction;
        $this->noTransactions = count($this->transactions);
    }

    /**
     * Invokes the Block for adding to the Blockchain
     *
     * @param array $options
     * @return void
     */
    public function __invoke(int $index, string $previousHash, int $difficulty, int $version)
    {
        $this->index = $index;
        $this->previousHash = $previousHash;
        $this->difficulty = $difficulty;
        $this->version = $version;

        $this->timestamp = time();

        $this->merkleRoot = $this->calculateMerkleRoot();
        $this->hash = $this->calculateHash();
    }

    /**
    * Checks if the Block is Valid. Checks that the Hash matches generated hash
    * and that the merkleRoot matches those of the transactions.
    *
    * @return boolean
    */
    public function isValid(): bool
    {
        if ($this->hash !== $this->calculateHash()) {
            return false;
        }

        if ($this->merkleRoot !== $this->calculateMerkleRoot()) {
            return false;
        }

        return true;
    }

    /**
     * Calculates the hash for this Block using the Block header
     *
     * @return string
     */
    public function calculateHash(): string
    {
        $serialized = implode('', array_values($this->header()));

        return dhash($serialized);
    }

    /**
     * Calculates the Merklet Root
     *
     * @return string
     */
    public function calculateMerkleRoot(): string
    {
        $hashedTransactions = $this->hashTransactions($this->transactions);

        return (new MerkleRoot())->calculate($hashedTransactions);
    }

    /**
     * Creates an array of Hashes from transactions
     * @param  array  $transactions
     * @return array  hashes
     */
    private function hashTransactions(array $transactions): array
    {
        $hashes = [];
   
        foreach ($transactions as $transaction) {
            if (! $transaction instanceof Transaction) {
                throw new BlockchainException('Invalid Transaction Object');
            }
            $hashes[] = $transaction->calculateHash();
        }

        return $hashes;
    }

    /**
     * Returns the Block as a JSON string
     *
     * @param array $options The following options keys are supported:
     *  - pretty: default:false toggle JSON pretty print
     * @return string
     */
    public function toJson(array $options = []): string
    {
        $options += ['pretty' => false];
        $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($options['pretty']) {
            $jsonOptions |= JSON_PRETTY_PRINT;
        }

        return json_encode($this->toArray(), $jsonOptions);
    }

    /**
     * Returns this Block as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'version' => $this->version,
            'previousHash' => $this->previousHash,
            'merkleRoot' => $this->merkleRoot,
            'timestamp' => $this->timestamp,
            'difficulty' => $this->difficulty,
            'nonce' => $this->nonce,
            'index' => $this->index,
            'noTransactions' => $this->noTransactions,
            'transactions' => $this->convertTransactions($this->transactions),
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Converts the Transaction Objects into Arrays
     *
     * @param array $transcations
     * @return array
     */
    private function convertTransactions(array $transcations): array
    {
        $out = [];
        foreach ($transcations as $transaction) {
            $out[] = $transaction->toArray();
        }

        return $out;
    }

    /**
     * Creates a Block from a JSON string
     *
     * @param string $json
     * @return \Blockchain\Block
     */
    public static function deserialize(string $json): Block
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BlockchainException('JSON decoding data Error: ' . json_last_error());
        }

        // create Transaction collection
        $transactions = [];
        foreach ($data['transactions'] as $transaction) {
            $tx = new Transaction($transaction['data']);
            $tx->hash = $transaction['hash']; #! Use saved hash here
            $transactions[] = $tx;
        }
        $data['transactions'] = $transactions;
 
        return new Block($data);
    }
}
