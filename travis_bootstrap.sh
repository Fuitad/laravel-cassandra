#!/bin/sh
set -e

# Install PHP 7.1-dev
sudo apt-get install software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update
sudo apt-get install -y php7.1-dev

# Install libuv from source since there is no libuv-dev package in Precise
cd /tmp && wget http://downloads.datastax.com/cpp-driver/ubuntu/16.04/dependencies/libuv/v1.24.0/libuv1_1.24.0-1_amd64.deb && wget http://downloads.datastax.com/cpp-driver/ubuntu/16.04/dependencies/libuv/v1.24.0/libuv1-dev_1.24.0-1_amd64.deb
sudo dpkg -i libuv1_1.24.0-1_amd64.deb
sudo dpkg -i libuv1-dev_1.24.0-1_amd64.deb

cd /tmp && wget http://downloads.datastax.com/cpp-driver/ubuntu/16.04/cassandra/v2.11.0/cassandra-cpp-driver_2.11.0-1_amd64.deb && wget http://downloads.datastax.com/cpp-driver/ubuntu/16.04/cassandra/v2.11.0/cassandra-cpp-driver-dev_2.11.0-1_amd64.deb
sudo dpkg -i cassandra-cpp-driver_2.11.0-1_amd64.deb
sudo dpkg -i cassandra-cpp-driver-dev_2.11.0-1_amd64.deb

# Stop remove configs
sudo rm -rf /var/lib/cassandra/* /etc/init.d/cassandra /etc/security/limits.d/cassandra.conf

# Install Apache Cassandra
echo "deb http://www.apache.org/dist/cassandra/debian 311x main" | sudo tee -a /etc/apt/sources.list.d/cassandra.sources.list
curl https://www.apache.org/dist/cassandra/KEYS | sudo apt-key add -
sudo apt-get update && sudo apt-get install -y cassandra

sudo service cassandra start

pecl install cassandra

/usr/bin/cqlsh -f $TRAVIS_BUILD_DIR/setup_cassandra_unittest_keyspace.cql
