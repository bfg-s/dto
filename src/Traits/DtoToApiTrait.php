<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Exceptions\DtoHttpRequestException;

trait DtoToApiTrait
{
    /**
     * @param  string  $url
     * @param  string  $method
     * @param  array  $headers
     * @return mixed
     */
    public function toApi(string $url, string $method = 'post', array $headers = []): mixed
    {
        $start = static::startTime();
        $headers = array_merge($headers, static::httpHeaders());
        $method = strtolower($method);
        if (! in_array($method, ['get', 'head', 'post', 'put', 'patch', 'delete'])) {
            $method = 'get';
        }
        $response = static::httpClient()
            ->withHeaders($headers)
            ->{$method}($url, static::httpData($this->toArray()));

        $this->log("toApi", compact('url', 'method', 'headers'), static::endTime($start));

        if ($response->status() >= 400) {
            throw new DtoHttpRequestException($response->body());
        }

        return $response->body();
    }
}
