# VolunteerSystem
This work is based on the [engelsystem](https://engelsystem.de).

Since the VolunteerSystem is open source, you can help improving it.
We really love to get pull requests containing fixes or improvements.

### Requirements
 * PHP >= 8.2
   * Required modules:
     * dom
     * json
     * mbstring
     * PDO
       * mysql
     * tokenizer
     * xml/libxml/SimpleXML
     * xmlwriter
 * MySQL-Server >= 5.7.8 or MariaDB-Server >= 10.7
 * Webserver, i.e. lighttpd, nginx, or Apache

From previous experience, 2 cores and 2GB ram are roughly enough for up to 1000 Volunteers (~700 arrived + 500 arrived but not working) during an event.

### Configuration and Setup
 * The webserver must have write access to the `storage` directory and read access for all other directories
 * The webserver must point to the `public` directory.
 * The webserver must read the `.htaccess` file and `mod_rewrite` must be enabled

 * Recommended: Directory Listing should be disabled.
 * There must be a MySQL database set up with a user who has full rights to that database.
 * If necessary, create a `config/config.php` to override values from `config/config.default.php`.
   * To disable/remove values from the following lists, set the value of the entry to `null`:
     * `themes`
     * `tshirt_sizes`
     * `headers`
     * `header_items`
     * `footer_items`
     * `locales`
     * `contact_options`
 * To import the database, the `bin/migrate` script has to be run. If you can't execute scripts, you can use the `initial-install.sql` file from the release zip.
 * In the browser, login with credentials `admin` : `asdfasdf` and change the password.

The Volunteersystem can now be used.

### Session Settings
 * Make sure the config allows for sessions.
 * Both Apache and Nginx allow for different VirtualHost configurations.

[GPL License](LICENSE)

# Development

Please also read the [CONTRIBUTING.md](CONTRIBUTING.md).

## Dev requirements
 * Node >= 14 (Development/Building only)
   * Including npm
 * Yarn (Development/Building only)
 * PHP Composer (Development/Building only)

## Code style
Please ensure that your pull requests follow the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style guide.
You can check that by running
```bash
composer run phpcs
# with docker
docker exec volunteersystem_dev-es_workspace-1 composer run phpcs
```
You may auto fix reported issues by running
```bash
composer run phpcbf
# with docker
docker exec volunteersystem_dev-es_workspace-1 composer run phpcbf
```

## Pre-commit hooks
You should set up the pre-commit hook to check the code style and run tests on commit:

Docker (recommended):

```sh
echo "docker exec volunteersystem_dev-es_workspace-1 bin/pre-commit" > .git/hooks/pre-commit
chmod u+x .git/hooks/pre-commit
```

Host machine:

```sh
ln -s ../../bin/pre-commit .git/hooks/pre-commit
```

## Docker

> [!TIP]
> We suggest using Docker for the Development local build.  
> This repo [ships a docker setup](docker/dev) for a quick development start.  
> If you use another uid/gid than 1000 on your machine you have to adjust it in [docker/dev/.env](docker/dev/.env).


Make sure you're in the `docker/dev` subfolder: 

```bash
cd docker/dev
```

Then, run
```bash
docker compose up
```

Run these commands once initially and then as required after changes

```bash
# Install composer dependencies
docker compose exec es_workspace composer i

# Install node packages
docker compose exec es_workspace yarn install

# Run a full front-end build
docker compose exec es_workspace yarn build

# Or run a front-end build for specific themes only, e.g.
docker compose exec -e THEMES=0,1 es_workspace yarn build

# Update the translation files
docker compose exec es_workspace find /var/www/resources/lang -type f -name '*.po' -exec sh -c 'msgfmt "${1%.*}.po" -o"${1%.*}.mo"' shell {} \;

# Run the migrations
docker compose exec es_workspace bin/migrate
```

While developing you may use the watch mode to rebuild the system on changes

```bash
# Run a front-end build and update every time a JS or CSS file is changed (not translation files!)
docker compose exec es_workspace yarn build:watch

# Or build and update on change for specific themes only to save build time, e.g.
docker compose exec -e THEMES=0,1 es_workspace yarn build:watch
```

> [!NOTE]
> Wait some time (up to a few minutes) after running `yarn build:watch` – it may look like it's stalling, but it's not.


It might also be useful to have an interactive database interface for which a phpMyAdmin instance can be startet at [http://localhost:8888](http://localhost:8888).
```bash
docker compose --profile dev up
```

## Localhost
You can find your local Volunteersystem on [http://localhost:5080](http://localhost:5080).

## Local build without Docker
The following instructions explain how to get, build and run the latest Volunteersystem version directly from the git main branch (may be unstable!).

* Clone the main branch: `git clone https://github.com/volunteersystem/volunteersystem.git`
* Install [Composer](https://getcomposer.org/download/) and [Yarn](https://yarnpkg.com/en/docs/install) (which requires [Node.js](https://nodejs.org/en/download/package-manager/))
* Install project dependencies:
  ```bash
  composer install
  yarn
  ```
  On production systems it is recommended to use
  ```bash
  composer install --no-dev
  composer dump-autoload --optimize
  ```
  to install the Volunteersystem
* Build the frontend assets
  * All
    ```bash
    yarn build
    ```
  * Specific themes only by providing the `THEMES` environment variable, e.g.
    ```bash
    THEMES=0,1 yarn build
    ```
* Generate translation files
  ```bash
  find resources/lang/ -type f -name '*.po' -exec sh -c 'msgfmt "${1%.*}.po" -o"${1%.*}.mo"' shell {} \;
  ```

## Testing
To run only unit tests (tests that should not change the Volunteersystem state) use
```bash
vendor/bin/phpunit --testsuite Unit
```

If a database is configured and the Volunteersystem is allowed to mess around with some files, you can run feature tests.
The tests can potentially delete some database entries, so they should never be run on a production system!
```bash
vendor/bin/phpunit --testsuite Feature
```

When you want to run unit and feature tests at once:
```bash
vendor/bin/phpunit
```

To generate code coverage reports it's highly recommended to use [`pcov`](https://github.com/krakjoe/pcov) or
at least `phpdbg -qrr`(which has problems with switch case statements) as using Xdebug slows down execution.
```bash
php -d pcov.enabled=1 -d pcov.directory=. vendor/bin/phpunit --coverage-text
```

For better debug output, adding `-vvv` might be helpful.
Adding `--coverage-html public/coverage/` exports the coverage reports to the `public/` dir which then can be viewed at [localhost:5080/coverage/index.html](http://localhost:5080/coverage/index.html).

### Docker
If using the Docker-based development environment  you can run the following script to retrieve a coverage report.
```sh
docker compose exec es_workspace composer phpunit:coverage
```

A browsable HTML version is available at http://localhost:5080/coverage/index.html .


### Var Dump server
Symfony Var Dump server is configured to allow for easier debugging. It is not meant as a replacement for xdebug but can actually be used together with xdebug.
The Var Dump Server is especially useful if you want to debug a request without messing up the output e.g. of API calls or the HTML layout.

To use simply call the method `dump` and pass the arguments in exactly the same way you would when using `var_dump`.

This will send the output to the Var Dump server which can be viewed in the terminal.
This does however require that you start the var-dump-server otherwise the output will be printed in your browser

You can also `dump` and `die` if you wish to not let your code continue any further by calling the `dd` method

To view the output of `dump` call the following commands:

```bash
vendor/bin/var-dump-server
# or for running in docker
docker compose exec es_server vendor/bin/var-dump-server
```

For more information check out the Var Dump Server documentation: [Symfony VarDumper](https://symfony.com/components/VarDumper)

## Translation
We use gettext. You may use POEdit to extract new texts from the sourcecode.
Please config POEdit to extract also the twig template files using the following settings: https://gist.github.com/jlambe/a868d9b63d70902a12254ce47069d0e6


## CI & Build Pipeline

The Volunteersystem can be tested and automatically deployed to a testing/staging/production environment.
This functionality requires a [GitLab](https://about.gitlab.com/) server with a working docker runner.

To use the deployment features the following secret variables need to be defined (if undefined the step will be skipped):
```bash
SSH_PRIVATE_KEY         # The ssh private key
STAGING_REMOTE          # The staging server, e.g. user@remote.host
STAGING_REMOTE_PATH     # The path on the remote server, e.g. /var/www/volunteersystem
PRODUCTION_REMOTE       # Same as STAGING_REMOTE but for the production environment
PRODUCTION_REMOTE_PATH  # Same as STAGING_REMOTE_PATH but for the production environment
```

## Static code analysis

You can run a static code analysis with this command:

```bash
composer phpstan
```

**Hint for using Xdebug with *PhpStorm***

For some reason *PhpStorm* is unable to detect the server name.
But without a server name it's impossible to set up path mappings.
Because of that the docker setup sets the server name *volunteersystem*.
To get Xdebug working you have to create a server with the name *volunteersystem* manually.

## Troubleshooting

### Docker version
If unspecific issues appear try using Docker version >= 20.10.14.

### `service "es_workspace" is not running`
Make sure you're running your docker commands from the `docker/dev` directory, not from `docker`

### `main` is broken after pulling the latest commits from upstream
Try running
```bash
composer install
```
from this repository's root directory.
If dependencies have been updated in `composer.json` since you last synced `main`, this should fix it.


# Contributing
## Coding guide lines
* Make sure your code follows the [PSR-12](https://www.php-fig.org/psr/psr-12/) code style and is [.editorconfig](.editorconfig) valid.
  You may use `composer run phpcs` and [Editorconfig-Checker](https://editorconfig-checker.github.io) to verify that.
* You should use an [editorconfig plugin for your editor](https://editorconfig.org/#pre-installed) for automatic basic code formatting.
* Use `use` statements wherever possible instead of writing the fully qualified name.
* Code must pass PHPStan checks (`composer phpstan`)
* Order the composer/npm dependencies alphabetically.
* Do not use code from the [includes](includes) directory anywhere else.
* Don't refactor [includes](includes) code just for the sake of change, it is legacy code that must only be replaced.
* Please cover your code by unit tests, our goal is to stay at 100% line coverage.
  Code under `includes` does not require tests as it's mostly not testable and needs to be rewritten.
* Do not use vendor prefixes like `-webkit` in styles.
  This is done by PostCSS + Autoprefixer according to the [`.browserslistrc`](.browserslistrc).
* Translations must be abbreviated, for example `form.save`.
  The `default.po` files contain translations that can be auto-detected using Poedit, `additional.po` contains generated messages like validations.
* JavaScript code must pass the checks `yarn lint`.
  Auto-fixing is supported via `yarn lint:fix`.
* Don't put function calls in a template-literal (template-strings).

## Pull requests
* The PR should contain a short overview of the changes.
* Before implementing bigger changes, please open an issue to discuss the feature and possible implementation options.
* Please create single pull requests for every feature instead of creating one big monster of pull request containing a complete rewrite.
* Squash similar commits to make the review easier.
* For visual changes, include both before and after screenshots to easily compare and discuss changes.

## Commits
* The commit message must be meaningful. It should serve as a short overview of the changes.
  If needed, an additional description can be provided.
* A commit should be self-contained and result in a working Volunteersystem.
