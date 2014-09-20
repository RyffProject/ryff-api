Ryff Server and API
===================

## About

This repository is the server side code that runs [Ryff](https://github.com/RyffProject). It is written in PHP, and
uses MySQL as a database. There is an [iOS interface for Ryff](https://github.com/RyffProject/ryff-ios) under development, with plans for a [web interface](https://github.com/RyffProject/ryff-web) as well.

Under `public_html/api` are the API scripts that the client interfaces use to login, upload riffs, send messages, get news feeds, etc. Information such as if the user needs to be logged in, what variables to set, and what to expect in return (as JSON) is documented at the top of each file.

## Setup

You must have a web server (only tested with Apache), MySQL, and PHP installed. Some things might not work on older versions of MySQL and PHP.

Clone the respository with `git clone https://github.com/RyffProject/ryff-api.git`. Make a symlink to the `public_html` folder in your web server's root directory (e.g. htdocs).

Make a MySQL user named ryff with access to two databases named ryff and ryfftest. Then Run the SQL install script under `resources/db/install.sql` on the ryff and ryfftest databases.

You will need to edit `resources/config.php` for your setup. The SITE_ROOT will be used for URLs returned by the API. DB_PASS and TEST_DB_PASS will need to be set if you gave a password to your database user. By default TEST_MODE is set to true, and will need to be changed to false before using in a non-testing environment.

### Tests

The test script can be run by

    php resources/tests/tests.php --type=TYPE

where TYPE is `unit` for unit testing models, `api` for testing API scripts, or `populate` for filling the database with test data. You can put sample user avatars, post images, and riff audio under `resources/tests/sample_media` for use with the populate script. Use

    php resources/tests/tests.php --help

for more options (you will want to run populate with `--no-teardown`), or read the documentation at the top of `resources/tests/tests.php`.

### Cron

Under the `cron` directory are the php files that should be run regularly via cron.

The `cron/send-push-notifications.php` script should be run as often as possible to send out push notifications.
Apple Push Notifications are the only type sent right now. You will need to edit `resources/config.php` with your
APNs credentials.

## Contributors

* [Robert Fotino](https://github.com/rfotino)
* [Chris Laganiere](https://github.com/ChrisLaganiere)

## Contact

If you would like to contribute or have any questions or comments, email robert.fotino@gmail.com.

## License

The Ryff API is released under the Apache License, Version 2.0.
