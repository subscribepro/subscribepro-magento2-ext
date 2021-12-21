Subscribe Pro Magento 2 Integration Extension
=============================================

[![Latest Stable Version](https://poser.pugx.org/subscribepro/subscribepro-magento2-ext/v/stable)](https://packagist.org/packages/subscribepro/subscribepro-magento2-ext)
[![Total Downloads](https://poser.pugx.org/subscribepro/subscribepro-magento2-ext/downloads)](https://packagist.org/packages/subscribepro/subscribepro-magento2-ext)
[![Latest Unstable Version](https://poser.pugx.org/subscribepro/subscribepro-magento2-ext/v/unstable)](https://packagist.org/packages/subscribepro/subscribepro-magento2-ext)
[![License](https://poser.pugx.org/subscribepro/subscribepro-magento2-ext/license)](https://packagist.org/packages/subscribepro/subscribepro-magento2-ext)

This is the official Magento 2 extension for Subscribe Pro.

To learn more about Subscribe Pro you can visit us at https://www.subscribepro.com/.

## Getting Started

Please visit our documentation website and start with our step by step integration guide for Magento 2: https://docs.subscribepro.com/display/spd/Install+Subscribe+Pro+for+Magento+2

## Installation via Composer

You can install our Subscribe Pro Magento 2 extension via [Composer](http://getcomposer.org/). Please run these commands at the root of your Magento install:
 ```bash
 composer require subscribepro/subscribepro-magento2-ext
 php bin/magento module:enable Swarming_SubscribePro
 php bin/magento setup:upgrade
 ```

## Coding Standards

Subscribe Pro team follows the standards described in https://devdocs.magento.com
 - https://devdocs.magento.com/guides/v2.4/coding-standards/bk-coding-standards.html - this document's purpose is to explain how the code should be formatted and the main idea for PHP developers is **"use codesniffer"**. Helpful and concise instructions on how to set it up are provided. The rules imposed by codesniffer are based on **PSR12** standard (see https://www.php-fig.org/psr/psr-1/ https://www.php-fig.org/psr/psr-2/ https://www.php-fig.org/psr/psr-12/) and are arguably too numerous for humans to remember and consistently apply, so official Magento team does not provide a human-readable description anyway
 - https://devdocs.magento.com/guides/v2.4/coding-standards/technical-guidelines.html - this document describes semantic requirements and best coding practices
 - https://devdocs.magento.com/guides/v2.4/coding-standards/code-standard-javascript.html - JS coding standard
 - https://devdocs.magento.com/guides/v2.4/coding-standards/code-standard-less.html - less coding standard
 - https://devdocs.magento.com/guides/v2.4/coding-standards/code-standard-html.html - HTML coding standard
 
### Subscribe Pro's internal coding standards
 - Use fully-qualified class names in PHPDoc, e.g.,
    ```
    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
     ```
    This is expected to make the code more clear when skimming through, leaves less ambiguity when dealing with classes that are named similarly but belong to different namespaces, in IDE there is no need to ctrl+click twice to actually see the class if an alias is used. This also eradicates the situations when an unnecessary use operator is included only for the sake of writing a phpdoc/comment.
 - No space after type cast, e.g.,
    ```
    (int)$variable
    ```
      None of the existing PHP standards stipulates this at the moment of writing, so "no space" was elected.

### To confirm project is compliant with standards

```bash
vendor/bin/phpcs --standard=Magento2 app/code/Swarming/SubscribePro
```
