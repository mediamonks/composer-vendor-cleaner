[![Total Downloads](https://poser.pugx.org/mediamonks/composer-vendor-cleaner/downloads)](https://packagist.org/packages/mediamonks/composer-vendor-cleaner)
[![Latest Stable Version](https://poser.pugx.org/mediamonks/composer-vendor-cleaner/v/stable)](https://packagist.org/packages/mediamonks/composer-vendor-cleaner)
[![Latest Unstable Version](https://poser.pugx.org/mediamonks/composer-vendor-cleaner/v/unstable)](https://packagist.org/packages/mediamonks/composer-vendor-cleaner)
[![License](https://poser.pugx.org/mediamonks/composer-vendor-cleaner/license)](https://packagist.org/packages/mediamonks/composer-vendor-cleaner)

# MediaMonks Composer Vendor Cleaner

Sometimes there are still some unfortunate cases when you need to send your vendor dir to some place and you want it to go as fast as possible.
This package provides a simple script which will try to remove as much stuff as possible without breaking your app.

Don't forget to run ``composer install`` with ``--no-dev`` when creating a package for non-development environments, it will already save you loads space and files if you have packages defined in ``require-dev`!

## How it works

The script simply reads all package dirs within the vendor dir (<vendor>/<package>) and removed files from that dir which are not used for running your project.
For most packages this means it will remove docs and tests but also the composer files, licenses and readme's.

Since not all packages can be cleaned the same way the ``type`` in ``composer.json`` of the package is used to determine the cleaning handler.

Currently these handlers are available:

### SymfonyBundleHandler

Used when the type is set to ``symfony-bundle``, removes the ``Tests`` dir if it's present

### DefaultHandler

For all other types, removes all folders except the ones that are defined in the ``autoload`` section of the composer.json


## Usage

Clean up current vendor dir:

```
php vendor/mediamonks/composer-vendor-cleaner/bin/clean.php
```

Clean up specific vendor dir:

```
php vendor/mediamonks/composer-vendor-cleaner/bin/clean.php --dir /path/to/vendor/
```

## Options

You pass the location of a json file to set some options which can influence the cleaning process:

```
php vendor/mediamonks/composer-vendor-cleaner/bin/clean.php --settings /path/to/options.json
```

Currently these options are available:

-

## Disclaimer

This script is not tested extensively yet so use at your own risk

## We can use your help!

This script was mainly created to clean up Symfony Framework projects but with your help we can make it work for other frameworks too.
Please create your own handlers, improve the cleaning on current handlers and send in a PR.
