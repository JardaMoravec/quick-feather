<?php

namespace Tests\Tool\Entity\Complex;

use PHPUnit\Framework\TestCase;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\Type\Complex\Password;

class PasswordTypeTest extends TestCase {

	/**
	 * @throws TypeError
	 * @throws NullError
	 */
	public function testFromVar(): void {
		$this->assertInstanceOf(Password::class, Password::fromVar('heslo', true));
		$this->assertInstanceOf(Password::class, Password::fromVar('heslo', false));
	}

	/**
	 * @throws NullError
	 * @throws TypeError
	 */
	public function testFromVar2(): void {
		$password = Password::fromVar('heslo', true);
		$this->assertInstanceOf(Password::class, $password);
		$this->assertEquals('heslo', $password->getValue());
	}

	/**
	 * @throws NullError
	 * @throws TypeError
	 */
	public function testFromVarNull(): void {
		$this->assertNull(Password::fromVar(''));
		$this->assertNull(Password::fromVar(' '));
	}

	/**
	 * @throws NullError|TypeError
	 */
	public function testFromSpecialChars(): void {
		$password = Password::fromVar('he\\slo', true);
		$this->assertInstanceOf(Password::class, $password);
		$this->assertEquals('heslo', $password->getValue());

		$password = Password::fromVar('he slo', true);
		$this->assertInstanceOf(Password::class, $password);
		$this->assertEquals('heslo', $password->getValue());

		$password = Password::fromVar('he"slo"', true);
		$this->assertInstanceOf(Password::class, $password);
		$this->assertEquals('heslo', $password->getValue());

		$password = Password::fromVar('he\'slo', true);
		$this->assertInstanceOf(Password::class, $password);
		$this->assertEquals('heslo', $password->getValue());

		$password = Password::fromVar("he\'s\"lo", true);
		$this->assertInstanceOf(Password::class, $password);
		$this->assertEquals('heslo', $password->getValue());
	}

	/**
	 * @throws NullError|TypeError
	 */
	public function testFromCamelCase(): void {
		$password = Password::fromVar('HeSlo*', true);
		$this->assertInstanceOf(Password::class, $password);
		$this->assertEquals('HeSlo*', $password->getValue());
	}

	/**
	 * @throws NullError|TypeError
	 */
	public function testFromWithNumbers(): void {
		$password = Password::fromVar('He3Slo*', true);
		$this->assertInstanceOf(Password::class, $password);
		$this->assertEquals('He3Slo*', $password->getValue());
	}

}
