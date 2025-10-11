<?php

namespace App\Tests\Service;

use App\Entity\Larp;
use App\Service\Larp\ParticipantCodeGenerator;
use App\Service\Larp\ParticipantCodeValidator;
use PHPUnit\Framework\TestCase;

class ParticipantCodeValidatorTest extends TestCase
{
    public function testValidCode(): void
    {
        $larp = new Larp();
        $generator = new ParticipantCodeGenerator();
        $validator = new ParticipantCodeValidator();
        $code = $generator->generate($larp);
        $this->assertTrue($validator->validate($code, $larp));
    }

    public function testInvalidCode(): void
    {
        $larp = new Larp();
        $other = new Larp();
        $generator = new ParticipantCodeGenerator();
        $validator = new ParticipantCodeValidator();
        $code = $generator->generate($larp);
        $this->assertFalse($validator->validate($code, $other));
    }
}
