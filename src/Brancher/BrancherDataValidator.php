<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Brancher;

use Hypernode\Deploy\Exception\ValidationException;

class BrancherDataValidator
{
    /**
     * @throws ValidationException
     */
    public static function validateLabels(array $labels)
    {
        $violations = array_map(
            fn (string $label) => !preg_match('/^[a-zA-Z0-9-_=]+$/', $label),
            $labels
        );

        if (array_filter($violations)) {
            throw new ValidationException(sprintf(
                'One or more of the provided labels are not valid. Labels provided: [%s].',
                implode(', ', $labels)
            ));
        }
    }

    /**
     * @throws ValidationException
     */
    public static function validate(array $data)
    {
        if (!empty($data['labels'])) {
            self::validateLabels($data['labels']);
        }
    }
}
