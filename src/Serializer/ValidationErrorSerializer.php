<?php

namespace App\Serializer;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationErrorSerializer
{
    public function serialize(ConstraintViolationListInterface $list): array
    {
        $errors = [];
        foreach ($list as $violation) {
            /** @var ConstraintViolationInterface $violation */
            $error['property_path'] = strtolower(
                preg_replace('/([a-z])([A-Z])/', '$1_$2', $violation->getPropertyPath())
            );
            $error['invalid_value'] = is_object($violation->getInvalidValue()) ?
                get_class($violation->getInvalidValue()) :
                $violation->getInvalidValue();
            $error['code'] = $violation->getCode();
            $error['message'] = $violation->getMessage();
            $error['message_template'] = $violation->getMessageTemplate();

            $errors[] = $error;
        }

        return $errors;
    }
}
