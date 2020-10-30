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
namespace Blockchain\Cryptography;

use InvalidArgumentException;

/**
 * MerkleRoot Class
 * @see https://en.bitcoin.it/wiki/Protocol_documentation#Merkle_Trees
 */
class MerkleRoot
{
    /**
     * Calculates the merkle root
     *
     * @param  array  $hashList array of hashes
     * @return string merkle root
     */
    public function calculate(array $hashList): string
    {
        $pairedHashes = $this->pairHashes($hashList);

        while (count($pairedHashes) > 1) {
            $pairedHashes = $this->pairHashes($pairedHashes);
        }
      
        return end($pairedHashes);
    }
    /**
     * Pairs the hashes
     *
     * @param  array $hashList hashes
     * @return array $pairedHashes
     */
    protected function pairHashes(array $hashList): array
    {
        if (empty($hashList)) {
            throw new InvalidArgumentException('Hashlist is empty');
        }

        //  If the number of hashes is uneven then use last element to make even
        if (count($hashList) % 2 !== 0) {
            $hashList[] = end($hashList);
        }

        $max = count($hashList);

        $pairedHashes = [];
        for ($i = 0; $i < $max; $i += 2) {
            $pairedHashes[] = dhash($hashList[$i] . $hashList[$i + 1]);
        }

        return $pairedHashes;
    }
}
