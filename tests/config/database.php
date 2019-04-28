<?php

return [

    'connections' => [

        'cassandra' => [
            'name' => 'cassandra',
            'driver' => 'cassandra',
            'host' => '127.0.0.1',
            'keyspace' => 'unittest',
            'consistency' => Cassandra::CONSISTENCY_LOCAL_ONE,
            'timeout' => null,
            'connect_timeout' => 5.0,
            'request_timeout' => 12.0,
        ],
    ],

];
