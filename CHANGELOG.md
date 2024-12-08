# Change Logs

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](http://semver.org/).

## [1.1.0](https://github.com/bfg-s/dto/compare/1.0.0...1.1.0) - 2024-12-08
* `toArray` revert back properties with `DtoName` attribute to their attribute name
* Array `extended` for extended properties
* Redone encryptions and decryptions, `Dto` accepts `encryption` and gives away `decryption` options
* Helper `length` property, now you can set the convert type for length
* Added new constructor `fromAnything` to create a `Dto` from anything
* Added new constructor `fromGet` to create a `Dto` from `GET` request body
* Added new constructor `fromPost` to create a `Dto` from `POST` request body
* Added magic debug information
* Added serialize for `DtoCollection`
* Added meta for `DtoCollection`
* Added command `make:dto-docs` to generate documentation for `Dto` classes
* Tests

## [1.0.0](https://github.com/bfg-s/dto/compare/1.0.0...1.0.0) - 2024-12-05
* Initial release
