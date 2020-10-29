# Blockchain (beta)

I will be creating a package soon and have included `demo.php` which will create a `Blockchain` with `Blocks` and `Transactions`

Clone the source code to a directory

```bash
$ git clone https://github.com/jamielsharief/blockchain blockchain-demo
```

Then run the demo file

```bash
$ cd blockchain-demo
$ php demo.php
```

## Create

To create a `Blockchain` instance, provide a path to where data for that Blockchain will be stored.

```php
$blockchain = new Blockchain('customer-payments',__DIR__ . '/data/payments', [
    'difficulty' => 4, // set to 0 to disable proof of work
    'version' => 1
]);
```

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
$block = $blockchain->get(1000);
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

To get a list of hashes of the most recent X `Blocks`

```php
$list = $blockchain->list(); // defaults to 10
$list = $blockchain->list(10000);
```

## All

To go through all `Blocks` in the `Blockchain`

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

To validate the `Blockchain`

```php
$isValid = $blockchain->isValid();
```