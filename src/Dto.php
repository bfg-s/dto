<?php

declare(strict_types=1);

namespace Bfg\Dto;

use AllowDynamicProperties;
use ArrayAccess;
use Bfg\Dto\Interfaces\DtoContract;
use Bfg\Dto\Traits\DtoCastingTrait;
use Bfg\Dto\Traits\DtoCastUsingTrait;
use Bfg\Dto\Traits\DtoConstructorTrait;
use Bfg\Dto\Traits\DtoEventsTrait;
use Bfg\Dto\Traits\DtoHelpersTrait;
use Bfg\Dto\Traits\DtoLogTrait;
use Bfg\Dto\Traits\DtoMagicTrait;
use Bfg\Dto\Traits\DtoMetaTrait;
use Bfg\Dto\Traits\DtoReflectionTrait;
use Bfg\Dto\Traits\DtoSystemVariablesTrait;
use Bfg\Dto\Traits\DtoSystemTrait;
use Bfg\Dto\Traits\DtoToApiTrait;
use Bfg\Dto\Traits\DtoToArrayTrait;
use Bfg\Dto\Traits\DtoToBase64Trait;
use Bfg\Dto\Traits\DtoToCollectionTrait;
use Bfg\Dto\Traits\DtoToFluentTrait;
use Bfg\Dto\Traits\DtoToImportTrait;
use Bfg\Dto\Traits\DtoToJsonTrait;
use Bfg\Dto\Traits\DtoToModelTrait;
use Bfg\Dto\Traits\DtoToResponseTrait;
use Bfg\Dto\Traits\DtoToSerializeTrait;
use Bfg\Dto\Traits\DtoToStringTrait;
use Bfg\Dto\Traits\DtoToUrlTrait;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use IteratorAggregate;

/**
 * Class Dto
 *
 * An abstract base class for Data Transfer Objects (DTOs), providing
 * common functionality for handling data transformations, casting,
 * validation, and serialization.
 *
 * @package Bfg\Dto
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model|null
 *
 * @implements ArrayAccess<string, mixed>
 * @implements Arrayable<string, mixed>
 * @implements Jsonable<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 *
 * @property string $dtoVersion             The version of the DTO.
 * @property array  $dtoCast                The casting rules for DTO properties.
 * @property array  $dtoHidden              The hidden properties.
 * @property array  $dtoValidateRules       The validation rules.
 * @property array  $dtoValidateMessages    The validation rule messages.
 * @property array  $dtoEncrypted           The properties that should be encrypted.
 * @property string $dtoDateFormat          The storage format for date columns.
 * @property bool   $dtoLogsEnabled         Indicates if logging is enabled for this DTO.
 */
#[AllowDynamicProperties]
abstract class Dto implements DtoContract, Arrayable, Jsonable, ArrayAccess, Castable, IteratorAggregate
{
    use DtoSystemVariablesTrait;
    /** @use DtoConstructorTrait<TModel> */
    use DtoConstructorTrait;
    use DtoReflectionTrait;
    use DtoCastUsingTrait;
    use DtoCastingTrait;
    /** @use DtoHelpersTrait<TModel> */
    use DtoHelpersTrait;
    use DtoSystemTrait;
    use DtoEventsTrait;
    use DtoMagicTrait {
        DtoMagicTrait::__call as magicCall;
    }
    use DtoMetaTrait;
    use DtoLogTrait;

    use DtoToCollectionTrait;
    use DtoToSerializeTrait;
    use DtoToResponseTrait;
    use DtoToBase64Trait;
    use DtoToStringTrait;
    use DtoToImportTrait;
    use DtoToFluentTrait;
    use DtoToArrayTrait;
    use DtoToModelTrait;
    use DtoToJsonTrait;
    use DtoToUrlTrait;
    use DtoToApiTrait;

    use Conditionable;
    use Macroable {
        Macroable::__call as macroCall;
    }
    use Tappable;

    /**
     * The version of the dto
     *
     * @var string
     */
    protected static string $dtoVersion = '1.0';

    /**
     * The extends properties
     *
     * @var array
     */
    protected static array $extends = [];

    /**
     * Dto cast properties.
     *
     * @var array
     */
    protected static array $dtoCast = [];

    /**
     * The hidden properties
     *
     * @var array
     */
    protected static array $dtoHidden = [];

    /**
     * The validation rules
     *
     * @var array
     */
    protected static array $dtoValidateRules = [];

    /**
     * The validation rule messages
     *
     * @var array
     */
    protected static array $dtoValidateMessages = [];

    /**
     * The encrypted properties
     *
     * @var array
     */
    protected static array $dtoEncrypted = [];

    /**
     * The storage format of the date columns
     *
     * @var string
     */
    protected static string $dtoDateFormat = 'Y-m-d H:i:s';

    /**
     * Is logs enabled
     *
     * @var bool
     */
    protected static bool $dtoLogsEnabled = false;

    /**
     * Use post by default for anything constructor
     *
     * @var 'get'|'post'|'put'|'patch'|'delete'
     */
    protected static string $defaultHttpMethod = 'get';

    /**
     * Allow dynamic properties.
     *
     * This property is used to control whether dynamic properties
     * are allowed on the DTO instances.
     *
     * If DTO constructors don't have properties, this is allowed automatically.
     *
     * @var bool
     */
    protected static bool $allowDynamicProperties = false;

    /**
     * The source for casting.
     * If Set this source, you dto will be converted to this string.
     *
     * @var string|null
     */
    protected static string|null $__source = null;

    /**
     * Handle dynamic method calls.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->magicCall($method, $parameters);
    }
}
