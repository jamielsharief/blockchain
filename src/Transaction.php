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
     * Returns the Transaction as a JSON string
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
    * @return string
    */
    public function __toString()
    {
        return $this->toJson();
    }
}
