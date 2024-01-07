# HLState

Web Stats for Half-Life based on [HLServers](https://github.com/YGGverse/HLServers) Registry

![HLState](https://github.com/YGGverse/HLState/assets/108541346/e8559edf-8429-496e-afbb-752a822cd3d6)

## Install

* `apt install git composer curl php php-xml php-intl php-mbstring php-curl php-sqlite3`
* `git clone https://github.com/YGGverse/HLState.git`
* `cd HLState`
* `composer install`
* `php bin/console doctrine:schema:update --force`

### Setup

* `chown -R www-data:www-data var`
* `cp .env .env.local`
* `crontab -e` > `* * * * * /usr/bin/curl --silent http://localhost/crontab/online &> /dev/null`

## Update

* `composer update`
* `php bin/console doctrine:migrations:migrate`
* `APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear`

## License

* Engine sources [MIT License](https://github.com/YGGverse/HLState/blob/main/LICENSE)

## Versioning

[Semantic Versioning 2.0.0](https://semver.org/#semantic-versioning-200)

## Components

* [Symfony Framework](https://symfony.com)
* [SVG icons](https://icons.getbootstrap.com)
* [PHP Source Query](https://github.com/xPaw/PHP-Source-Query)

## Support

* [Issues](https://github.com/YGGverse/HLState/issues)

## Blog

* [Mastodon](https://mastodon.social/@YGGverse)

## See also

* [HLServers](https://github.com/YGGverse/HLServers) - Half-Life Servers Registry for HLState
* [half-life-server](https://github.com/YGGverse/half-life-server) - Half-Life server edition by YGGverse
* [pymaster](https://github.com/YGGverse/pymaster) - Refactored master server written in Python
