# Tuque 

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.5-8892BF.svg?style=flat-square)](https://php.net/)
[![Build Status](https://travis-ci.org/jonathangreen/tuque.png?branch=master)](https://travis-ci.org/jonathangreen/tuque)
[![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](http://www.gnu.org/licenses/gpl-3.0)
[![codecov](https://codecov.io/gh/jonathangreen/tuque/branch/master/graph/badge.svg)](https://codecov.io/gh/jonathangreen/tuque)

## Introduction

This is the [API](https://github.com/Islandora/islandora/wiki/Working-With-Fedora-Objects-Programmatically-Via-Tuque) that Islandora uses to communicate with Fedora Commons.

## Requirements

* PHP 5.5+

## Configuration

There is a configuration option that if set in the ini will override the control group of the RELS-EXT and RELS-INT datastreams. We default these control groups to X if the setting is not present.
Setting this to M can increase the stability and performance of Fedora.

```
[Tuque]
tuque.rels_ds_control_group = M
```

USE AT YOUR OWN RISK!

There are [issues](https://jira.duraspace.org/browse/FCREPO-849) that are inconsistent across Fedora versions and not fully explored with making the relation datastreams managed.

Tests with the Islandora UI and Fedora 3.6.2 have not shown issues.

## Documentation

Further documentation for this module is available at [our wiki](https://wiki.duraspace.org/display/ISLANDORA/APPENDIX+G+-+All+About+Tuque).

## License

[GPLv3](./LICENSE)
