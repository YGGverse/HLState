# HLState <img align="right" width="128" height="128" src="https://github.com/YGGverse/HLState/blob/main/public/mstile-150x150.png?raw=true" alt="HLState" />

Web monitor for Half-Life game servers based on [Xash3D FWGS](https://github.com/FWGS/xash3d-fwgs) masters crawler

## Features

* Live scrape of each server for online status, active map, players total
* Game session info: names, frags, time in game, etc
* Subscription for online updates and players join to server with RSS
* History keeping in SQLite DB for any charts building
* New instances crawler based on multiple master nodes
* Flexible environment settings

## Examples

* `http://94.140.114.89/hl/` - Clearnet
* `http://[201:5eb5:f061:678e:7565:6338:c02c:5251]/hl/` - Yggdrasil
* `http://hl.ygg` - Alfis DNS

## Install

* `apt install git composer curl php php-xml php-intl php-mbstring php-curl php-sqlite3`
* `git clone https://github.com/YGGverse/HLState.git`
* `cd HLState`
* `composer install`
* `php bin/console doctrine:migrations:migrate`

### Setup

* `chown -R www-data:www-data var`
* `cp .env .env.local`
* `crontab -e` > `* * * * * /usr/bin/curl --silent http://localhost/crontab/index &> /dev/null`

### Update

* `git pull`
* `git merge`
* `composer update`
* `php bin/console doctrine:migrations:migrate`
* `APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear`

## Contribution

Please create new branch from main before make PR

* `git checkout main`
* `git checkout -b 'new-commit-branch'`

## License

* Engine sources [MIT License](https://github.com/YGGverse/HLState/blob/main/LICENSE)

## Versioning

[Semantic Versioning 2.0.0](https://semver.org/#semantic-versioning-200)

## Components

* [Symfony Framework](https://symfony.com)
* [SVG icons](https://icons.getbootstrap.com)
* [PHP Source Query](https://github.com/xPaw/PHP-Source-Query)
* [HL-PHP](https://github.com/YGGverse/hl-php)
* [Favicons](https://realfavicongenerator.net)

## Support

* [Issues](https://github.com/YGGverse/HLState/issues)

## Blog

* [Mastodon](https://mastodon.social/@YGGverse)

## See also

* [Xash3D FWGS builds for Yggdrasil](https://github.com/YGGverse/xash3d-fwgs/releases)
* [half-life-server](https://github.com/YGGverse/half-life-server) - Half-Life server edition by YGGverse
* [pymaster](https://github.com/YGGverse/pymaster) - Refactored master server written in Python
