# Symfony Launchpad

## Contribution

This project comes with Coding Standards and Tests.
To help you contribute a Makefile is available to simplify the actions.

```bash
$ make
Symfony Launchpad available targets:
  clean        > removes the vendors, caches, etc.
  codeclean    > run the codechecker
  install      > install vendors
  phar         > build the phar locally into your home
```

Please comply with `make codeclean` before to push, your PR won't be merged otherwise.