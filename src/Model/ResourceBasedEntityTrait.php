<?php

namespace Muffin\Webservice\Model;

trait ResourceBasedEntityTrait
{

    public static function createFromResource(Resource $resource, array $options = [])
    {
        $entity = new self();

        $entity->applyResource($resource);

        return $entity;
    }

    public function applyResource(Resource $resource)
    {
        $this->set($resource->toArray());
    }
}
