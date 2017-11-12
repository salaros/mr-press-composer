Mr.Press Composer
=================

[![Package license](https://img.shields.io/packagist/l/salaros/mr-press-composer.svg)](https://packagist.org/packages/salaros/mr-press-composer)
[![Packagist type](https://img.shields.io/badge/Packagist-library-pink.svg)](https://packagist.org/packages/salaros/mr-press-composer)
[![Packagist downloads](https://img.shields.io/packagist/dt/salaros/mr-press-composer.svg)](https://packagist.org/packages/salaros/mr-press-composer)
[![Monthly Downloads](https://poser.pugx.org/salaros/mr-press-composer/d/monthly)](https://packagist.org/packages/salaros/mr-press-composer)
[![Latest Stable Version](https://img.shields.io/packagist/v/salaros/mr-press-composer.svg)](https://packagist.org/packages/salaros/mr-press-composer)
[![composer.lock](https://poser.pugx.org/salaros/mr-press-composer/composerlock)](https://packagist.org/packages/salaros/mr-press-composer)

Set of [Composer scripts](https://getcomposer.org/doc/articles/scripts.md) used during installation and configuration of [Mr. Press](https://github.com/salaros/mr-press) project.

Currently Mr.Press Composer's script is capable of:
* creating WordPress database (defined in .env file) if it doesn't exist
* installs WordPress (creates and fills tables in the database) using the credentials defined in .env file
* activates plugins installed via [Composer](https://getcomposer.org/doc/00-intro.md#dependency-management) and [WordPress Packagist](https://wpackagist.org/)
* generating WordPress salts via [WP Salts](https://github.com/salaros/wp-salts)
* creating a cron job as [www-data user](https://askubuntu.com/questions/873839/what-is-the-www-data-user) if `DISABLE_WP_CRON` is true using [PHP Crontab Manager](https://github.com/qi-interactive/php-crontab-manager)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
