# Requirements checker

Simple PHP requirements checker for hosting environments

## Usage

This is a standalone script, simply copy this to your hosting environment and run on the CLI:

```
php requirements.php
```

You can optionally email the output via the email parameter:

```
php requirements.php --email=name@domain.com
```

## Configuration

Add requirements to the `$req` array. This accepts:

* php_version - minimum PHP version required
* modules - PHP modules required
* ini - PHP ini settings required

For ini settings express boolean ini settings as `'1'` or `'0'`

A limited number of ini settings are supported at present, you can add new ones at `check_php_ini`

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Simon R Jones](https://github.com/simonrjones)
