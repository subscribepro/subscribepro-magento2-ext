Subscribe Pro Magento 2 Integration Extension
=============================================

[![Latest Stable Version](https://poser.pugx.org/subscribepro/subscribepro-magento2-ext/v/stable)](https://packagist.org/packages/subscribepro/subscribepro-magento2-ext)
[![Total Downloads](https://poser.pugx.org/subscribepro/subscribepro-magento2-ext/downloads)](https://packagist.org/packages/subscribepro/subscribepro-magento2-ext)
[![Latest Unstable Version](https://poser.pugx.org/subscribepro/subscribepro-magento2-ext/v/unstable)](https://packagist.org/packages/subscribepro/subscribepro-magento2-ext)
[![License](https://poser.pugx.org/subscribepro/subscribepro-magento2-ext/license)](https://packagist.org/packages/subscribepro/subscribepro-magento2-ext)

This is the official Magento 2 extension for Subscribe Pro.

To learn more about Subscribe Pro you can visit us at https://www.subscribepro.com/.

## Installation via Composer

You can install our PHP client via [Composer](http://getcomposer.org/). Please follow these simple steps:

1. Set up the correct path for Composer or keep Composer within Magento root.

2. In Magento root, run command:  
```bash
composer require subscribepro/subscribepro-magento2-ext
```

3. After the above is successful, run this command in Magento root. This will let Magento know about the module.
```bash
php bin/magento module:enable Swarming_SubscribePro
```

4. Run this command in Magento root. This will ensure any installer scripts we may have are executed properly and store the current data version.
```bash
php bin/magento setup:upgrade
```

5. You many need to run this command to deploy any necessary static content.
```bash
php bin/magento setup:static-content:deploy
```

6. Run this command if you have a single website and store:
```bash
php bin/magento setup:di:compile-multi-tenant
```
or this one if you have multiple ones:
```bash
php bin/magento setup:di:compile
```

7. Clear cache from Magento admin.

## Getting Started

Please visit our documentation website and start with our step by step integration guide for Magento 2: https://docs.subscribepro.com/display/spd/Install+Subscribe+Pro+for+Magento+2
