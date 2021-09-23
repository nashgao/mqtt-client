<?php

declare(strict_types=1);

return [
    'default' => [
        'host' => '',
        'port' => '',
        'http_port' => '1883',
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
            'max_connections' => 10,
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
                    'auto_subscribe' => false,
                    'enable_multisub' => false,
                    'multisub_num' => 2, // let multiple subscribe the same topic, usually works with queue topic and shared topic
                    'enable_share_topic' => false,
                    'share_topic' => [
                        'group_name' => [], // list of group names that
                    ],
                    'enable_queue_topic' => false, // queue topic has more priority, if queue topic is define then share topic would be useless
                    'qos' => '',
                    'filter' => null,
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
                    'filter' => false,
                    'no_local' => true,
                    'retain_as_published' => true,
                    'retain_handling' => 2,
                ],
            ],
        ],
    ],
];
