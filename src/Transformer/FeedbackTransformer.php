<?php

namespace App\Transformer;

use App\DTO\FeedbackDTO;
use App\Entity\Activity;
use App\Entity\Feedback;
use App\Entity\User;

class FeedbackTransformer
{
    /**
     * @param FeedbackDTO $dto
     * @param User $authenticatedUser
     * @param Activity $activity
     * @param User $userTo
     * @return Feedback
     */
    public function addFeedback(
        FeedbackDTO $dto,
        User $authenticatedUser,
        Activity $activity,
        User $userTo
    ): Feedback {
        $feedback = new Feedback();
        $feedback->setStars($dto->stars);
        $feedback->setComment($dto->comment);
        $feedback->setUserFrom($authenticatedUser);
        $feedback->setUserTo($userTo);
        $feedback->setActivity($activity);

        return $feedback;
    }

    /**
     * @param FeedbackDTO $feedbackDTO
     * @param Feedback $feedback
     * @return Feedback
     */
    public function editFeedback(FeedbackDTO $feedbackDTO, Feedback $feedback): Feedback
    {
        $feedback->setStars($feedbackDTO->stars);
        $feedback->setComment($feedbackDTO->comment);

        return $feedback;
    }
}
