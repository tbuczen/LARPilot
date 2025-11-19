<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\ParticipantCodeGenerator;
use App\Domain\Core\Service\ParticipantCodeValidator;
use Codeception\Test\Unit;
use Tests\Support\UnitTester;

class ParticipantCodeValidatorTest extends Unit
{
    public function validCodeIsAccepted(UnitTester $I): void
    {
        $I->wantTo('verify that valid participant codes are accepted');

        $larp = new Larp();
        $generator = new ParticipantCodeGenerator();
        $validator = new ParticipantCodeValidator();

        $code = $generator->generate($larp);

        $I->assertTrue($validator->validate($code, $larp));
    }

    public function invalidCodeIsRejected(UnitTester $I): void
    {
        $I->wantTo('verify that invalid participant codes are rejected');

        $larp = new Larp();
        $other = new Larp();
        $generator = new ParticipantCodeGenerator();
        $validator = new ParticipantCodeValidator();

        $code = $generator->generate($larp);

        $I->assertFalse($validator->validate($code, $other));
    }
}
