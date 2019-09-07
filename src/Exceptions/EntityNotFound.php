<?php

namespace App\Exceptions;

class EntityNotFound extends TransformerException
{
    /** @var string */
    private $entity;

    /** @var int */
    private $id;

    public function __construct(string $entity, int $id, string $message = '')
    {
        parent::__construct($message);
        $this->entity = $entity;
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
