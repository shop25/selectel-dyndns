# Selectel Dynamic DNS

Create or update subdomain for the local machine ip address.

## Installation
```shell
git clone https://github.com/yakimov/selectel-dyndns.git
cd selectel-dyndns
composer install
cp .env.example .env
```

## Settings
Set up parameters in .env file

## Running
```shell
php selectel-dyndns.php
```

You can set alternative subdomain name.
```shell
php selectel-dyndns.php -s test
```
In this case will be created test.domain.com<br>

### Crontab
Add this script to crontab for permanent refreshing your machine address.
```shell
*/15 * * * * php /usr/local/selectel-dyndns/selectel-dyndns.php
```
