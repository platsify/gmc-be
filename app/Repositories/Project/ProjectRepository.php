<?php

namespace App\Repositories\Project;

use App\Models\Category;
use App\Repositories\BaseRepository;

class ProjectRepository  extends BaseRepository implements ProjectRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\Project::class;
    }

    public function upsertByOriginalId($originalId, $attributes) {
       return $this->upsertBySpecificField('original_id', $originalId, $attributes);
    }
}
