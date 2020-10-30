# Blockchain (beta)

A Blockchain database suitable for both private or public blockchains.

- Blocks are stored with transactions as JSON so they are very portable
- Database has been designed to handle large amounts of Blocks and can be quickly synced across multiple servers
- Transactions are encoded in a Merkle Tree, and can detect duplicate transactions being submitted in different blocks.
- Proof of work can be disabled for private blockchains to remove unnecessary and cumbersome calculations
- It is very fast, you can search for the furthest away hash in the index of 1.2 million blocks in 0.37 seconds (2012 laptop)

## Getting started

I will be creating a package soon and have included `demo.php` which will create a `Blockchain` with `Blocks` and `Transactions`

Clone the source code to a directory

```bash
$ git clone https://github.com/jamielsharief/blockchain blockchain-demo
```

Run `composer install`

```php
$ cd blockchain-demo
$ composer install --no-dev
```

Then execute `demo.php`

```bash
$ php demo.php
```

## Create

To create a `Blockchain` instance, provide a path to where data for that Blockchain will be stored.

```php
$blockchain = new Blockchain('customer-payments',__DIR__ . '/data/payments', [
    'difficulty' => 4, // set to 0 to disable proof of work
    'lookback' => 10, // number of Blocks to look back for duplicate transactions
    'version' => 1
]);
```

> For a private Blockchain, set difficulty to 0, since no proof of work is required.

The Blockchain is created when you add the first `Block` of `Transactions`.

Create a `Transaction` object and pass the data to the constructor.

```php
$transaction = new Transaction([
    'date' => '2020-10-27',
    'to' => 'Bob',
    'from' => 'Tony',
    'amount'=> 500
]);
```

Create a `Block` and add a `Transaction` to it, you can add as many `Transactions` as you need for 
each `Block`.

```php
$block = new Block();
$block->add($transaction);
```

To add the `Block` to the `Blockchain`

```php
$blockchain->insert($block);
```

## Finding Blocks

To get a `Block` provide the block number

```php
$block = $blockchain->get(4);
/*
{
    "hash": "0000a1b9dc3c28e3deacf1e862fd299fc7e29637da1c6c847e158141b8ac82cd",
    "version": 1,
    "previousHash": "000048cf5816b30cfb1b4fcf091fd801bd5ba9722a94fb42424df48a7d141fcc",
    "merkleRoot": "2924811dad7aebcc1772414d4296af5e21f636570c825dc8db22768660e92446",
    "timestamp": 1604053905,
    "difficulty": 4,
    "nonce": 116249,
    "index": 4,
    "noTransactions": 1,
    "transactions": [
        {
            "hash": "aa2c26771ca48904af0a5c2924ba8cebfd2bd37a6396a55db7d6939cb158a8dc",
            "data": {
                "date": "2020-10-30 22:31:45",
                "to": "2e6c1c401c5233c5b4e5f8b31938188f1375c4cc",
                "from": "f73c557f3c50b8be433a7a32dfcdd4d236e7ce23",
                "amount": 24087
            }
        }
    ]
}
*/
```



You can also search by hash

```php
$block = $blockchain->find('0000d64537ceca1e65f5dca51bed677c6c6a5665b21725ef87dd6da21994e09c');
```

To get the most recent `Block`

```php
$block = $blockchain->last(); // the last Block
```

## Counting

To count the number of `Blocks` in the `Blockchain`

```php
$blockchain->count();
```

## List

To get a list of hashes of the most recent X `Blocks` with the `Block` number.

```php
$list = $blockchain->list(); // defaults to 10
/*
Array
(
    [9] => 0000b08e7e34f3cafb35602123d3aa18416d2279d36ab702fa9feac8758f6f66
    [8] => 0000963bc54ec3dba730e186bbddd03bf754117057b6d534a4c08610052b5c12
    [7] => 0000e042bdd2208d8187d6166dfb2f4cc9851d8a256178460f9daab56224f9f2
    [6] => 00008e61f6338138705ad6a389e0149e872c60839c5a638efc5cc44953c3820e
    [5] => 00005d7994266b55b1c48ceb79f59bc5fe9d786ab672417a6abba2db3f47a558
    [4] => 0000807d375c5fbe63f13445cec81abaf132eec8fb0f04bf7de8a68c29d173eb
    [3] => 0000fb7f973b48a0a928f190c843820b4bb91ba305330f702035b8d4dc64003f
    [2] => 000043adb9258af0b5b564e96ca20ed6709e4b3ebb84498d1a334b5a185cd788
    [1] => 0000d14502ac90c9047f3f43c96678dc4329db98f9e94c775f72a27d54820e87
    [0] => 0000a543c66f641c0998711f71afe2393ec3ed1b6ecefe979873f1ae4e5c15d2
)
*/
```

To list a custom amount of `Blocks`

```php
$list = $blockchain->list(10000);
```

## All

To go through all `Blocks` in the `Blockchain`, use the `all` method which yeilds each `Block` so that it can be done in a memory efficient manner.

```php
foreach($blockchain->all() as $block){
    $transactions = $block->transactions;
}
```

The following options can be used

- start: default:0 from which block number
- finish: default:[last block] to which block number
- reverse: default: true. Goes through Blocks starting at the most recent block if set

## Validating the Blockchain

> On my 2012 Mac Book it took me approx 9 minutes to validate a Blockchain with 1.2 million Blocks and roughly 6 million Transactions.

To validate the entire `Blockchain`.

```php
$blockchain->validate();
```

## Resources

- [Bitcoin: A Peer-to-Peer Electronic Cash System](https://bitcoin.org/bitcoin.pdf)
- [Block hashing algorithm](https://en.bitcoin.it/wiki/Block_hashing_algorithm)