Coroutine MQTT Client Integration With [Hyperf](https://github.com/hyperf/hyperf) Framework

This library uses [simps/mqtt](https://github.com/simps/mqtt), please see [https://mqtt.simps.io/#/en/]() for compatibility

[![Latest Stable Version](http://poser.pugx.org/nashgao/mqtt/v)](https://packagist.org/packages/nashgao/mqtt) 
[![Total Downloads](http://poser.pugx.org/nashgao/mqtt/downloads)](https://packagist.org/packages/nashgao/mqtt) 
[![Latest Unstable Version](http://poser.pugx.org/nashgao/mqtt/v/unstable)](https://packagist.org/packages/nashgao/mqtt) 
[![License](http://poser.pugx.org/nashgao/mqtt/license)](https://packagist.org/packages/nashgao/mqtt) [![PHP Version Require](http://poser.pugx.org/nashgao/mqtt/require/php)](https://packagist.org/packages/nashgao/mqtt)

# This library supports MQTT 5 only

# Installation
```bash
composer require nashgao/mqtt
```

publish config
```bash
php bin/hyperf.php vendor:publish nashgao/mqtt
```

# Design Purpose

[Shared Subscription](https://www.emqx.com/en/blog/introduction-to-mqtt5-protocol-shared-subscription) is a new feature introduced by MQTT 5. It allows multiple subscribers to subscribe a same topic while only one of them receives the message at a time


```
                                                   [subscriber1] got msg1
             msg1, msg2, msg3                    /
[publisher]  ---------------->  "$share/g/topic"  -- [subscriber2] got msg2
                                                 \
                                                   [subscriber3] got msg3
   
```

As the [Chart](https://www.emqx.io/docs/en/v5.0/advanced/shared-subscriptions.html#shared-subscriptions-in-group) demonstrated, subscriber 1,2,3 belongs to the same group ```g``` under ```topic```, where ```$share``` is the constant prefix of the topic.

While msg 1,2,3 is published gradually, only one of the subscriber within the group will receive the message instead of all of them (```queue topic``` is special case for shared subscription, with group ```g``` became a constant string as ```$queue```). 

In order to make the subscription easier, this library was designed and integrated with [Hyperf](https://github.com/hyperf/hyperf) framework. The library uses [simps/mqtt](https://github.com/simps/mqtt)  which is the first php mqtt library that support MQTT 5 for basic mqtt broker interaction.

# Usage

- Subscribe
  - Auto subscribe:
    - For each topic defined under your ```config/autoload/mqtt.php```, if the ```auto_subscribe``` is enabled as true, the mentioned topic will be subscribed once the hyperf server is started.
    - If the ```queue topic``` is enabled as true, then it would short circuit the shared topic options
    - option ```enable_multi_sub``` is enabled as true, then ```multi_sub``` numbers of client will be created to subscribe corresponding topic
    - option ```group_name``` represents to the ```g``` option mentioned above it the design purpose section
  - Manual subscribe (in these cases, the shared subscription needs to be handled manually)
    - dispatch ```Nashgao\MQTT\Event\SubscribeEvent``` event
      ```php
      use Hyperf\Utils\ApplicationContext;
      use Nashgao\MQTT\Config\TopicConfig;
      use Nashgao\MQTT\Event\SubscribeEvent;
      use Psr\EventDispatcher\EventDispatcherInterface;  
    
      $event = new SubscribeEvent(topicConfigs: [
          new TopicConfig([
             'topic' => 'topic/test',
             'qos' => 2
          ])
      ]);
      $dispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
      $dispatcher->dispatch($event);
      ```
    - call ```Nashgao\MQTT\Client``` directly
      ```php 
      use Nashgao\MQTT\Client;

      $client = make(Client::class);
      $client->subscribe([
          'topic/test' => [
              'qos' => 2
          ]
      ]);  
      ```
  

- Publish
  - dispatch ```Nashgao\MQTT\Event\PublishEvent```
    ```php 
    use Hyperf\Event\EventDispatcher;
    use Hyperf\Utils\ApplicationContext;
    use Nashgao\MQTT\Event\PublishEvent;
    use Psr\EventDispatcher\EventDispatcherInterface;
    
    $dispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
    $dispatcher->dispatch(new PublishEvent('topic/test', 'hi mqtt', 2)); 
    ```
  -  call ```Nashgao\MQTT\Event``` directly
    ```php
    use Nashgao\MQTT\Client;
    
    $client = make(Client::class);
    $client->publish('topic/test', 'hi_mqtt', 2);
    ```

