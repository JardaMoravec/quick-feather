<?php

namespace Tests\Tool\Entity\Complex;

use Entity\User\User\Phone;
use PHPUnit\Framework\TestCase;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;

class PhoneTypeTest extends TestCase {

	/**
	 * @throws NullError|TypeError|EntityError
	 */
	public function testFromVar(): void {
		$this->assertInstanceOf(Phone::class, Phone::fromVar('123456789', true));
		$this->assertInstanceOf(Phone::class, Phone::fromVar('123456789', false));
	}

	/**
	 * @throws NullError|TypeError|EntityError
	 */
	public function testFromVar2(): void {
		$phone = Phone::fromVar('123456789', true);
		$this->assertInstanceOf(Phone::class, $phone);
		$this->assertEquals('123456789', $phone->getValue());
	}

	/**
	 * @throws NullError|TypeError|EntityError
	 */
	public function testFromVarNull(): void {
		$this->assertNull(Phone::fromVar(''));
		$this->assertNull(Phone::fromVar(' '));
	}

	/**
	 * @throws NullError|TypeError|EntityError
	 */
	public function testFromSpecialChars(): void {
		$phone = Phone::fromVar('123 456 789', true);
		$this->assertInstanceOf(Phone::class, $phone);
		$this->assertEquals('123456789', $phone->getValue());

		$phone = Phone::fromVar('123-456-789', true);
		$this->assertInstanceOf(Phone::class, $phone);
		$this->assertEquals('123456789', $phone->getValue());

		$phone = Phone::fromVar('123/456/789', true);
		$this->assertInstanceOf(Phone::class, $phone);
		$this->assertEquals('123456789', $phone->getValue());

		$phone = Phone::fromVar('+420123456789', true);
		$this->assertInstanceOf(Phone::class, $phone);
		$this->assertEquals('123456789', $phone->getValue());
	}
}
