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

use function Blockchain\Cryptography\dhash;

/**
 * Transaction/Record
 *
 * @internal I have removed timestamp, but if it is added back, it should not be included
 * in hash.
 */
class Transaction
{
    public ?string $hash = null;
    public array $data = [];
    
    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Sets or gets the data for the Transaction
     *
     * @param array $data
     * @return array|null
     */
    public function data(array $data = null): ? array
    {
        if ($data === null) {
            $data = $this->data;
        }

        return $this->data = $data;
    }

    /**
     * Creates hash of the Transaction (data only)
     *
     * @return string
     */
    public function calculateHash(): string
    {
        return dhash(json_encode($this->data));
    }

    /**
     * Returns the Transaction as an Array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'data' => $this->data
        ];
    }

    /**
     * Converts the Transaction to JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode(
                $this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
    }
}
