# bit-array [![CI](https://github.com/nikeee/php-bit-array/actions/workflows/CI.yml/badge.svg)](https://github.com/nikeee/php-bit-array/actions/workflows/CI.yml) [![CD](https://github.com/nikeee/php-bit-array/actions/workflows/CD.yml/badge.svg)](https://github.com/nikeee/php-bit-array/actions/workflows/CD.yml) [![Packagist Version](https://img.shields.io/packagist/v/nikeee/bit-array)](https://packagist.org/packages/nikeee/bit-array)
A bit-array implementation for PHP. Compatible with the BitArray of [ts-ds](https://github.com/nikeee/ts-ds).

Depending on the extensions that are available, `GMP` is used under the hood for faster bit operations.

## Installation

```sh
composer require nikeee/bit-array
```

## Usage
```php
<?php
require_once 'vendor/autoload.php';

use Nikeee\BitArray\BitArray;

$arr = BitArray::create(8);

$arr->set(1, true)
    ->set(2, true)
    ->set(4, true);

$arr->applyBitwiseNot();

echo "Bits: " . $arr->toBitString() . "\n";
```
