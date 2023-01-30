<p align="center">
  <a href="https://github.com/postpayio/magento2/releases"><img src="https://img.shields.io/github/release/postpayio/magento2.svg" alt="Latest Version"/></a> <a href="https://travis-ci.com/postpayio/magento2"><img src="https://img.shields.io/travis/postpayio/magento2.svg" alt="Build Status"/></a>
</p>

# Postpay for Magento 2 

[Postpay](https://postpay.io) Payment Gateway for Magento 2.

## Installation

The recommended way to install postpay is through Composer:

```sh
composer require postpay/magento2
```

Register the extension:

```sh
bin/magento setup:upgrade
```

Recompile your Magento project:

```sh
bin/magento setup:di:compile
```

Clean the cache:

```sh
bin/magento cache:clean
```
