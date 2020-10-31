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
namespace Blockchain\Database;

use Blockchain\Block;
use Blockchain\Exception\BlockchainException;

class Index
{
    protected Filesystem $fs;
    protected string $path;
    
    protected int $recentBlocks = 1000;

    public function __construct(string $path)
    {
        $this->fs = new Filesystem();
        $this->path = $path . '/blockchain.idx';
    }

    /**
     * Adds a Block to the Index
     *
     * @param \Blockchain\Block $block
     * @return void
     */
    public function add(Block $block): void
    {
        if (! $this->fs->append($this->path, "{$block->index},{$block->hash}\n")) {
            throw new BlockchainException("Error adding block #{$block->index} to index");
        }
    }

    /**
     * Gets the index for the last block
     *
     * @return integer
     */
    public function last(): int
    {
        $lastLine = $this->fs->lastLine($this->path, 100);

        list($index, ) = explode(',', $lastLine);

        return (int) $index;
    }

    /**
     * Returns a list of hashes for the most recent Blocks
     *
     * @param integer $amount
     * @return array
     */
    public function list(int $amount): array
    {
        $out = [];

        foreach ($this->fs->each($this->path, $amount) as $line) {
            list($index, $hash) = explode(',', $line);
            $out[$index] = $hash;
        }

        return $out;
    }

    /**
     * Searches for a Hash in the index
     *
     * @param string $hash
     * @return integer|null
     */
    public function search(string $hash): ?int
    {
        if ($this->recentBlocks > 0) {
            $result = $this->searchRecentBlocks($hash);

            if ($result !== null) {
                return $result;
            }
        }

        return $this->searchForward($hash);
    }

    /**
     * Standard search forward, faster than backwards since it is more efficient.
     *
     * @param string $hash
     * @return integer|null
     */
    protected function searchForward(string $hash): ? int
    {
        $line = $this->fs->search($this->path, $hash);
        if ($line) {
            list($index, ) = explode(',', $line);

            return (int) $index;
        }

        return null;
    }

    /**
     * Recency search optimisation. Even without this searching for the furthest
     * away hash for 2 million blocks takes 0.44 seconds
     *
     * @param string $hash
     * @return integer|null
     */
    protected function searchRecentBlocks(string $hash): ? int
    {
        foreach ($this->fs->each($this->path, $this->recentBlocks) as $line) {
            if (strpos($line, $hash) !== false) {
                list($index, $hash) = explode(',', $line);

                return (int) $index;
            }
        }

        return null;
    }
}
