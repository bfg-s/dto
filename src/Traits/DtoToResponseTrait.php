<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Illuminate\Http\JsonResponse;

trait DtoToResponseTrait
{
    /**
     * Generate response from DTO
     *
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse(int $status = 200, array $headers = [], int $options = 0): JsonResponse
    {
        return response()->json([
            'data' => $this->toArray()
        ], $status, $headers, $options);
    }
}
