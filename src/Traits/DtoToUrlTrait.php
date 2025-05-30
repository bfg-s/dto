<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Illuminate\Support\Facades\Route;

trait DtoToUrlTrait
{
    /**
     * Convert an object to the URL format string from which the object was created.
     *
     * @param  string|null  $baseUrl
     * @param  array  $exclude
     * @param  array  $only
     * @param  array  $query
     * @param  bool  $absolute
     * @return string
     */
    public function toUrl(
        string|null $baseUrl = null,
        array $exclude = [],
        array $only = [],
        array $query = [],
        bool $absolute = true
    ): string {
        if ($baseUrl && Route::has($baseUrl)) {
            $router = Route::getRoutes()->getByName($baseUrl);
            $baseUrl = ($absolute ? ($router->getDomain() ?: request()->root()) . '/' : '')
                . $router->uri();
        }
        $parameters = array_merge($query, $this->toArray());
        $parametersForQuery = [];
        $url = (string) dto_string_replace($baseUrl ?: '', $parameters);
        if (count($exclude) === 1 && $exclude[0] === '*') {
            $exclude = array_keys($parameters);
        }
        if ($baseUrl) {
            foreach ($parameters as $key => $value) {
                if (! preg_match("/\{{$key}([?]?)}/", $baseUrl) && ! in_array($key, $exclude) && (! $only || in_array($key, $only))) {
                    $parametersForQuery[$key] = $value;
                }
            }
        } else {
            $parametersForQuery = array_diff_key($parameters, array_flip($exclude));
            if ($only) {
                $parametersForQuery = array_intersect_key($parametersForQuery, array_flip($only));
            }
        }
        $query = http_build_query($parametersForQuery, '', '&', PHP_QUERY_RFC3986);
        return $url . ($query ? (str_contains($url, '?') ? '&' : '?') . $query : '');
    }
}
