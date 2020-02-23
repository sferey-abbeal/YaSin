<?php

namespace App\Serializer;

use App\Entity\Image;
use App\Service\ActivityCoverManager;
use App\Service\UserAvatarManager;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;

class ImageSerializationHandler implements SubscribingHandlerInterface
{
    /**
     * @var UserAvatarManager
     */
    private $userAvatarManager;
    /**
     * @var ActivityCoverManager
     */
    private $activityCoverManager;

    public function __construct(
        UserAvatarManager $userAvatarManager,
        ActivityCoverManager $activityCoverManager
    ) {
        $this->userAvatarManager = $userAvatarManager;
        $this->activityCoverManager = $activityCoverManager;
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
                'type' => Image::class,
                'method' => 'serializeImageToJson',
            )
        );
    }

    public function serializeImageToJson(
        JsonSerializationVisitor $visitor,
        Image $image,
        array $type,
        Context $context
    ): array {
        if ($image->getLinkedTo() === Image::IMAGE_TYPE_USER) {
            return $this->userAvatarManager->getAvailableResolutions($image);
        }

        if ($image->getLinkedTo() === Image::IMAGE_TYPE_ACTIVITY) {
            return $this->activityCoverManager->getAvailableResolutions($image);
        }
        return array();
    }
}
