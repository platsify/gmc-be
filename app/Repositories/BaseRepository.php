<?php

namespace App\Repositories;

use App\Repositories\RepositoryInterface;

abstract class BaseRepository implements RepositoryInterface
{
    protected $model;

    public function __construct()
    {
        $this->setModel();
    }

    abstract public function getModel();

    public function setModel()
    {
        $this->model = app()->make(
            $this->getModel()
        );
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function getPaginate() {
        return $this->model->paginate();
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function findBySpecificField($fieldName, $fieldValue) {
        return $this->model->where($fieldName, $fieldValue)->first();
    }

    public function create($attributes = [])
    {
        return $this->model->create($attributes);
    }

    public function update($id, $attributes = [])
    {
        $result = $this->find($id);
        if ($result) {
            $result->update($attributes);
            return $result;
        }

        return false;
    }

    public function upsertBySpecificField($fieldName, $fieldValue, $attributes) {
        $item = $this->findBySpecificField($fieldName, $fieldValue);
        if (!$item) {
            return $this->create($attributes);
        }

        return $this->update($item->id, $attributes);
    }

    public function delete($id): bool
    {
        $result = $this->find($id);
        if ($result) {
            $result->delete();

            return true;
        }

        return false;
    }
}
