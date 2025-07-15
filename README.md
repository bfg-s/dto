# Dto

[![Latest Stable Version](https://poser.pugx.org/bfg/dto/version.svg)](https://packagist.org/packages/bfg/dto)
[![License](https://poser.pugx.org/bfg/dto/license.svg)](https://packagist.org/packages/bfg/dto)
[![Downloads](https://poser.pugx.org/bfg/dto/d/total.svg)](https://packagist.org/packages/bfg/dto)

The data transfer object pattern

## Installation

```bash
composer require bfg/dto
```
after install your can create a new dto by use this command

```bash
php artisan make:dto UserDto
```

## Usage

* [Introduction](#introduction)
* [First steps](#first-steps)
* [Constructors](#constructors)
    * [from](#from)
    * [fromEmpty](#fromempty)
    * [fromArray](#fromarray)
    * [fromDto](#fromdto)
    * [fromFluent](#fromfluent)
    * [fromUrl](#fromurl)
    * [fromGet](#fromget)
    * [fromPost](#frompost)
    * [fromHttp](#fromhttp)
    * [fromRequest](#fromrequest)
    * [fromJson](#fromjson)
    * [fromSerialize](#fromserialize)
    * [fromModel](#frommodel)
    * [fromStaticCache](#fromstaticcache)
    * [fromCollection](#fromcollection)
    * [fromCache](#fromcache)
    * [fromString](#fromstring)
* [Nested DTO calls](#nested-dto-calls)
* [Binding](#binding)
    * [Binding to the model](#binding-to-the-model)
    * [Binding to the Carbon](#binding-to-the-carbon)
* [Property extends](#property-extends)
* [Property casting](#property-casting)
* [Property hidden](#property-hidden)
* [Property rules](#property-rules)
* [Property encrypted](#property-encrypted)
* [Computed properties](#computed-properties)
* [Lazy properties](#lazy-properties)
* [Method property access](#method-property-access)
* [Collection hinting](#collection-hinting)
* [Property by method with](#property-by-method-with)
* [Property logsEnabled](#property-logsenabled)
* [Meta](#meta)
* [HasDtoTrait](#hasdtotrait)
* [Attributes](#attributes)
    * [DtoAuthenticatedUser](#dtoauthenticateduser)
    * [DtoCast](#dtocast)
    * [DtoClass](#dtoclass)
    * [DtoExceptProperty](#dtoexceptproperty)
    * [DtoFromCache](#dtofromcache)
    * [DtoFromConfig](#dtofromconfig)
    * [DtoFromRequest](#dtofromrequest)
    * [DtoFromRoute](#dtofromroute)
    * [DtoItem](#dtoitem)
    * [DtoMapApi](#dtomapapi)
    * [DtoMapFrom](#dtomapfrom)
    * [DtoMapTo](#dtomapto)
    * [DtoMutateFrom](#dtomutatefrom)
    * [DtoMutateTo](#dtomutateto)
    * [DtoToResource](#dtotoresource)
* [Events](#events)
    * [creating](#creating)
    * [created](#created)
    * [updating](#updating)
    * [updated](#updated)
    * [mutating](#mutating)
    * [mutated](#mutated)
    * [serialize](#serialize)
    * [unserialize](#unserialize)
    * [clone](#clone)
    * [fromModel](#fromModel)
    * [fromEmpty](#fromEmpty)
    * [fromArray](#fromArray)
    * [fromRequest](#fromRequest)
    * [fromJson](#fromJson)
    * [fromSerialize](#fromSerialize)
    * [prepareModel](#prepareModel)
    * [prepareSerialize](#prepareSerialize)
    * [prepareJson](#prepareJson)
    * [prepareRequest](#prepareRequest)
    * [prepareArray](#prepareArray)
* [Reflection](#reflection)
    * [explain](#explain)
    * [vars](#vars)
    * [getModifiedFields](#getmodifiedfields)
    * [getRelationNames](#getrelationnames)
    * [getPropertyNames](#getpropertynames)
    * [getNames](#getnames)
    * [getReflection](#getreflection)
* [Convert DTO to](#convert-dto-to)
    * [ToArray](#toarray)
    * [ToJson](#tojson)
    * [ToResponse](#toresponse)
    * [ToCollection](#tocollection)
    * [ToBase64](#tobase64)
    * [ToModel](#tomodel)
    * [ToSerialize](#toserialize)
    * [ToString](#tostring)
    * [ToFluent](#tofluent)
    * [ToImport](#toimport)
    * [ToUrl](#tourl)
* [DTO Collection](#dto-collection)
    * [insertToDatabase](#inserttodatabase)
    * [insertToModel](#inserttomodel)
* [Commands](#commands)
    * [make:dto-cast](#makedto-cast)
    * [make:enum](#makeenum)
    * [make:dto-docs](#makedto-docs)
* [Dto like model cast](#dtolikemodelcast)
* [Helpers](#helpers)
* [Customize http request](#customize-http-request)
* [Default Laravel Support](#default-laravel-support)

### Introduction

#### Variety of constructs for creating DTOs
The package provides a variety of methods for creating DTOs (Data Transfer Objects), which significantly improves flexibility. Methods such as fromArray, fromModel, fromRequest, fromJson, fromSerialize and others allow you to conveniently create DTOs from different data sources.

#### Support for nested DTOs
The package supports nested DTOs with typing, which makes it easy to work with complex data, such as addresses or comments in the user example. This simplifies data processing in cases with dependencies between objects.

#### Rich customization of properties
Support for data casting, such as datetime, bool, as well as property extension through methods and attributes, such as DtoMapFrom, DtoFromConfig, DtoFromRequest are useful and powerful tools that make DTOs even more versatile.

#### Diving into events
The ability to handle various events (creating, created, mutating and others) provides greater flexibility in managing the state of the DTO. This can be useful for implementing validation logic or transforming data before or after it is used.

#### DTO Collections Support
Including support for DTO collections with methods for saving them to the database or models is a great addition that makes it easier to work with multiple objects.

#### Additional Features
Convenient helpers for working with DTOs, such as validate, restore, cache, as well as methods for working with metadata and logs, make this package a great tool for organizing the data structure in large projects.

#### Ease of Use
The ease of creating DTOs via the php artisan make:dto command and obvious typing of properties make the package developer-friendly, ensuring both clean code and good integration with the Laravel framework.

Overall, this package looks like a powerful tool for structuring data in Laravel, suitable for use in projects with large amounts of data and complex models. Everything looks logical and flexible, which allows you to quickly adapt it to various needs.

### First steps
Before you can use the DTO, you need to create a new class that extends the `Bfg\Dto\Dto` class.
All you dto properties must be public and have a type hint.
Also you must use the constructor for the DTO. And you can use the dependency injection in the constructor.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    protected array $hidden = [
        'password'
    ];
    
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
}
```
After creating the DTO, you can use it in your code.
```php
$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456'
]);
// Or
$dto = UserDto::new(
    name: 'John Doe',
    email: 'test@gmail.com',
    password: '123456'
);

echo $dto->name; // John Doe
```

### Constructors

#### from
You can create a new DTO from anything.
```php
$dto = UserDto::from(['name' => 'John Doe', 'email' => 'test@gmail.com']);
$dto = UserDto::from([
    ['name' => 'John Doe', 'email' => 'test@gmail.com'],
    ['name' => 'John Doe', 'email' => 'test@gmail.com'],
]);
$dto = UserDto::from(\Illuminate\Foundation\Http\FormRequest::class);
$dto = UserDto::from('{"name":"John Doe","email":"test@gmail.com"}');
$dto = UserDto::from('C:8:"UserDto":23:{a:2:{s:4:"name";s:8:"John Doe";s:5:"email";s:13:"test@gmail.com";}}');
$dto = UserDto::from(User::find(1));
```

#### fromEmpty
You can create a new DTO with empty properties.
```php
$dto = UserDto::fromEmpty();
```

#### fromArray
You can create a new DTO from an array.
```php
$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
]);
```

#### fromDto
You can create a new DTO from another DTO.
```php
$firstDto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
]);

$secondDto = UserDto::fromDto($firstDto); 
// Creates a new DTO with the same properties as the first DTO
```

#### fromFluent
You can create a new DTO from a fluent object.
```php
$fluent = (new \Illuminate\Support\Fluent([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
]))->set('password', '123456');

$dto = UserDto::fromFluent($fluent);
```

#### fromUrl
You can create a new DTO from the URL.
```php
UserDto::fromUrl(string $url, array|string|null $query = null, array $headers = []);
UserDto::fromUrl('https://test.dev');
```

#### fromGet
You can create a new DTO from the get request.
```php
$dto = UserDto::fromGet(string $url, array|string|null $query = null, array $headers = []);

UserDto::fromGet('https://test.dev', ['name' => 'John Doe', 'email' => 'test@gmail.com']);
```

#### fromPost
You can create a new DTO from the post request.
```php
$dto = UserDto::fromPost(string $url, array $data = [], array $headers = []);

UserDto::fromPost('https://test.dev', ['name' => 'John Doe', 'email' => 'test@gmail.com']);
```

#### fromHttp
You can create a new DTO from the http request.
```php
$dto = UserDto::fromHttp(string $method, string $url, array|string|null $data = [], array $headers = []): DtoCollection|static|null;

UserDto::fromHttp('get', 'https://test.dev', ['name' => 'John Doe', 'email' => 'test@gmail.com']);
````

#### fromRequest
You can create a new DTO from the request.
```php
$dto = UserDto::fromRequest(\Illuminate\Foundation\Http\FormRequest::class);
```

#### fromJson
You can create a new DTO from json.
```php
$dto = UserDto::fromJson('{"name":"John Doe","email":"test@gmail.com"}');
```

#### fromSerialize
You can create a new DTO from serialize.
```php
$dto = UserDto::fromSerialize('C:8:"UserDto":23:{a:2:{s:4:"name";s:8:"John Doe";s:5:"email";s:13:"test@gmail.com";}}');
```

#### fromModel
You can create a new DTO from model.
```php
$dto = UserDto::fromModel(User::find(1));
```

#### fromStaticCache
You can create a new DTO from static cache.
```php
$dto = UserDto::fromStaticCache('user', function () {
    return UserDto::fromArray([
        'name' => 'John Doe',
        'email' => 'test@gmail.com',
    ]);
});
```

#### fromCollection
You can create a new DTO from collection.
```php
$collection = UserDto::fromCollection([
    ['name' => 'John Doe', 'email' => 'test@gmail.com'],
    ['name' => 'Sam Doe', 'email' => 'test2@gmail.com'],
]);
```

#### fromCache
You can create a new DTO from cache.
```php
// You can cache dto before
$dto->cache();

$dto = UserDto::fromCache(function () {
    // If cache not found
    return UserDto::fromArray([
        'name' => 'John Doe',
        'email' => 'test@gmail.com',
    ]);
});
```

#### fromString
You can create a new DTO from string.
```php
$dto = UserDto::fromString('John Doe|test@gmail.com', '|'); 
```

#### fromSource
You can create a new DTO from source.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
    
    public static function sourceV1(...$arguments): array {
    
        // Do something
    
        return [
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
            'password' => '123456',
        ];
    }
}
```
And after that you can create a new DTO from source.
```php
$dto = UserDto::fromSource('v1', ...$arguments);
```


### Nested DTO calls
For nested DTO calls, you can use type hinting.
```php
use Bfg\Dto\Dto;

class AddressDto extends Dto
{
    public function __construct(
        public string $city,
        public string $street,
    ) {}
}

class CommentDto extends Dto
{
    public function __construct(
        public string $message,
    ) {}
}

class UserDto extends Dto
{
    protected array $hidden = [
        'password'
    ];
        
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public AddressDto $address,
        public CommentDto|array $comments,
    ) {}
}
```
Now you can use nested DTOs.
```php
$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456',
    'address' => [
        'city' => 'New York',
        'street' => 'Wall Street',
    ],
    'comments' => [
        ['message' => 'The first comment'],
        ['message' => 'The second comment'],
    ]
]);

echo $dto->address->city; // New York
// And
foreach ($dto->comments as $comment) {
    echo $comment->message;
}
```

### Binding
You can use binding for the DTO.

#### Binding to the model
You can bind the DTO property to the model.

```php
use Bfg\Dto\Dto;
use App\Models\User;
use Bfg\Dto\Attributes\DtoMapFrom;

class UserDto extends Dto
{
    public function __construct(
        public string $name,
        public string $email,
        #[DtoMapFrom('user_id')] 
        public ?User $user,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'user_id' => 1,
    // Or 
    'user' => 1, 
]);

dump($dto->user); // User model
// In Array you will get the id of the model
dump($dto->toArray()); // ['name' => 'John Doe', 'email' => 'test@gmail.com', 'user_id' => 1]
```
If model User with id `1` exists, it will be bound to the DTO property. If not, the property will be null.

#### Binding to the Carbon
You can bind the DTO property to the Carbon.
Default format for the Carbon is `Y-m-d H:i:s`.
You can change the format using the `$dateFormat` property.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    public function __construct(
        public string $name,
        public string $email,
        public \Carbon\Carbon $created_at,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'created_at' => '2025-01-01 00:00:00',
]);

dump($dto->created_at); // Carbon object
// In Array you will get the date in the format `Y-m-d H:i:s`
dump($dto->toArray()); // ['name' => 'John Doe', 'email' => 'test@gmail.com', 'created_at' => '2025-01-01 00:00:00']
```

### Property extends
You can use property extends for extending the DTO.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    public function __construct(
        public string $email,
        public ?string $password,
    ) {}
}
```
```php
class SpecialUserDto extends UserDto
{
    protected static array $extends = [
        'name' => 'string|null',
        // Or
        'name' => ['string', 'null'],
    ];
}
```
This way you will expand the properties for DTO.

All properties have identical behavior to properties in the property promotion constructor.

### Property casting
You can use property casting like in Laravel models.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    protected static array $casts = [
        'is_admin' => 'bool',
        'created_at' => 'datetime',
    ];
        
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public bool $is_admin,
        public \Carbon\Carbon $created_at,
    ) {}
}
````
Also, this casting support class casting.
You can create a new class casting using the artisan command:
```bash
php artisan make:dto-cast UserNameCast
```
After creating the class casting, you can use it in the DTO.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    protected static array $casts = [
        'is_admin' => 'bool',
        'created_at' => 'datetime',
        'name' => UserNameCast::class,
    ];
}
```
And casting support enum casting.
You can create a new enum casting using the artisan command:
```bash
php artisan make:enum UserStatusEnum
```
After creating the enum casting, you can use it in the DTO.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    protected static array $casts = [
        'is_admin' => 'bool',
        'created_at' => 'datetime',
        'name' => UserNameCast::class,
        'status' => UserStatusEnum::class,
    ];
}
```

### Property hidden
You can use property hidden like in Laravel models.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    protected array $hidden = [
        'password'
    ];
            
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456',
]);

echo $dto->toArray(); // ['name' => 'John Doe', 'email' => 'test@gmail.com']
```

### Property rules
You can use property rules like in Laravel requests.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    protected static array $rules = [
        'name' => 'required|string',
        'email' => 'required|email',
        'password' => 'required|string|min:6',
    ];
    
    protected static array $ruleMessages = [
        'name.required' => 'The name field is required.',
        'email.required' => 'The email field is required.',
        'password.required' => 'The password field is required.',    
    ];
                
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
]); // Throws an exception
```

### Property encrypted
You can use property encrypted for getting encrypted data.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    protected array $encrypted = [
        'password'
    ];
                
    public function __construct(
        public string $name,
        public string $email,
        public string $password, // Data will be decrypted
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => \Illuminate\Support\Facades\Crypt::encrypt('123456'),
]);

echo $dto->password; // You will get decrypted password

dump($dto->toArray()); // ['name' => 'John Doe', 'email' => 'test@gmail.com', 'password' => 'encrypted data']
```

### Computed properties
You can use computed properties.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $lastName,
        public string $email,
        public ?string $password,
    ) {}
        
    public function fullName(): string
    {
        return $this->name . ' ' . $this->lastName;
    }
}
```
Now you can use computed properties.
```php
$dto = UserDto::fromArray([
    'name' => 'John',
    'lastName' => 'Doe',
    'email' => 'test@gmail.com',
    'password' => '123456'
]);

echo $dto->fullName; // John Doe
```

### Lazy properties
You can use lazy like properties any property or computed property.
If you add the prefix "lazy" before the name of a property or a computed property, you will get access to lazy execution. When you request a property, the value that this property receives is cached and all subsequent times the result for the lazy property is taken from the cache and if in DTO the property changes, then in Lazy it will remain the same since it is taken from the cache.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $lastName,
        public string $email,
        public ?string $password,
    ) {}
        
    public function fullName(): string
    {
        return $this->name . ' ' . $this->lastName;
    }
}

$dto = UserDto::fromArray([
    'name' => 'John',
    'lastName' => 'Doe',
    'email' => 'test@gmail.com',
    'password' => '123456'
]);

echo $dto->lazyEmail; // test@gmail.com
echo $dto->lazyFullName; // John Doe, and it put in the cache
$dto->set('name', 'Sam');
echo $dto->lazyFullName; // John Doe, because it is taken from the cache
```

### Method property access
You can use the method property access.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456',
]);

echo $dto->name(); // John Doe
// The same as
echo $dto->get('name'); // John Doe
```

### Method default for field
You can use the `default` method for setting the default value for the field.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
    
    public static function defaultName()
    {
        return 'Jon';
    }
}
```
After that, you can escape the name field when creating a DTO.
```php
$dto = UserDto::fromArray([
    'email' => 'test@gmail.com',
    'password' => '123456',
]);

echo $dto->name; // Jon
```

### Collection hinting
You can use collection hinting.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public AddressDto|array $address,
        // Or
        public AddressDto|\Illuminate\Support\Collection $address,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456',
    'address' => [
        ['city' => 'New York', 'street' => 'Wall Street'],
        ['city' => 'Los Angeles', 'street' => 'Hollywood Street'],    
    ]
]);

foreach ($dto->address as $address) {

    echo $address->city;
}
```

#### Property by method with
You can use the `with...` method for adding data to the DTO array.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $lastName,
        public string $email,
        public ?string $password,
    ) {}
        
    public function withFullName(): string
    {
        return $this->name . ' ' . $this->lastName;
    }
}

$dto = UserDto::fromArray([
    'name' => 'John',
    'lastName' => 'Doe',
    'email' => 'test@gmail.com',
    'password' => '123456'
]);

dump($dto->toArray()); // ['name' => 'John', 'lastName' => 'Doe', 'email' => 'test@gmail.com', 'password' => '123456', 'fullName' => 'John Doe']
```


#### Property logsEnabled
You can use property logsEnabled for logging data in the DTO memory.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    protected static bool $logsEnabled = true;
    
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456',
]);

$dto->set('name', 'Sam Doe');

dump($dto->logs()); // You will get logs DTO
```
You can write your own logs to the DTO memory using the `log` method.
```php
$dto->log(string $message, array $context = []);
```

### Meta
You can use meta.
```php
$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456',
]);

$dto->setMeta(['key' => 'value']);
$dto->setMeta(['key2' => 'value2']);
echo $dto->getMeta('key'); // value
echo $dto->getMeta('key2'); // value2
// You can get all meta
dump($dto->getMeta()); // ['key' => 'value', 'key2' => 'value2']
// You can remove meta
$dto->unsetMeta('key');
```

### HasDtoTrait
You can use the `HasDtoTrait` trait in your model or request to use the DTO.
```php
use Bfg\Dto\HasDtoTrait;
use Bfg\Dto\Attributes\DtoClass;

#[DtoClass(UserDto::class)]
class User extends Model
{
    /** @use HasDtoTrait<UserDto> */
    use HasDtoTrait;
}
```
After that, you can use the DTO in your model.
```php
$user = User::find(1);
$dto = $user->getDto(); // UserDto object
```

### Attributes
You can use attributes.

#### DtoItem
You can set `DTO` for `Collection` property

```php
class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $email,
        #[\Bfg\Dto\Attributes\DtoItem(UserContactDto::class)]
        public \Bfg\Dto\Collections\DtoCollection $contacts,
    ) {}
}
```

#### DtoMapApi
You can use the `DtoMapApi` attribute for converting the DTO keys to the API camel case.
```php
use Bfg\Dto\Dto;
use Bfg\Dto\Attributes\DtoMapApi;

class UserDto extends Dto
{            
    public function __construct(
        #[DtoMapApi] 
        public string $userName,
        #[DtoMapApi] 
        public string $userEmail,
        public ?string $password,
    ) {}
}

$dto = UserDto::fromArray([
    'user_name' => 'John Doe',
    'user_email' => 'test@gmail.com',
    'password' => '123456',
]);

echo $dto->userName; // John Doe

$dto->toArray(); // ['user_name' => 'John Doe', 'user_email' => 'test@gmail.com', 'password' => '123456']
```

#### DtoAuthenticatedUser
You can use the `DtoAuthenticatedUser` attribute to set the authenticated user for the DTO.
```php
use Bfg\Dto\Dto;

class UserSettingsDto extends Dto
{            
    public function __construct(
        #[\Bfg\Dto\Attributes\DtoAuthenticatedUser]
        public \App\Models\User $user,
        #[\Bfg\Dto\Attributes\DtoMapApi]
        public bool $receiveNotifications,
        #[\Bfg\Dto\Attributes\DtoMapApi]
        public bool $darkMode,
    ) {}
}

$dto = UserSettingsDto::fromAssoc([
    'receive_notifications' => true,
    'dark_mode' => false,
]);

$dto->user; // Will return the authenticated user model
```

#### DtoCast
You can use the `DtoCast` attribute to set the DTO for the property.
```php
use Bfg\Dto\Dto;
use Bfg\Dto\Attributes\DtoCast;

class UserPhoneDto extends Dto
{            
    public function __construct(
        #[DtoCast('int')] public int $number,
    ) {}
}

UserPhoneDto::fromArray([
    'number' => '1234567890',
]);
```
> Attention! If you do not specify the property casting, then, when you try to assign a different type to the property, there will be a PHP error that will say that you are trying to assign a different type than expected.

#### DtoClass
You can use the `DtoClass` attribute to set the DTO for the class.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
}

#[\Bfg\Dto\Attributes\DtoClass(UserDto::class)]
class User extends Model
{
    /** @use \Bfg\Dto\Traits\Support\HasDtoTrait<UserDto::class> */
    use \Bfg\Dto\Traits\Support\HasDtoTrait;
}

// Now you can use the DTO in the model
$user = User::find(1);
$dto = $user->getDto(); // UserDto object
```

#### DtoExceptProperty
You can use the `DtoExceptProperty` attribute to exclude the property from the DTO.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $email,
        #[\Bfg\Dto\Attributes\DtoExceptProperty] 
        public ?string $password, // This property will be excluded from the DTO
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456',
]);

echo $dto->toArray(); // ['name' => 'John Doe', 'email' => 'test@gmail.com']
```

#### DtoItem
You can use the `DtoItem` attribute to set the DTO for the collection property.
```php
use Bfg\Dto\Dto;
use Bfg\Dto\Attributes\DtoItem;
use Bfg\Dto\Collections\DtoCollection;

class UserContactDto extends Dto
{            
    public function __construct(
        #[DtoItem(UserPhoneDto::class)] public DtoCollection $phones,
        #[DtoItem(UserEmailDto::class)] public DtoCollection $emails,
    ) {}
}
```

#### DtoMapFrom
You can use the `DtoMapFrom` attribute to add the name of the DTO.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        #[DtoMapFrom('user')] 
        public string $name,
        #[DtoMapFrom('contacts.email'), DtoMapTo('contacts.email')] 
        public string $email,
        public ?string $password,
    ) {}
}

$dto = UserDto::fromArray([
    'user' => 'John Doe',
    'contacts' => [
        'email' => 'test@gmail.com',
    ],
    'password' => '123456'
]);
// Or
$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456'
]);

echo $dto->name; // John Doe
```
Here we have added a field name to the "name" field and we can now use two fields to insert data.

For property extends:
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    #[DtoMapFrom('user', 'name')]
    protected static array $extends = [
        'name' => 'string',
    ];

    public function __construct(
        public string $email,
        public ?string $password,
    ) {}
}
```
Since we cannot write an attribute to a separate element of the array, we need to specify the name of the field to which we need to assign an additional name as the second parameter.

#### DtoMapTo
You can use the `DtoMapTo` attribute to add the name of the DTO.
```php
use Bfg\Dto\Dto;
class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        #[\Bfg\Dto\Attributes\DtoMapTo('user_emails.email1'), \Bfg\Dto\Attributes\DtoMapFrom('email')]
        public string $userEmail,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456'
]);

echo $dto->userEmail;

$dto->toArray(); // ['name' => 'John Doe', 'email' => 'test@gmail.com', 'password' => '123456', 'user_emails' => ['email1' => 'test@gmail.com']]
```

#### DtoMutateFrom
You can use the `DtoMutateFrom` attribute to mutate the property value from any to the DTO.
```php
use Bfg\Dto\Dto;

class SettingsDto extends Dto
{            
    public function __construct(
        #[\Bfg\Dto\Attributes\DtoMutateFrom('mutateStringBoolean')]
        public bool $receiveNotifications,
    ) {}
    
    public static function mutateStringBoolean(mixed $value): bool
    {
        return is_string($value)
            ? trim(strtolower($value)) === 'true'
            : (bool) $value;
    }
}

$dto = SettingsDto::fromArray([
    'receive_notifications' => 'true', // Will be mutated to true
]);

echo $dto->receiveNotifications; // true
```

#### DtoMutateTo
You can use the `DtoMutateTo` attribute to mutate the property value to any type from the DTO.
```php
use Bfg\Dto\Dto;

class SettingsDto extends Dto
{            
    public function __construct(
        #[\Bfg\Dto\Attributes\DtoMutateTo('mutateBooleanString'), \Bfg\Dto\Attributes\DtoMapApi]
        public bool $receiveNotifications,
    ) {}
    
    public static function mutateBooleanString(mixed $value): string
    {
        return $value ? 'true' : 'false';
    }
}

$dto = SettingsDto::fromArray([
    'receive_notifications' => true,
]);

echo $dto->receiveNotifications; // true

$dto->toArray() // ['receive_notifications' => 'true']
```

#### DtoFromConfig
You can use the `DtoFromConfig` attribute to add the property value from the config.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        #[DtoFromConfig('app.name')]
        public string $appName,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456'
]);

echo $dto->appName; // Laravel
```

#### DtoFromRequest
You can use the `DtoFromRequest` attribute to add the property value from the request.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        #[DtoFromRequest]
        public string $id,
    ) {}
}

// https://test.dev/?id=100

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456'
]);

echo $dto->id; // 100
```

#### DtoFromRoute
You can use the `DtoFromRoute` attribute to add the property value from the route.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        #[DtoFromRoute]
        public string $id,
    ) {}
}

// Route::get('/{id}', function ($id) {});

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456'
]);

echo $dto->id; // 100
```

#### DtoFromCache
You can use the `DtoFromCache` attribute to add the property value from the cache.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        
        #[DtoFromCache('user')]
        public string $userFromCache,
        // Or
        #[DtoFromCache]
        public string $user,
    ) {}
}

Cache::put('user', 'John Doe', 60);

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456'
]);

echo $dto->user; // John Doe
```

#### DtoToResource
You can use the `DtoToResource` attribute to convert the property for adding to the array.
Create a new resource:
```bash
php artisan make:resource UserAddressResource
```
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{            
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        #[DtoToResource(UserAddressResource::class)]
        public AddressDto $address,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456',
    'address' => [
        'city' => 'New York',
        'street' => 'Wall Street',
    ]
]);

echo $dto->toArray(); 
// ['name' => 'John Doe', 'email' => '...', 'password' => '...', 'address' => ['city' => 'New York', 'street' => 'Wall Street']]
```
Address will be converted to the array using the `UserAddressResource` resource.

### Events
You can use events.

#### creating
You can use the `creating` event.
```php
UserDto::on('creating', function (array $arguments) {
    $arguments['name'] = 'John Doe';
    return $arguments;
});
```

#### created
You can use the `created` event.
```php
UserDto::on('created', function (UserDto $dto, array $arguments) {
    $dto->name = 'John Doe';
});
```

#### updating
You can use the `updating` event.
```php
UserDto::on(['updating', 'name'], function (mixed $value, UserDto $dto) {
    return strtoupper($value);
});
```

#### updated
You can use the `updated` event.
```php
UserDto::on(['updated', 'name'], function (UserDto $dto) {
    $dto->name = strtoupper($dto->name);
});
```

#### mutating
You can use the `mutating` event.
```php
UserDto::on(['mutating', 'name'], function (mixed $value, UserDto $dto, array $arguments) {
    return strtoupper($value);
});
```

#### mutated
You can use the `mutated` event.
```php
UserDto::on(['mutated', 'name'], function (mixed $value, UserDto $dto, array $arguments) {
    return strtoupper($value);
});
```

#### serialize
You can use the `serialize` event.
```php
UserDto::on('serialize', function (array $arguments, UserDto $dto) {
    $arguments['name'] = strtoupper($arguments['name']);
    return $arguments;
});
```

#### unserialize
You can use the `unserialize` event.
```php
UserDto::on('unserialize', function (array $arguments, UserDto $dto) {
    $arguments['name'] = strtolower($arguments['name']);
    return $arguments;
});
```

#### clone
You can use the `clone` event.
```php
UserDto::on('clone', function (array $arguments, UserDto $dto) {
    $arguments['name'] = strtolower($arguments['name']);
    return $arguments;
});
```

#### fromModel
You can use the `fromModel` event.
```php
UserDto::on('fromModel', function (UserDto $dto, array $arguments) {
    // You can change the DTO data or something else
});
```

#### fromEmpty
You can use the `fromEmpty` event.
```php
UserDto::on('fromEmpty', function (UserDto $dto, array $arguments) {
    // You can change the DTO data or something else
});
```

#### fromArray
You can use the `fromArray` event.
```php
UserDto::on('fromArray', function (UserDto $dto, array $arguments) {
    // You can change the DTO data or something else
});
```

#### fromRequest
You can use the `fromRequest` event.
```php
UserDto::on('fromRequest', function (UserDto $dto, array $arguments) {
    // You can change the DTO data or something else
});
```

#### fromJson
You can use the `fromJson` event.
```php
UserDto::on('fromJson', function (UserDto $dto, array $arguments) {
    // You can change the DTO data or something else
});
```

#### fromSerialize
You can use the `fromJson` event.
```php
UserDto::on('fromJson', function (UserDto $dto) {
    // You can change the DTO data or something else
});
```

#### prepareModel
You can use the `prepareModel` event.
```php
UserDto::on('prepareModel', function (array $arguments) {
    $arguments['name'] = 'John Doe';
    return $arguments;
});
```

#### prepareSerialize
You can use the `prepareSerialize` event.
```php
UserDto::on('prepareSerialize', function (string $serializedString) {
    // You can change the serialized string or something else
    return $serializedString;
});
```

#### prepareJson
You can use the `prepareJson` event.
```php
UserDto::on('prepareJson', function (array $arguments) {
    // You can change the json array or something else
    return $arguments;
});
```

#### prepareRequest
You can use the `prepareRequest` event.
```php
UserDto::on('prepareRequest', function (array $arguments) {
    // You can change the request array or something else
    return $arguments;
});
```

#### prepareArray
You can use the `prepareArray` event.
```php
UserDto::on('prepareArray', function (array $arguments) {
    // You can change the array or something else
    return $arguments;
});
```

#### prepareEmpty
You can use the `prepareArray` event.
```php
UserDto::on('prepareEmpty', function () {
    // You can create a new array or something else
    return [];
});
```

#### destruct
You can use the `destruct` event.
```php
UserDto::on('destruct', function (UserDto $dto) {
    // You can do something with the DTO
});
```

### Reflection
You can use reflection.

#### explain
You can use the `explain` method for getting the DTO information.
```php
$dto->explain();
```

#### vars
You can use the `vars` method for getting the DTO properties.
```php
$dto->vars();
```

#### getModifiedFields
You can use the `getModifiedFields` method for getting the modified fields.
```php
$dto->getModifiedFields();
```

#### getRelationNames
You can use the `getRelationNames` method for getting the relation names.
```php
$dto->getRelationNames();
```

#### getPropertyNames
You can use the `getPropertyNames` method for getting the property names.
```php
$dto->getPropertyNames();
```

#### getNames
You can use the `getNames` method for getting the names.
```php
$dto->getNames();
```

#### getReflection
You can use the `getReflection` method for getting the reflection.
```php
$dto->getReflection();
```

### Convert DTO to

#### ToArray
You can convert DTO to array.
```php
$dto->toArray();
```

#### ToJson
You can convert DTO to json.
```php
$dto->toJson($options = 0);
```

#### ToResponse
You can convert DTO to response.
```php
$dto->toResponse(int $status = 200, array $headers = [], int $options = 0);
```

#### ToCollection
You can convert DTO to collection.
```php
$dto->toCollection();
```

#### ToBase64
You can convert DTO to base64.
```php
$dto->toBase64();
```

#### ToModel
You can convert DTO to model.
```php
$dto->toModel(User::class);
```

#### ToSerialize
You can convert DTO to serialize.
```php
$dto->toSerialize();
```

#### ToApi
You can convert DTO to api.
```php
$dto->toApi(string $url, string $method = 'post', array $headers = []);
```

#### ToString
You can convert DTO to string.
```php
$dto->toString();
//Or
echo (string) $dto;
```

#### ToFluent
You can convert DTO to fluent.
```php
$dto->toFluent();
```

#### ToImport
You can convert DTO to import.
> Convert an object to the format string from which the object was created.
> Used for storing in a database.
> `json` by default.
```php
$dto->toImport(string $fileName, string $sheetName = 'Sheet1', array $options = []);
```

#### ToUrl
You can convert DTO to url.
```php
$dto->toUrl(string|null $baseUrl = null, array $exclude = [], array $only = [], array $query = []): string;

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
]);
// Generate only url parameters
echo $dto->toUrl();
// ?name=John%20Doe&email=test%40gmail.com

// Or generate parameters with base url
echo $dto->toUrl('https://example.com');
// https://example.com?name=John%20Doe&email=test%40gmail.com  

// Or generate parameters with base url but without email property
echo $dto->toUrl('https://example.com', exclude: ['email']); 
// https://example.com?name=John%20Doe

// Or generate parameters with base url use only email property
echo $dto->toUrl('https://example.com', only: ['email']);
// https://example.com?email=test%40gmail.com

// Or generate parameters with base url and additional query parameters
echo $dto->toUrl('https://example.com', query: ['page' => 1]);
// https://example.com?name=John%20Doe&email=test%40gmail.com&page=1

// And you can use parameters like tags in the base url
echo $dto->toUrl('https://example.com/find/{name}/name');
// https://example.com/find/John%20Doe/name?email=test%40gmail.com

// And you can convert DTO to the route.
// For example, you can have a route named 'user.profile'.
// You can use the `toUrl` method to generate a URL for that route.
echo $dto->toUrl('user.profile');
// This will generate a URL like: https://example.com/user/profile?name=John%20Doe&email=test%40gmail.com
```

### DTO Collection
Dto collection have couple additional methods.
#### insertToDatabase
For save collection to database you can use `saveToDatabase` method.
```php
$collection = UserDto::fromCollection([
    ['name' => 'John Doe', 'email' => 'test@gmail.com', 'password' => '123456'],
    ['name' => 'Sam Doe', 'email' => 'sam@gmail.com', 'password' => '123456'],
]);

$collection->insertToDatabase('users');
```
#### insertToModel
For save collection to model you can use `saveToModel` method.
```php
$collection = UserDto::fromCollection([
    ['name' => 'John Doe', 'email' => 'test@gmail.com', 'password' => '123456'],
    ['name' => 'Sam Doe', 'email' => 'sam@gmail.com', 'password' => '123456'],
]);

$collection->insertToModel(\App\Models\User::class);
```

### Commands
You can use commands.
#### Make dto
You can create a new DTO using the artisan command.
```bash
php artisan make:dto UserDto
```

#### Make dto cast
You can create a new DTO cast using the artisan command.
```bash
php artisan make:dto-cast UserNameCast
```

#### Make dto docs
You can build the DTO documentation for extended fields using the artisan command.
```bash
php artisan make:dto-docs
```
> Add this command to the `composer.json` file for auto-generating DTO documentation after the `composer update` and `composer dump-autoload` command.
```json
"scripts": {
    "post-autoload-dump": [
        "@php artisan make:dto-docs"
    ],
}
```

### Dto like model cast
You can use the Dto class as a cast class for a model with a JSON or link field.
Keep in mind that if the array is associative, it will return the Dto class, but if not, it will return a collection.
```php
use Bfg\Dto\Dto;

/**
 * @extends Dto<User>
 */
class UserSettingsDto extends Dto
{
    public function __construct(
        public string $theme,
        public bool $notificationsEnabled,
        public string $language,
    ) {}
    
    public function toDatabase(): string 
    {    
        return "$this->theme|$this->notificationsEnabled|$this->language";
    }
    
    /**
     * If you have a static method that ends with the same suffix as the envelope method "toDatabase". 
     * That is, you must have the following inverse static method "fromDatabase".
     */
    public static function fromDatabase(string $data): array
    {
        $data = explode('|', $data);
        
        return [
            'theme' => $data[0],
            'notificationsEnabled' => (bool) $data[1],
            'language' => $data[2],  
        ];         
    }  
}

use Illuminate\Database\Eloquent\Model;
use Bfg\Dto\Collections\DtoCollection;

class User extends Model
{
    protected $casts = [
        'settings' => UserSettingsDto::class, // Use the Dto class as a cast class
        // Or you can specify the Dto how to save the data in the database
        'settings' => UserSettingsDto::store()->toDatabase(), // Custom save.
        // Or you can use the DtoCollection class for a collection
        'settings' => DtoCollection::using(UserSettingsDto::class),
        // Or specify dto casting
        'settings' => DtoCollection::using(UserSettingsDto::store()->toDatabase()),
    ];
    protected $fillable = ['name', 'email', 'settings'];
    protected $hidden = ['password'];
}
```

### Helpers
You can use helpers.

#### new
You can use the `new` helper for creating a new DTO.
```php
UserDto::new(
    name: 'John Doe',
    email: 'test@gmail.com',
    password: '123456',
);
```

#### version
You can use the `version` helper for getting the DTO version.
```php
UserDto::version();
```

#### pipeline
You can use the `pipeline` helper for creating a new DTO pipeline.
```php
$dto->pipeline([
    SomeClassForPipeline::class,
]);
```

#### camelKeys
You can use the `camelKeys` helper for converting the DTO keys to camel case when converting to an array.
```php
$dto->camelKeys()->toArray();
```

#### snakeKeys
You can use the `snakeKeys` helper for converting the DTO keys to snake case when converting to an array.
```php
$dto->snakeKeys()->toArray();
```

#### cache
You can use the `cache` helper for caching the DTO.
```php
$dto->cache(\DateTimeInterface|\DateInterval|int|null $ttl = null);
```

#### cacheKey
You can use the `cacheKey` helper for getting the cache key.
```php
$dto->cacheKey('name');
```

#### getCachedKey
You can use the `getCachedKey` helper for getting the cached key.
```php
$dto->getCachedKey('name');
```

#### cacheKeyClear
You can use the `cacheKeyClear` helper for clearing the cache key.
```php
$dto->cacheKeyClear('name');
```

#### validate
You can use the `validate` helper for validating the DTO.
```php
$dto->validate([
    'name' => 'required|string',
    'email' => 'required|email',
    'password' => 'required|string|min:6',
]); // bool
```

#### restore
You can use the `restore` helper for restoring the DTO from the original data.
```php
$dto->restore();
```

#### originals
You can use the `originals` helper for getting the original data.
```php
$dto->originals();
```

#### equals
You can use the `equals` helper for comparing the DTOs.
```php
$dto->equals(UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456',
])); // bool
```

#### fill
You can use the `fill` helper for filling the DTO.
```php
$dto->fill([
    'name' => 'John Doe',
    'email' => 'test@gmail.com',
    'password' => '123456',
]);
```

#### length
You can use the `length` helper for getting the length of the DTO in bytes.
```php
$dto->length();
// Or
$dto->length(\Bfg\Dto\Dto::GET_LENGTH_SERIALIZE); // You get the length of the DTO in bytes in serialized form

// And you can get the length of the DTO in bytes in json form
$dto->length(\Bfg\Dto\Dto::GET_LENGTH_JSON);
```

#### count
You can use the `count` helper for getting the count of the DTO.
```php
$dto->count();
```

#### has
You can use the `has` helper for checking the property in the DTO.
```php
$dto->has('name');
```
You also can use `isset` for checking the property in the DTO.
```php
isset($dto['name']);
```

#### dataHash
You can use the `dataHash` helper for getting the data hash of the DTO.
```php
$dto->dataHash();
```

#### hash
You can use the `hash` helper for getting the hash of the DTO.
```php
$dto->hash();
```

#### clone
You can use the `clone` helper for cloning the DTO.
```php
$dto->clone();
```

#### str
You can use the `str` helper for getting the string representation of the DTO.
```php
$dto->str('name')->camel()->snake();
```

#### collect
You can use the `collect` helper for wrapping the DTO properties in the collection.
```php
$dto->collect('addresses')->map(function (UserAddressDto $address) {
    return $address->city;
});
```

#### boolable
You can use the `boolable` helper for inverting the boolean property in the DTO.
```php
$dto->boolable('confirmed');
```

#### toggleBool
You can use the `toggleBool` helper for toggling the boolean property in the DTO.
```php
$dto->toggleBool('is_admin');
```

#### increment
You can use the `increment` helper for incrementing the property in the DTO.
```php
$dto->increment('count');
```

#### decrement
You can use the `decrement` helper for decrementing the property in the DTO.
```php
$dto->decrement('count');
```

#### set
You can use the `set` helper for setting the property in the DTO.
```php
$dto->set('name', 'John Doe');
```

#### get
You can use the `get` helper for getting the property in the DTO.
```php
$dto->get('name');
```

#### map
You can use the `map` helper for mapping the DTO properties.
```php
$dto->map(function (mixed $value, string $key) {
    return strtoupper($value);
});
```

#### isEmpty
You can use the `isEmpty` helper for checking the DTO is property empty.
```php
$dto->isEmpty('name');
```

#### isNotEmpty
You can use the `isNotEmpty` helper for checking the DTO is property not empty.
```php
$dto->isNotEmpty('name');
```

#### isNull
You can use the `isNull` helper for checking the DTO is property null.
```php
$dto->isNull('name');
```

#### isNotNull
You can use the `isNotNull` helper for checking the DTO is property not null.
```php
$dto->isNotNull('name');
```

#### isCanNull
You can use the `isCanNull` helper for checking the DTO is property can be null.
```php
$dto->isCanNull('name');
```

#### isTrue
You can use the `isTrue` helper for checking the DTO is property true.
```php
$dto->isTrue('is_admin');
```

#### isFalse
You can use the `isFalse` helper for checking the DTO is property false.
```php
$dto->isFalse('is_admin');
```

#### isBool
You can use the `isBool` helper for checking the DTO is property bool.
```php
$dto->isBool('is_admin');
```

#### isEquals
You can use the `isEquals` helper for checking the DTO is property equals.
```php
$dto->isEquals('name', 'John Doe');
```

#### isNotEquals
You can use the `isNotEquals` helper for checking the DTO is property not equals.
```php
$dto->isNotEquals('name', 'John Doe');
```

#### isInstanceOf
You can use the `isInstanceOf` helper for checking the DTO is property instance of.
```php
$dto->isInstanceOf('address', AddressDto::class);
```

#### isNotInstanceOf
You can use the `isNotInstanceOf` helper for checking the DTO is property not instance of.
```php
$dto->isNotInstanceOf('address', AddressDto::class);
```

#### isString
You can use the `isString` helper for checking the DTO is property string.
```php
$dto->isString('name');
```

#### isNotString
You can use the `isNotString` helper for checking the DTO is property not string.
```php
$dto->isNotString('name');
```

#### isInt
You can use the `isInt` helper for checking the DTO is property int.
```php
$dto->isInt('id');
```

#### isNotInt
You can use the `isNotInt` helper for checking the DTO is property not int.
```php
$dto->isNotInt('id');
```

#### isFloat
You can use the `isFloat` helper for checking the DTO is property float.
```php
$dto->isFloat('price');
```

#### isNotFloat
You can use the `isNotFloat` helper for checking the DTO is property not float.
```php
$dto->isNotFloat('price');
```

#### isArray
You can use the `isArray` helper for checking the DTO is property array.
```php
$dto->isArray('addresses');
```

#### isNotArray
You can use the `isNotArray` helper for checking the DTO is property not array.
```php
$dto->isNotArray('addresses');
```

#### isObject
You can use the `isObject` helper for checking the DTO is property object.
```php
$dto->isObject('address');
```

#### isNotObject
You can use the `isNotObject` helper for checking the DTO is property not object.
```php
$dto->isNotObject('address');
```

#### isInstanceOfArray
You can use the `isInstanceOfArray` helper for checking the DTO is property instance of array.
```php
$dto->isInstanceOfArray('addresses', AddressDto::class);
```

#### isNotInstanceOfArray
You can use the `isNotInstanceOfArray` helper for checking the DTO is property not instance of array.
```php
$dto->isNotInstanceOfArray('addresses', AddressDto::class);
```

### Customize http request
You can customize the http request.
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
    
    protected static function httpClient(): PendingRequest
    {
        return Http::withoutVerifying()
            ->withoutRedirecting()
            ->withCookies(['name' => 'value'])
            ->withHeaders(['Authorization' => 'Bearer ' . auth()->user()->token]);
    }
}
```
Or you can customize only headers:
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
        
    protected static function httpHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . auth()->user()->token,
        ];
    }
}
```
Also you can customize what `from` or `fromUrl` can be used for the request POST by default
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    protected static string $defaultHttpMethod = 'post';

    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
}
```
And you can customize the parameters data for the request by default
```php
use Bfg\Dto\Dto;

class UserDto extends Dto
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
    ) {}
    
    protected static function httpData(array|string|null $data): array|string|null
    {
        // Do something with data
        return $data;
    }
}
```


### Default Laravel Support
DTO class use a famous Laravel support, such as `Illuminate\Support\Traits\Conditionable`, `Illuminate\Support\Traits\Dumpable`, 
`Illuminate\Support\Traits\Macroable` and `Illuminate\Support\Traits\Tappable`.


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please see [SECURITY](SECURITY.md) for more information about security.

## Credits

- [Xsaven](mailto:xsaven@gmail.com)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
