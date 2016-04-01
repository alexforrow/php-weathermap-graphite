Graphite datasource for Network Weathermap
==========================================

Simple plugin for network-weathermap that adds the ability to source information from Graphite

Install to `lib/datasources`

For your TARGET use the format to get both in and out:
graphite:graphite.example.com/devices.network.XXXXX.ifoctets.rx:devices.network.XXXXX.ifoctets.tx

If you want a single value to represent both in and out use the format:
graphite:graphite.example.com/devices.network.XXXXX.if_octets.rx

This was forked from [https://github.com/alexforrow/php-weathermap-graphite](https://github.com/alexforrow/php-weathermap-graphite "php-weathermap-graphite") to solve 2 problems:

1. It reported same value for both in and out and I needed both
2. The rawData method used to collect the data has been depreciated since version 0.9.9 in favor of format, which this version uses.


