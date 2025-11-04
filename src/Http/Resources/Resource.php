<?php

namespace Laravel\Nova\Http\Resources;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Laravel\Nova\Makeable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

abstract class Resource implements Responsable
{
    use Makeable;

    /**
     * Handle the resource for Inertia response.
     */
    public function handle(Request $request): ?array
    {
        try {
            return $this->toArray($request);
        } catch (Throwable $e) {
            if ($e instanceof HttpExceptionInterface) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return response()->json($this->toArray($request));
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    abstract public function toArray($request);
}
