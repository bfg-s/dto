# Change Logs

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](http://semver.org/).

## [1.1.2](https://github.com/bfg-s/dto/compare/1.1.1...1.1.2) - 2024-12-20
* Added `fromFile` constructor to create a `Dto` from file
* Added `toApi` method to send data for `POST` request
* Added `default` method to set default value for property
* Added deep model support

## [1.1.1](https://github.com/bfg-s/dto/compare/1.1.0...1.1.1) - 2024-12-09
* Fixed `make:dto-docs` command, now it generates documentation and for `lazy` properties and for computed properties
* Fixed reflexion `fromArray...` and `toArray...` now is not computed properties
* Method for getting http client
* Property for getting by post request in anything constructor
* Method `httpData` for prepare data for post or get request
* Added `fromHttp` constructor to create a `Dto` from `GET` or `POST` request body

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
