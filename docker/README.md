# Docker and docker-compose usage

The EAs.LiT wordpress plugin my be set up as a new wordpress deployment or for testing purposes. To ease this process, use the steps given in the following sections.

## Testing

To set up a new wordpress instance for testing purposes with the plugin installed, do the following

1. `docker-compose up -d db` to start the db container
2. wait for the db to initialize (typically about 10 seconds)
3. `docker-compose build` to create a wordpress image with EAs.LiT plugin installed (not activated yet)
4. `docker-compose up -d wordpress` to start the wordpress at port 8080
5. navigate to "localhost:8080" and set up Wordpress
6. login as admin and activate the EAs.LiT plugin + create a new user with role author
7. logout & login as this new user to use the plugin

EAs.LiT will print all warnings and errors by default to the page. See [Production](#production) for information an how to change this behaviour.

## Production

To deploy EAs.LiT as a new wordpress instance, do:

1. include all commented lines in the docker-compose.yml. pay attention to genera comments
2. `docker-compose up -d db` to start the db container
3. wait for the db to initialize (typically about 10 seconds)
4. `docker-compose build` to create a wordpress image with EAs.LiT plugin installed (not activated yet)
5. `docker-compose up -d` to start all remaining containers
6. wait some seconds to get letsencypt certificates (HTTPS)
7. navigate to the URL you added to VIRTUAL_HOST and set up Wordpress
8. follow steps 6. and 7. of the [Testing](#testing) section

**Errors and Warnings** are printed by default to the page. To disable this, add `error_reporting(0);` to easlit_plugin.php, e.g. after the inital comment, and continue with step 3.

## TODO

* use docker-compose file stapling ([-f option](https://docs.docker.com/compose/reference/overview/#use--f-to-specify-name-and-path-of-one-or-more-compose-files)) instead of comments
* verify setup for errors and warnings
