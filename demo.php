<?php
/**
 * DEMO Script, each time you run it it will add X amount of Blocks to the Blockchain.
 */

use Blockchain\Block;
use Blockchain\Blockchain;
use Blockchain\Transaction;

require __DIR__ . '/vendor/autoload.php';

// The directory where the Blockchain data will be saved
$path = __DIR__ . '/data';

// The amount of Blocks that will be created when you run this script
$blocks = 5;

// the proof of work (if not needed, then set to 0)
$difficulty = 4;

// the number of blocks to look back at to see if there are duplicate transactions
$lookback = 10;  // set to 0 if you want to speed up generating test Blocks

// Create the instance
$blockchain = new Blockchain('demo-coin', $path, [
    'difficulty' => $difficulty,
    'lookback' => $lookback
]);

// Insert Blocks
for ($i = 0;$i < $blocks;$i++) {
    $block = new Block();
    $rand = rand(1, 5);
    for ($ii = 0;$ii < $rand;$ii++) {
        $rand2 = rand(1, 24);
        $block->add(new Transaction([
            'date' => date('Y-m-d H:i:s', strtotime("+ {$rand2} hour")),
            'to' => $blockchain->generateAddress(),
            'from' => $blockchain->generateAddress(),
            'amount' => rand(1, 100000)
        ]));
    }

    $blockchain->insert($block);
    print("Added Block #{$block->index} with hash {$block->hash}\n");
}

print("\nLast Block:\n");

$latest = $blockchain->last();
print($latest->toJson());

print("\nGoing through all Blocks..\n");

foreach ($blockchain->all(['reverse' => false]) as $block) {
    print("Block #{$block->index}\n");
    print($block->toJson() . "\n");
}

print("\nThere are {$blockchain->count()} Blocks in the Blockchain\n");
print("\nBlockchain is " . ($blockchain->isValid() ? 'valid' : 'not valid'));
