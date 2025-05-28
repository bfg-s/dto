<?php

declare(strict_types=1);

namespace Bfg\Dto;

use ArrayAccess;
use Bfg\Dto\Interfaces\DtoContract;
use Bfg\Dto\Traits\DtoCastingTrait;
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
use Bfg\Dto\Traits\DtoToJsonTrait;
use Bfg\Dto\Traits\DtoToModelTrait;
use Bfg\Dto\Traits\DtoToResponseTrait;
use Bfg\Dto\Traits\DtoToSerializeTrait;
use Bfg\Dto\Traits\DtoToStringTrait;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;

/**
 * Class Dto
 *
 * An abstract base class for Data Transfer Objects (DTOs), providing
 * common functionality for handling data transformations, casting,
 * validation, and serialization.
 *
 * @package Bfg\Dto
 *
 * @implements ArrayAccess<string, mixed>
 * @implements Arrayable<string, mixed>
 * @implements Jsonable<string, mixed>
 *
 * @property string $version       The version of the DTO.
 * @property array  $cast          The casting rules for DTO properties.
 * @property array  $hidden        The hidden properties.
 * @property array  $rules         The validation rules.
 * @property array  $ruleMessages  The validation rule messages.
 * @property array  $encrypted     The properties that should be encrypted.
 * @property string $dateFormat    The storage format for date columns.
 * @property bool   $logsEnabled   Indicates if logging is enabled for this DTO.
 */
abstract class Dto implements DtoContract, Arrayable, Jsonable, ArrayAccess, Castable
{
    use DtoSystemVariablesTrait;
    use DtoConstructorTrait;
    use DtoReflectionTrait;
    use DtoCastingTrait;
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
    use DtoToArrayTrait;
    use DtoToModelTrait;
    use DtoToJsonTrait;
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
    protected static string $version = '1.0';

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
    protected static array $cast = [];

    /**
     * The hidden properties
     *
     * @var array
     */
    protected static array $hidden = [];

    /**
     * The validation rules
     *
     * @var array
     */
    protected static array $rules = [];

    /**
     * The validation rule messages
     *
     * @var array
     */
    protected static array $ruleMessages = [];

    /**
     * The encrypted properties
     *
     * @var array
     */
    protected static array $encrypted = [];

    /**
     * The storage format of the date columns
     *
     * @var string
     */
    protected static string $dateFormat = 'Y-m-d H:i:s';

    /**
     * Is logs enabled
     *
     * @var bool
     */
    protected static bool $logsEnabled = false;

    /**
     * Use post by default for anything constructor
     *
     * @var bool
     */
    protected static bool $postDefault = false;

    /**
     * The source for casting.
     * If Set this source, you dto will be converted to this string.
     *
     * @var string|null
     */
    protected static string|null $source = null;

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
