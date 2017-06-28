# E-Assessment Literacy (EAs.LiT)

![EAs.LiT Logo](Logo_EAs.LiT.png)


## Install
* Install Wordpress on Apache Server
* Add [Multiple Roles Plugin](https://de.wordpress.org/plugins/multiple-roles/) 
* Add EAs.LiT plugin
* Configure config_taxonomies.php
* Increase Apache File Upload Size (2MB is usually too small if you want to upload items)
* Set date / time format and time zone 
* Set Start Page

(braucht es doch nicht: php extensions: ds, gmp)


max_input_vars = 1000000
post_max_size=100M
upload_max_filesize=200M
