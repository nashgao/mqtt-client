<?php

declare(strict_types=1);

return [
    'default' => [
        'host' => '',
        'port' => '',
        'time_out' => '',
        'keepalive' => '',
        'protocol_name' => 'MQTT',
        'protocol_level' => 5,
        'username' => '',
        'password' => '',
        'properties' => [
        ],
        'swoole_config' => [
            'package_max_length' => 1024 * 1024,
            'connect_timeout' => 5.0,
            'keepalive' => 0, // default 0 sec which means disabled
            'ssl_enabled' => '',
            'ssl_cert_file' => '',
            'ssl_key_file' => '',
            'ssl_cafile' => '',
        ],
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 3,
            'connect_timeout' => 10.0,
            'wait_timeout' => 20.0,
            'heartbeat' => -1,
            'max_idle_time' => 60,
        ],
        'prefix' => '',
        'subscribe' => [
            'topics' => [
                [
                    'topic' => '',
                    'shared_topic' => '',
                    'qos' => '',
                    'no_local' => true,
                    'retain_as_published' => true,
                    'retain_handling' => 2,
                ],
            ],
        ],
        'publish' => [
            'topics' => [
                [
                    'topic' => '',
                    'shared_topic' => '',
                    'qos' => '',
                    'no_local' => true,
                    'retain_as_published' => true,
                    'retain_handling' => 2,
                ],
            ],
        ],
    ],
];
