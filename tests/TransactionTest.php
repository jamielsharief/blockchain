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

use Blockchain\Transaction;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function testHash()
    {
        $transaction = new Transaction([
            'date' => '2020-10-29 13:13:59',
            'to' => '5f997ca7272d1',
            'from' => '5f997ca7272d2',
            'amount' => 1234
        ]) ;

        $this->assertEquals('74110b5311ff248a53048ea7c45d21386b12fbf368296cafd2fa7e3362108cde', $transaction->calculateHash());
     
        $transaction->data['amount'] = 4567;
        $this->assertEquals('ea578a34b6edf697d572c840d5ca67f8c2834a63ca1a1aac3c12944c19a32aee', $transaction->calculateHash());
    }

    public function testData()
    {
        $transaction = new Transaction();
        $data = [
            'date' => '2020-10-29 13:13:59',
            'to' => '5f997ca7272d1',
            'from' => '5f997ca7272d2',
            'amount' => 1234
        ];
        $transaction->data($data);
        $this->assertEquals($data, $transaction->data());
    }

    public function testToJson()
    {
        $transaction = new Transaction([
            'date' => '2020-10-29 13:13:59',
            'to' => '5f997ca7272d1',
            'from' => '5f997ca7272d2',
            'amount' => 1234
        ]);
        $transaction->hash = $transaction->calculateHash();
        $this->assertEquals('{"hash":"74110b5311ff248a53048ea7c45d21386b12fbf368296cafd2fa7e3362108cde","data":{"date":"2020-10-29 13:13:59","to":"5f997ca7272d1","from":"5f997ca7272d2","amount":1234}}', $transaction->toJson());
    }
}
