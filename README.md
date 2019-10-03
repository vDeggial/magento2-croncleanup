# Cron Cleanup - Magento 2 Module

## Requirements
- Magento 2.x.x
- [Hapex Core module](https://github.com/shinoamakusa/m2-core)

## Installation
- Upload files to `app/code/Hapex/CronCleanup`
- Run `php bin/magento setup:upgrade` in CLI
- Run `php bin/magento setup:di:compile` in CLI
- Run `php bin/magento setup:static-content:deploy -f` in CLI
- Run `php bin/magento cache:flush` in CLI
