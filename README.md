# Symfony Launchpad

Symfony Launchpad is a CLI tool to start a Symfony project in 5 min on top of a full Docker stack.

It is fully based on eZ Launchpad created for eZ Platform / Ibexa installations.

You can find the full documentation here: https://sklimaszewski.github.io/symfony-launchpad

## Changes added to the base eZ Launchpad

- [x] Allow for DATABASE_URL env variable usage
- [x] Removed Ibexa-specific functionalities/code
- [x] Platform.sh support replaced with K8S tools
- [x] Reduce main engine docker image size and build time

## Possible future improvements

- [ ] Dump/import databases from db container instead of symfony
- [ ] Setup xdebug configuration
- [ ] Allow for postgresql database
- [ ] Bring back testing

## Contribution

[CONTRIBUTING](CONTRIBUTING.md)

## License

This project is under the MIT license. See the complete license in the file:

[LICENSE](LICENSE)



