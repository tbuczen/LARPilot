<?php

namespace App\Tests\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\Larp\Service\ParticipantCodeGenerator;
use App\Domain\Larp\Service\ParticipantCodeValidator;
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
