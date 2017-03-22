#!/bin/sh
set -e

# Install libuv from source since there is no libuv-dev package in Precise
curl -sSL https://github.com/libuv/libuv/archive/v1.8.0.tar.gz | sudo tar zxfv - -C /usr/local/src
cd /usr/local/src/libuv-1.8.0
sudo sh autogen.sh
sudo ./configure
sudo make
sudo make install
sudo rm -rf /usr/local/src/libuv-1.8.0 && cd ~/
sudo ldconfig

sudo service cassandra stop
sudo rm -rf /var/lib/cassandra/* /etc/init.d/cassandra /etc/security/limits.d/cassandra.conf
echo "deb http://www.apache.org/dist/cassandra/debian 30x main" | sudo tee -a /etc/apt/sources.list.d/cassandra.sources.list
echo "deb-src http://www.apache.org/dist/cassandra/debian 30x main" | sudo tee -a /etc/apt/sources.list.d/cassandra.sources.list
curl https://www.apache.org/dist/cassandra/KEYS | sudo apt-key add -
sudo apt-get update && sudo apt-get install -y cassandra

cd /tmp && git clone https://github.com/datastax/cpp-driver.git && cd cpp-driver && git checkout 76656844bd13be1dbccbdb69dad93c2e9514e21f `#Version 2.6.0` && mkdir build && cd build && cmake -DCMAKE_INSTALL_PREFIX=/usr .. && make && sudo make install

pecl install cassandra-1.2.2

sudo easy_install pip
sudo pip install cqlsh

/usr/bin/cqlsh -f $TRAVIS_BUILD_DIR/setup_cassandra_unittest_keyspace.cql
