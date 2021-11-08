<?php

namespace App\Repositories\Project;

use App\Repositories\RepositoryInterface;

interface ProjectRepositoryInterface extends RepositoryInterface
{
    public function upsertByOriginalId($originalId, $attributes);
}
