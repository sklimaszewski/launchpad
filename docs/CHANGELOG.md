# Symfony Launchpad

## CHANGELOG

### 3.2.0
- Add configurable main container and project folder name

### 3.1.0
- Add docker context support for mutagen extension

### 3.0.0
- Updating to docker compose v2
- Add legacy support for abandoned eZLaunchpad stack

### 2.0.0
- Add support for PHP8+ and Symfony 6
- Removed NFS support for MacOS
- Support additional docker-compose.yml files for MacOS and ARM64
- Better configuration by separating project into 2 files - .sflaunchpad.yml and .sflaunchpad.local.yml
- Allow configuring docker ENV variables from launchpad YAML file
- Generate .gitignore automatically
- Fixing storage import
- Updating documentation

### 1.1.1
- Updating documentation

### 1.1.0
- Renaming repository
- Updating dependencies
- Cleaning up code

### 1.0.11
- Fixing MongoDB dump command

### 1.0.10
- Adding MongoDB improvements

### 1.0.9
- Fixing build command

### 1.0.8
- Fixing build command

### 1.0.7
- Build command improvements

### 1.0.6
- Updating nginx default configuration

### 1.0.5
- Disable and remove blackfire

### 1.0.4
- Docker volume fix

### 1.0.3
- Build improvements

### 1.0.2
- Adding `.dockerignore` file to reduce docker image size

### 1.0.1
- Cleaning up D4M implementation

### 1.0.0 
- Initial Stable Version forked from ezsystems/launchpad
