<?php


namespace App\Transformer;

use App\DTO\ActivityTypeDTO;
use App\Entity\ActivityType;

class TypeTransformer
{
    public function transform(ActivityTypeDTO $dto): ActivityType
    {
        $entity = new ActivityType();
        $entity->setName($dto->name);
        $entity->setDescription($dto->description);

        return $entity;
    }

    public function inverseTransform(ActivityType $entity): ActivityTypeDTO
    {
        $dto = new ActivityTypeDTO();
        $dto->name = $entity->getName();
        $dto->description = $entity->getDescription();

        return $dto;
    }
}
