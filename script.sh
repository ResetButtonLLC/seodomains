#"ubuntu/bionic64"

#update apache
sudo apt-get -y update
sudo DEBIAN_FRONTEND=noninteractive apt-get -y upgrade
		
# install apache and php7
sudo apt-get -y install php
sudo apt-get -y install apache2 libapache2-mod-php debconf-i18n

# install mysql and give password to installer
sudo debconf-set-selections <<< "mysql-server mysql-server/root_password password promodo33"
sudo debconf-set-selections <<< "mysql-server mysql-server/root_password_again password promodo33"
sudo apt-get -y install mysql-server

# install extensions
sudo apt-get -y install php-xml zip php-ldap php-curl php-mbstring php-ldap php7.2-mysql php-zip
sudo apt-get -y install sqlite3 libsqlite3-dev php-sqlite3
sudo apt-get -y install mc

# create database
sudo mysql -h127.0.0.1 -uroot -ppromodo33 -e "create database seodomains;"

#Удаленный доступ
#Закомментировать #bind-address and #skip-external-locking in my.cnf
#Залогинится в mysql ->  mysql -u root -33 и выполнить
#GRANT ALL PRIVILEGES ON *.* TO 'root'@'%ppromodo' IDENTIFIED BY 'promodo33';
#GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY 'promodo33';
#sudo /etc/init.d/mysql restart
#
#Если наблюдает ошибка доступа root@localhost, то нужно залогинится в mysql и посмотреть через что идет авторизация ->  select user, host, plugin from mysql.user;
#Если  root | auth_socket, то нужно сменить на mysql_native_password
#https://stackoverflow.com/questions/39281594/error-1698-28000-access-denied-for-user-rootlocalhost
#mysql> UPDATE user SET plugin='mysql_native_password' WHERE User='root';
#mysql> FLUSH PRIVILEGES;
#mysql> exit;

sudo mkdir "/var/www/site/"

# setup virtual domain
# Configure Apache
sudo echo "<Directory var/www/>
AllowOverride All
Options All
</Directory>

<VirtualHost *:80>
    ServerName tzcreator.local
    ServerAdmin admin@example.com
    DocumentRoot /var/www/site/public
    ErrorLog /error.log
    CustomLog /access.log combined
</VirtualHost>" > /tmp/site.conf

sudo cp /tmp/site.conf /etc/apache2/sites-available/00.conf
sudo a2ensite 00.conf

#
sudo sed -i 's/memory_limit = 128M/memory_limit = 1024M/g' /etc/php/7.2/apache2/php.ini

# restart services
sudo a2enmod rewrite
sudo a2enmod ssl
sudo service apache2 restart

sudo chown -R $USER:$USER /var/www/*
		
# install git
sudo apt-get -y install git

# Addons
sudo apt install -y php-gd
sudo apt install -y php-intl
# Addons END

# install Composer
sudo apt-get -y install composer
sudo php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --quiet --install-dir /usr/bin --filename composer
sudo php -r "unlink('composer-setup.php');"

#curl -s https://getcomposer.org/installer | php
#sudo mv composer.phar /usr/local/bin/composer

#Uncomment in php.ini
#extension=pdo_mysql
#extension=pdo_sqlite
#
#sudo service apache2 restart


# update project by composer
#cd /var/www/site && composer install

#php -r "file_exists('.env') || copy('.env.example', '.env');"
#php artisan key:generate
#php artisan migrate --path=/database/migrations/general --seed
#Illuminate\\Foundation\\ComposerScripts::postInstall
#php artisan optimize

