# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 2.0.0
### Changed
- DependencyInstaller constructor types are now nullable to resolve php 8.4 deprecation notice
- Typed properties after dropping php 7.4 support

### Removed
- Remove php 7.x support

## 1.5.0
### Changed
- Add support whether upstream projects should have versions replaced in composer.json if the version does not match.
This can be decided per package individually, and the default value is the behavior of 1.4.0 where a package will always be replaced if the versions do not match.
- Enforce this package is unit tested with phpunit 12. Since the dev dependency was on stable, phpunit 12 would be installed anyway on modernized systems.

## 1.4.0
### Added
- Option to update module with all dependencies.

## 1.3.1
### Changed
- Package is now also installable in PHP 8.

## 1.3.0
### Added
- Check on version if versions do not match require package again.

## 1.2.0
### Changed
- Vendor name of the module to Youwe.

### Added
- Declare strict type to all files.
- Copyrights.
