IMPORTANT: This library is only compatible with the [Hyperf Framework](https://hyperf.io/).

This library is a distributed scheduler that uses redis sub-pub mechanism to gurantee an unique [instance](https://github.com/nashgao/distriubted-scheduler/blob/master/src/Instance/Instance.php) in a distributed system. 

```
class Instance implements InstanceInterface
{
    public string $instanceEvent;

    public string $instanceId;

    public Actor $actor;
}
```
- The combination of Instance Event and Instance Id is regarded as an unique identifier. 

- Actor uses the [Swoole Channel](http://wiki.swoole.com/#/coroutine/channel) as a simulation. 

- The instance will be stored in the scheduler's container

In order to communicate between processes or even servers, a pair of Server Id and Worker Id is used. The Server Id is generated before the swoole server starts by using uniqid method and Worker Id is as it is in terms of the swoole worker id.


Once the instance is created, the scheduler will check if the instance exists based on the instance event and id pair. If it exists, the get the Server Id and Worker Id from the redis.

- If the instance is in the same worker process (by checking if the Server Id and Worker Id are equivlent), then access the scheduler's container directly. Access the actor attribute of the instance and push the message to the channel
- If the instance is in other worker of the same server, then use the sendMessage api of the swoole server to trigger the ipc, a message instance will be sent and corresponding worker will receive it and do the process mentioned before.
- If the instance is in another server, then use redis's pub-sub mechanism, supported by mix-php's [redis-subscribe](https://github.com/mix-php/redis-subscribe) library