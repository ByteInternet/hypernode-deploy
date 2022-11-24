<?php

declare(strict_types=1);

namespace Brancher;

use Hypernode\Deploy\Brancher\BrancherDataValidator;
use Hypernode\Deploy\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class BrancherDataValidatorTest extends TestCase
{
    public function testValidateLabels()
    {
        BrancherDataValidator::validateLabels([
            'label',
            'mylabel=myvalue'
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            'One or more of the provided labels are not valid. Labels provided: [label, mylabel = myvalue].'
        );
        BrancherDataValidator::validateLabels([
            'label',
            'mylabel = myvalue'
        ]);
    }

    public function testValidate()
    {
        $this->expectException(ValidationException::class);
        BrancherDataValidator::validate(['labels' => ['mylabel = myvalue']]);
    }
}
