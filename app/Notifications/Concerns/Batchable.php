<?php

namespace App\Notifications\Concerns;

/**
 * Lets a notification carry a batch id so every channel row it produces (and every
 * recipient in a broadcast) is grouped under one send-operation in the logs.
 */
trait Batchable
{
    public ?string $batchId = null;

    public function withBatchId(?string $batchId): static
    {
        $this->batchId = $batchId;

        return $this;
    }
}
