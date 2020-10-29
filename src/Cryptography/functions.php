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

/**
 * Double SHA-256 hashing
 *
 * It is belived that Bitcoin uses double hashing to prevent
 * against
 *
 * @link https://en.bitcoin.it/wiki/Protocol_documentation
 * @link https://en.wikipedia.org/wiki/Length_extension_attack
 *
 * @return string
 */
function dhash(string $data): string
{
    return hash('sha256', hash('sha256', $data));
}
