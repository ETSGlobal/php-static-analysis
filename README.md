php-static-analysis
===================

This repository contains the base configuration for static analysis tools used by ETSGlobal PHP applications.

It includes:
- PHP_CodeSniffer configuration and custom sniffs
- PHP Mess Detector configuration
- PHPStan configuration

## Installation

Using composer:
```bash
composer require etsglobal/php-static-analysis --dev
```

It will install `squizlabs/php_codesniffer` by default, but you may install other optional tools manually:
```bash
composer require phpmd/phpmd phpstan/phpstan --dev
```

## Usage

### PHP_CodeSniffer

In your project root directory, add a `phpcs.xml` file with the following contents:
```xml
<?xml version="1.0"?>
<ruleset name="ETSGlobal Coding Standard" 
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
         xsi:noNamespaceSchemaLocation="../../vendor/squizlabs/php_codesniffer/phpcs.xsd">
    <rule ref="./vendor/etsglobal/php-static-analysis/lib/phpcs/phpcs.xml" />
</ruleset>
```

Then, run it with:
```bash
vendor/bin/phpcs src
```

If needed, you can exclude some rules by adding exceptions to your `phpcs.xml`:
```xml
    ...
    <rule ref="SlevomatCodingStandard.Functions.TrailingCommaInCall">
        <!-- Ignore missing trailing commas in multiline function calls -->
        <exclude name="SlevomatCodingStandard.Functions.TrailingCommaInCall.MissingTrailingComma"/>
    </rule>
    ...
```

### PHP Mess Detector

First, make sure you have the `phpmd/phpmd` package installed:
```bash
composer require phpmd/phpmd --dev
```

Then, in your project root directory, add a `phpmd.xml` file with the following contents:
```xml
<?xml version="1.0"?>
<ruleset name="ETSGlobal ruleset"
         xmlns="http://pmd.sf.net/ruleset/1.0.0"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://pmd.sf.net/ruleset/1.0.0 http://pmd.sf.net/ruleset_xml_schema.xsd"
         xsi:noNamespaceSchemaLocation="http://pmd.sf.net/ruleset_xml_schema.xsd">
    <description>
        ETSGlobal ruleset
    </description>

    <rule ref="./vendor/etsglobal/php-static-analysis/lib/phpmd/phpmd.xml"/>
</ruleset>
```

Now run phpmd:
```bash
vendor/bin/phpmd src text phpmd.xml
```

### PHPStan

First, make sure you have the `phpstan/phpstan` package installed:
```bash
composer require phpstan/phpstan --dev
```

Then, in your project root directory, add a `phpstan.neon` file with the following contents:
```neon
includes:
    - vendor/etsglobal/php-static-analysis/lib/phpstan/phpstan.neon
```

You can now run phpstan:
```bash
vendor/bin/phpstan analyse --level=max src
```
