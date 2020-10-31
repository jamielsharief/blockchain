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
    private function transactionFixture()
    {
        return [
            'date' => '2020-10-29 13:13:59',
            'to' => '5f997ca7272d1',
            'from' => '5f997ca7272d2',
            'amount' => 1234
        ];
    }
    public function testHash()
    {
        $transaction = new Transaction($this->transactionFixture());

        $this->assertEquals('74110b5311ff248a53048ea7c45d21386b12fbf368296cafd2fa7e3362108cde', $transaction->calculateHash());
     
        $transaction->data['amount'] = 4567;
        $this->assertEquals('ea578a34b6edf697d572c840d5ca67f8c2834a63ca1a1aac3c12944c19a32aee', $transaction->calculateHash());
    }

    public function testData()
    {
        $transaction = new Transaction();
        $data = $this->transactionFixture();
        $transaction->data($data);
        $this->assertEquals($data, $transaction->data());
    }

    public function testToJson()
    {
        $transaction = new Transaction($this->transactionFixture());
        $transaction->hash = $transaction->calculateHash();
        $this->assertEquals('{"hash":"74110b5311ff248a53048ea7c45d21386b12fbf368296cafd2fa7e3362108cde","data":{"date":"2020-10-29 13:13:59","to":"5f997ca7272d1","from":"5f997ca7272d2","amount":1234}}', $transaction->toJson());
    }

    public function testToJsonPretty()
    {
        $transaction = new Transaction($this->transactionFixture());
        $transaction->hash = $transaction->calculateHash();
  
        $this->assertEquals(
            'e682b7f2710263eeef65f7b4c0eda925', md5($transaction->toJson(['pretty' => true]))
        );
    }

    public function testToString()
    {
        $transaction = new Transaction($this->transactionFixture());
        $transaction->hash = $transaction->calculateHash();
        
        $this->assertEquals('{"hash":"74110b5311ff248a53048ea7c45d21386b12fbf368296cafd2fa7e3362108cde","data":{"date":"2020-10-29 13:13:59","to":"5f997ca7272d1","from":"5f997ca7272d2","amount":1234}}', (string) $transaction);
    }
}
