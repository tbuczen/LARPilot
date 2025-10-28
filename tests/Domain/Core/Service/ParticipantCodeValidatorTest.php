<?php

namespace App\Tests\Domain\Core\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\ParticipantCodeGenerator;
use App\Domain\Core\Service\ParticipantCodeValidator;
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
