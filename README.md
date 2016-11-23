# shdev
Development repository for Revolver Core and Sporthyra theme

## Deployment on Debian 8
### Prerequisites

$HOSTNAME - The hostname for which the system will be installed  
$REPO_URL - The repository URL  
$REPO - The repository name  
$UPLOADS - The full folder of wp-content/uploads from archive  
$DB - Database name  
$DBUSER - WP database user  
$DABPASS - Password for WP database user  
$DBDUMP

Commands assumes safe working directory. Commands are written as root but would preferably be run by sudo.

### Prepare system
`# apt-get -y update`  
`#apt-get -y upgrade`

### Install tools

`# apt-get install -y curl git unzip snmp`  
`# curl -sL https://deb.nodesource.com/setup_4.x | bash -`  
`# apt-get install --yes nodejs build-essential`  
`# npm install bower -g`  
`# npm install gulp -g`  

## Install packages

#### Install mariadb 

`# apt-get -y install mariadb-server mariadb-client`

#### Install apache2

`# apt-get -y install apache2`

#### Install php5

`# apt-get -y install php5 libapache2-mod-php5`

##### Test

`# echo "<?php phpinfo(); ?>" > /var/www/html/test.php`

Visit $hostname/test.php

### Install php5 extensions

`# apt-get -y install php5-mysqlnd php5-curl php5-gd php5-intl php-pear php5-imagick php5-imap php5-mcrypt php5-memcache php5-pspell php5-recode php5-snmp php5-sqlite php5-tidy php5-xmlrpc php5-xsl php5-apcu`

### Install myphpadmin

`# apt-get -y install phpmyadmin`

### Clone repository
`# git clone $repo_url`  
`# mv $repo $hostname`

### Install wordpress and wordpress dependencies
`# wget https://wordpress.org/latest.tar.gz`  
`# tar xvf latest.tar.gz`  
`# cp -rv wordpress/* $hostname`  

`# wget https://downloads.wordpress.org/plugin/woocommerce.2.6.8.zip`  
`# wget https://downloads.wordpress.org/plugin/woocommerce-gateway-stripe.3.0.6.zip`  
`# wget https://downloads.wordpress.org/plugin/wp-admin-no-show.1.7.0.zip`  
`# wget https://downloads.wordpress.org/plugin/disable-site.zip`  
`# wget https://downloads.wordpress.org/plugin/easing-slider.3.0.8.zip`  
`# wget https://downloads.wordpress.org/plugin/intercom.zip`  
`# wget https://downloads.wordpress.org/plugin/no-page-comment.zip`  
`# wget https://downloads.wordpress.org/plugin/simple-custom-post-order.zip`  
`# wget https://downloads.wordpress.org/plugin/widget-css-classes.1.3.0.zip`  
`# wget https://downloads.wordpress.org/plugin/woocommerce-brand.zip`  

Be cautious of 404 errors in case files are no longer present on wordpress.org

Download paywalled extension archives to same directory:
* Woothemes/Local pickup plus  
* Woothemes/Sequential order number

Extract to plugins folder:

`# unzip '*.zip' -d /var/www/$hostname/wp-content/plugins/`

### Uploads

Copy $UPLOAD to /var/www/$HOSTNAME/wp-content/

### Set file persmissions

`# chown -R root:www-data /var/www/$hostname` 
`# chmod -R 750 /var/www/$hostname` 
`# chmod -R 770 /var/www/$hostname/wp-content/uploads` 
`# find /var/www/$hostname/wp-content/plugins/revolver_core -type d -exec chmod g+s '{}' \;`  
`# find /var/www/$hostname/wp-content/themes/sporthyra/ -type d -exec chmod g+s '{}' \;` 
  
### Configure new database

`# mysql -p`  

```
CREATE DATABASE $DB;
CREATE USER '$DBUSER'@'%' IDENTIFIED BY '$DBPASS';
GRANT ALL PRIVILEGES ON `$DB`.* TO '$DBUSER'@'%';
```

### Import databse

Assumes a transformed dump.

`# mysql $DB < $DBDUMP`  

### wp-config

Edit create and edit wp-config.php to match database credentials

### Apache configuration

Create and enable corresponing virtual host. Take care to preapre for permalinks:

```
<Directory />
  Options FollowSymLinks
  AllowOverride All
</Directory>

<Directory /var/www/>
  Options Indexes FollowSymLinks MultiViews
  AllowOverride All
  Order allow,deny
  allow from all
</Directory>
```

`# service apache2 reload`

### Post-install

Make sure permalinks are set

### Cleanup

`rm /var/www/html/index.php`

## Development

### Sass environemt

Compile css automatically by running 

`$ gulp watch``

If gulp prints an error regardin changing environment try reinitialsing project:

`$ sudo npm install --unsafe-perm node-sass`
