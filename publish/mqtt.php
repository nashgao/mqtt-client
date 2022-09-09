<?php

declare(strict_types=1);

return [
    'default' => [
        'host' => env('MQTT_HOST', 'localhost'),
        'port' => env('MQTT_PORT', 1883),
        // http port option deprecated
        'http_port' => env('MQTT_HTTP_PORT', 8081),
        'time_out' => env('MQTT_TIMEOUT', 10),
        'keepalive' => env('MQTT_KEEPALIVE', 60),
        'protocol_name' => 'MQTT',
        'protocol_level' => env('MQTT_PROTOCOL_LEVEL', 5),
        'username' => env('MQTT_USERNAME', 'admin'),
        'password' => env('MQTT_PASSWORD', 'public'),
        'clean_session' => false,
        'will' => [], // template: ['topic' => $topic, 'message' => 'bye']
        'properties' => [
        ],
        'swoole_config' => [
            'package_max_length' => 1024 * 1024,
            'connect_timeout' => 5.0,
            'keepalive' => 0, // default 0 sec which means disabled
            'ssl_enabled' => false,
            'ssl_cert_file' => '',
            'ssl_key_file' => '',
            'ssl_cafile' => '',
        ],
        'http' => [
            'host' => env('MQTT_HTTP_HOST', 'localhost'),
            'port' => env('MQTT_HTTP_PORT', 8081),
            'app_id' => env('MQTT_HTTP_APP_ID', 'admin'),
            'app_secret' => env('MQTT_HTTP_APP_SECRET', 'public'),
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
                    'enable_queue_topic' => false, // queue topic has more priority, if queue topic is defined then share topic would be useless
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
                    'qos' => '',
                    'no_local' => true,
                    'retain_as_published' => true,
                    'retain_handling' => 2,
                ],
            ],
        ],
    ],
];
