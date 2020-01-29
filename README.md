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

The default step is 60 seconds.
If the series in Graphite have a different step, use `graphite_step` hint at map, node or link level.
Use for example `SET graphite_step 500` if the time per point is 5 minutes.

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
TARGET graphite:graphite.example.com:8080:seriesByTag('name=snmp.in_octets','hostname=router1','ifName=eth0'):seriesByTag('name=snmp.out_octets.rx','hostname=router1','ifName=eth0')
```

Series which are a counter can use `perSecond`:
```
TARGET graphite:graphite.example.com:8080:perSecond(devices.router1.interfaces.eth0.in_octets):perSecond(devices.router1.interfaces.eth0.out_octets)
```
