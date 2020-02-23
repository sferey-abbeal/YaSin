<?php


namespace App\Transformer;

use App\DTO\TechnologyDTO;
use App\Entity\Technology;

class TechnologyTransformer
{
    public function transform(TechnologyDTO $dto): Technology
    {
        $entity = new Technology();
        $entity->setName($dto->name);
        $entity->setDescription($dto->description);

        return $entity;
    }

    public function inverseTransform(Technology $entity): TechnologyDTO
    {
        $dto = new TechnologyDTO();
        $dto->name = $entity->getName();
        $dto->description = $entity->getDescription();

        return $dto;
    }

}
