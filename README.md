# php-gae-util
Utility belt for common tasks and patterns on Google App Engine for PHP.
The goal is to make development of microservices on Google App Engine
go fast and smooth. Handling every common App Engine spesifikk scenario.

## Modules
* **Auth:** Handles several topics around getting users autenticated.
Inkluding.
* **Cached:** Just a simple wrapper for memcache to make this code better.
* **Conf:** A wrapper around `hassankhan/config` which provides a lightweighhed
library for doing such stuff. This wrapper is adapted for GAE.
* **Fetch:** Simple module to ensure service to service communication.
* **JWT:** My module to handle all the work on JWT-tokens. Wrapper around
`firebase/php-jwt`
* **Secrets:** Module to handle keeping secrets secret. Using Google KMS
to secure passwords and tokens.


### Coding style
The liberary consist of several separate Classes that really just form
a set of functions which should be fairly simple to introduce to code.
From version 0.7.0 every static method and function is written in
camel_case. Classnames are still written in Camel Case.

The static paradigm is just a codesmell. I like it this way cause it
creates very clear dependencies and should be easier to test.

### Testing
Test-coverage is an important part of creating reusable, reliable code.
The goal of this testing is to use phpunit as this is the most used
PHP test framework.

#### Setup for testing

```bash
$ gcloud components install cloud-datastore-emulator
$ gcloud beta emulators datastore start
```


#### For local development
My strategy for developing packages for packagist is as following.

* Create a local folder where you symlink packages

In

~/composer/config.json

add, this also works with using the projects composer.json file, but
then you might get problems on other developers computers and in
pipelines.

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "~/path/to/liberary/root",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

This will now create a link. Two tricks for getting a more problem free
start of development is to add your liberary with "*" for and set minimum
stability in the local composer.json for the liberary that requires your
packages:

```json
{
  "minimum-stability": "dev",
  "require": {
    "mijohansen/php-gae-util": "*"
  }
}
```








