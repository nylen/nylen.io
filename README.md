# nylen.io

This is the PHP code behind
[my personal/professional website](https://nylen.io/).

For more info about this site, see https://nylen.io/this-site/.

## Server requirements

- _(In production)_ apache2 with `mod_rewrite` enabled
- PHP XML extensions installed (`SimpleXmlElement` available)
- `flock` available and working (the site is served from a fast, local
  filesystem, for example; see
  [this article](http://0pointer.de/blog/projects/locking.html)
  for more caveats)
- `composer` dependencies installed, or required `composer` packages otherwise
  installed into the `vendor/` directory

## Local development

- Pick a hostname and alias it to `127.0.0.1`, e.g. `nylen.localhost`
- Run `composer install`
- Run `php -S nylen.localhost:8000 router.php`
- Visit `http://nylen.localhost:8000/`

## Production hosting

- Run `composer install`
- Set up Apache and a virtual host for the site
- Add `ErrorDocument 404 /404/` to the site's `.htaccess` file
- _(To be improved)_ Symlink each directory in `loaders/` to the site root

## Credits

Thanks to @michelf for the excellent
[`php-markdown`](https://github.com/michelf/php-markdown)
library.
