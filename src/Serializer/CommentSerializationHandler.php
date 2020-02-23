<?php

namespace App\Serializer;

use App\Entity\Comment;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;

class CommentSerializationHandler implements SubscribingHandlerInterface
{

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return array
     */
    public static function getSubscribingMethods(): array
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => Comment::class,
                'method' => 'serializeCommentToJson',
            )
        );
    }

    public function serializeCommentToJson(
        JsonSerializationVisitor $visitor,
        Comment $comment,
        array $type,
        Context $context
    ): array {
        /** @var SerializationContext $serializationContext */
        $serializationContext = SerializationContext::create()->setGroups(array('Comment'));

        $json = $this->serializer->serialize(
            $comment->getUser(),
            'json',
            $serializationContext
        );

        /** @var Comment $parent */
        $parent = $comment->getParent();
        if ($comment->getDeleted() === true) {
            return array(
                'id' => $comment->getId(),
                'user' => json_decode($json, true),
                'comment' => 'Comment was deleted',
                'parent' => $parent ? $parent->getId() : null,
                'deleted' => $comment->getDeleted(),
                'created_at' => $comment->getCreatedAt()->getTimestamp(),
                'updated_at' => $comment->getUpdatedAt()->getTimestamp()
            );
        }
        return array(
            'id' => $comment->getId(),
            'user' => json_decode($json, true),
            'comment' => $comment->getComment(),
            'parent' => $parent ? $parent->getId() : null,
            'deleted' => $comment->getDeleted(),
            'created_at' => $comment->getCreatedAt()->getTimestamp(),
            'updated_at' => $comment->getUpdatedAt()->getTimestamp()
        );
    }
}
