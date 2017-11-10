# STK Addons Website
This is the source code for the SuperTuxKart asset sharing and distribution
platform. The official location of the production website is http://addons.supertuxkart.net/.

## Build Status
[![Build Status](https://travis-ci.org/leyyin/stk-addons.svg?branch=master)](https://travis-ci.org/leyyin/stk-addons)

## Branches
The **[master branch](https://github.com/supertuxkart/stk-addons)** contains the latest code, not stable or production ready.

The **[production branch](https://github.com/supertuxkart/stk-addons/tree/production)** contains stable code that is ready for production (the live addons server is based on this branch).

## Installation
The whole installation procedure can be seen in the [INSTALL.md](INSTALL.md) file.

## Common Problems

### Permissions
A common problem on Linux are the permissions for the `assets/cache` and `dl` directories.
There are several ways to solve this problem:
* Change the permission of the directories with `chmod 775` (not recommended)
* Add yourself to the owner group of these directories and give the group read & write access, or change the owner of those directories
to the user under which your webserver is running (usually www-data). The latter can be achieved using:
```sudo chown -R www-data:www-data <directory>```

### Missing extension after install
Sometimes even after you install `mcrypt` extension for PHP it tells you that it is disabled or not available.
The solution is to enable it: `sudo php5enmod mcrypt && sudo service apache2 restart`

### Bower doesn't work
If ```bower --version``` doesn't give any output, it hasn't found the nodejs installation. You can fix that with
```ln -s /usr/bin/nodejs /usr/bin/node```

### Class not found after creating a new class
Example:
```
Error: Class 'Debug' not found in /stk-addons/index.php on line 0
```

This is due to composer not knowing about it. To fix it update composer with `composer update`.

## Testing
The project uses [PHPUnit](http://phpunit.de/) for unit testing (it's installed automatically by composer if you have enabled the developer dependencies)

Run tests from the root of the project with (it will use the default `phpunit.xml` found in the root directory):

    ./vendor/bin/phpunit

If you want to give it a custom configuration use the `--configuration` flag, like this:

    ./vendor/bin/phpunit --configuration custom.xml

## Translation and locales generation
To generate all locales supported (system wide), run the script in [locale/locale-gen.sh](locale/locale-gen.sh).

After that, update the [translations.pot](locale/translations.pot) files by running the [locale/update-pot.sh](locale/update-pot.sh) script.

Then after getting the updated translate `po` files from https://www.transifex.com/supertuxkart/supertuxkart/ run the
[locale/generate-mo.sh](locale/generate-mo.sh) script.

## Contributing
All contributions are welcome: ideas, patches, documentation, bug reports, complaints, etc!

### Git message conventions
Some messages include the prefix `[tag]` at the beginning of the commit message, if present these mean
that you need to make manual modifications to your code/infrastructure for it to work with that commit.
- `[C]` - modified the config file, update your `config.php` file accordingly
- `[D]` - updated the composer/bower dependencies, run the appropriate bower/composer update commands
- `[S]` - updated the SQL schema, modify your SQL schema accordingly


### PHP
The PHP coding standard is heavily based on [PSR-2](http://www.php-fig.org/psr/psr-2/), with some modifications:
* The line limit is 120 characters.
* Opening braces for control structures MUST go on the next line, and closing braces MUST go on the next line after the body.
```php
if ($a === 42)
{
    bar();
}
else
{
    foo();
}
```

### Other

For JavaScript, CSS, and SQL you should use 4 spaces, not tabs.
The JavaScript coding standard is based on http://javascript.crockford.com/code.html and the
CSS coding standard is based on http://make.wordpress.org/core/handbook/coding-standards/css/.

The JavaScript and CSS coding standards are modified to use the same line limit as PHP.

## License
STK Addons Website is licensed under GPL version 3. See [COPYING](COPYING) for the full license text.

## Contact
* Mailing list: [supertuxkart-devel at SourceForge](http://sourceforge.net/p/supertuxkart/mailman/supertuxkart-devel/)
* Forum: [at FreeGameDev Forums](http://forum.freegamedev.net/viewforum.php?f=16)
* IRC: [#supertuxkart on Freenode](https://webchat.freenode.net/?channels=#supertuxkart)
* Twitter: [@supertuxkart](https://twitter.com/supertuxkart)

