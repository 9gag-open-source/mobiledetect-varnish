# Mobile_Detect Varnish

A tool to generate VCL function for Varnish using rules from Mobile_Detect.

Based on [varnish-mobiletranslate](https://github.com/willemk/varnish-mobiletranslate).

Intended to be a drop-in replacement for devicedetect

## Installation

```
composer install 9gag-open-source/mobiledetect-varnish
```

## Generating Varnish VCL

```php
$generator = new \Detection\MobileDetect\Varnish\DeviceDetect();
echo $generator->generateVcl();
```
