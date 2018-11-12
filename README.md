# E-Assessment Literacy (EAs.LiT)

![EAs.LiT Logo](Logo_EAs.LiT.png)

## Install

Tested with
[![Wordpress](https://img.shields.io/badge/Wordpress-v4.9-lightgrey.svg)](https://wordpress.org/download/)
[![PHP](https://img.shields.io/badge/PHP-v7.2-lightgrey.svg)](https://secure.php.net/)

EAs.LiT may be installed to some server running Apache and PHP or by using a docker setup. For docker, have a look at the README.md inside the docker folder.The following enumeration describes steps to setup EAs.LiT without docker.

1. Set up a webserver, [install Apache and PHP](https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-ubuntu-18-04)
2. [Install Wordpress](https://codex.wordpress.org/Installing_WordPress) to the server
3. [Add EAs.LiT plugin](https://www.dummies.com/web-design-development/wordpress/templates-themes-plugins/how-to-install-wordpress-plugins-manually/) to the installed wordpress
4. Configure the file config_taxonomies.php as needed
5. [Increase Apache File Upload Size](https://www.cyberciti.biz/faq/increase-file-upload-size-limit-in-php-apache-app/) (default vaule (2MB) is usually too small if you want to upload items)
6. Set date/time format and time zone in wordpress
7. Set up a Start Page

PHP Extension "STATS" is needed for correlations inside the test analysis.

## Comments
max_input_vars = 1000000  
post_max_size=100M  
upload_max_filesize=200M  
