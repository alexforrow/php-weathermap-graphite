Graphite datasource for Network Weathermap
==========================================

Simple plugin for network-weathermap that adds the ability to source information from Graphite

Install to `lib/datasources`

For your TARGET use the format to get both in and out:
graphite:graphite.example.com/devices.network.XXXXX.ifoctets.rx:devices.network.XXXXX.ifoctets.tx

If you want a single value to represent both in and out use the format:
graphite:graphite.example.com/devices.network.XXXXX.if_octets.rx
