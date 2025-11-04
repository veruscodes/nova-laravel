<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Illuminate\Database\Eloquent\Model;

class Replicate extends Create
{
    /**
     * The source Resource ID.
     *
     * @var \Illuminate\Database\Eloquent\Model|string|int
     */
    public mixed $fromResourceId;

    /**
     * Create a new page instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $fromResourceId
     * @param  array<string, mixed>  $queryParams
     */
    public function __construct(
        string $resourceName,
        mixed $fromResourceId,
        array $queryParams = []
    ) {
        parent::__construct($resourceName, $queryParams);

        $this->fromResourceId = $fromResourceId instanceof Model ? $fromResourceId->getKey() : $fromResourceId;

        $this->setNovaPage("/resources/{$this->resourceName}/{$this->fromResourceId}/replicate");
    }
}
