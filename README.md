Graphite datasource for Network Weathermap
==========================================

Simple plugin for [network-weathermap] that adds the ability to source information from [Graphite].

Install to `lib/datasources`.

[network-weathermap]: https://www.network-weathermap.com
[graphite]: http://graphiteapp.org

Usage
-----

```
TARGET graphite:graphite_host:metric
TARGET graphite:graphite_host:metric_in:metric_out
```

The `graphite_host` can be specified as IP address or hostname.
Port number can also be included.
Example: `localhost:8080`

You can also specify `-` for either metric name, which tells Weathermap to ignore this metric for the purposes of the input or output value.
This is mainly useful in combination with the aggregation feature, where you can take the input data from one target, and the output data from another.

The metric name can also include functions like [perSecond] or [seriesByTag].
If You need to add special characters in the metric name such as `:` or a space, it is better to URL encode it.

[perSecond]: https://graphite.readthedocs.io/en/latest/functions.html#graphite.render.functions.perSecond
[seriesByTag]: https://graphite.readthedocs.io/en/latest/functions.html#graphite.render.functions.seriesByTag

Examples
--------

Basic example:
```
TARGET graphite:graphite.example.com:8080:devices.router1.snmp.if_otctets-eth0.rx:devices.router1.snmp.if_otctets-eth0.tx
```

Target with 1 metric can be used for example on `NODE`:
```
TARGET graphite:graphite.example.com:8080:devices.router1.cpu.usage
```

Get tagged series:
```
TARGET graphite:graphite.example.com:8080:seriesByTag('name=snmp.in_octets.rx','hostname=router1','ifName=eth0'):seriesByTag('name=snmp.out_octets.tx','hostname=router1','ifName=eth0')
```

Series which are a counter can use `perSecond` (2 different ways):
```
TARGET graphite:graphite.example.com:8080:perSecond(devices.router1.interfaces.eth0.in_octets):devices.router1.interfaces.eth0.out_octets|perSecond()
```

Targets with space or other special characters should be URL encoded:
```
TARGET graphite:graphite.example.com:8080:seriesByTag%28%27host%3Drouter1%27%2C%20%27entPhysicalName%3DCPU%201%27%2C%20%27name%3Dsnmp.cpu_usage%27%29
```
Which resolves in the following series target: `seriesByTag('host=router1', 'entPhysicalName=CPU 1', 'name=snmp.cpu_usage')`.

Advanced example with special tokens:
```
NODE DEFAULT
    LABEL {node:this:name}
    SET metric cpu
    TARGET graphite:graphite.example.com:8080:devices.{node:this:name}.{node:this:metric}.usage

NODE router1
    POSITION 200 200

NODE router2
    POSITION 500 200
    SET metric mem
```
Which resolves in the following series targets: `devices.router1.cpu.usage` and `devices.router2.mem.usage`.
