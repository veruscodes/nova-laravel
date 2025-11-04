<?php

namespace Laravel\Nova\Contracts;

use Illuminate\Bus\PendingBatch;
use Laravel\Nova\Fields\ActionFields;

interface BatchableAction
{
    /**
     * Register `then`, `catch`, and `finally` callbacks on the pending batch.
     *
     * @return void
     */
    public function withBatch(ActionFields $fields, PendingBatch $batch);

    /**
     * Set the batch ID on the job.
     *
     * @return $this
     */
    public function withBatchId(string $batchId);
}
