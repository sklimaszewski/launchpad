#!/usr/bin/env bash

SF_HOME="$HOME/.sflaunchpad"
mkdir -p $SF_HOME
cd $SF_HOME

php -r "copy('https://sklimaszewski.github.io/launchpad/installer', 'installer');"
php installer
rm installer

ln -sf $SF_HOME/sf.phar $HOME/sf
chmod +x $HOME/sf

echo "You can now use Symfony Launchpad by running: ~/sf"
echo ""
echo "- You may want to put ~/sf in you PATH"
echo "- You may want to creat an alias (in your .zshrc or .bashrc) alias sf='~/sf'"

~/sf
