<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Illuminate\Contracts\Encryption\Encrypter;

trait DtoSystemVariablesTrait
{
    /**
     * The logs
     *
     * @var array
     */
    protected static array $__logs = [];

    /**
     * The built-in, primitive cast types supported by Eloquent.
     *
     * @var string[]
     */
    protected static array $__primitiveCastTypes = [
        'array',
        'bool',
        'boolean',
        'collection',
        'custom_datetime',
        'date',
        'datetime',
        'decimal',
        'double',
        'encrypted',
        'encrypted:array',
        'encrypted:collection',
        'encrypted:json',
        'encrypted:object',
        'float',
        'hashed',
        'immutable_date',
        'immutable_datetime',
        'immutable_custom_datetime',
        'int',
        'integer',
        'json',
        'object',
        'real',
        'string',
        'timestamp',
    ];

    /**
     * The encrypter instance that is used to encrypt attributes.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter|null
     */
    public static ?Encrypter $__encrypter = null;

    /**
     * Created events for dto
     *
     * @var array
     */
    protected static array $__events = [];

    /**
     * Possible events names
     *
     * @var array|string[]
     */
    protected static array $__eventsNames = [
        'creating', 'created', 'updating', 'updated', 'serialize', 'unserialize',
        'clone', 'fromModel', 'fromEmpty', 'fromArray', 'fromRequest', 'fromJson', 'fromSerialize',
        'prepareModel', 'prepareSerialize', 'prepareJson', 'prepareRequest', 'prepareArray', 'prepareEmpty',
        'destruct'
    ];

    /**
     * Set without casting
     *
     * @var bool
     */
    protected static bool $__setWithoutCasting = false;

    /**
     * Set log mute
     *
     * @var bool
     */
    protected static bool $__logMute = false;

    /**
     * The lazy cache.
     *
     * @var array
     */
    protected static array $__lazyCache = [];

    /**
     * The original data
     *
     * @var array
     */
    protected static array $__originals = [];

    /**
     * The meta data
     *
     * @var array
     */
    protected static array $__meta = [];

    /**
     * The reflection class
     *
     * @var array
     */
    protected static array $__reflections = [];

    /**
     * The vars of class
     *
     * @var array
     */
    protected static array $__vars = [];

    /**
     * The constructor parameters
     *
     * @var array
     */
    protected static array $__constructorParameters = [];

    /**
     * Extended list parameters
     *
     * @var array
     */
    protected static array $__parameters = [];

    /**
     * The cache of instances
     *
     * @var array
     */
    protected static array $__cache = [];

    /**
     * @var array
     */
    protected static array $__logStartTime = [];

    /**
     * @var array
     */
    protected static array $__models = [];

    /**
     * @var array
     */
    protected static array $__requestKeys = [];

    /**
     * @var array
     */
    protected static array $__settings = [];

    /**
     * @var array
     */
    protected static array $__importType = [];

    /**
     * @var array
     */
    protected static array $__defaultCallbacks = [];
}
