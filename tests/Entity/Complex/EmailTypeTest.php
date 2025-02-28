<?php

namespace Tests\Tool\Entity\Complex;

use PHPUnit\Framework\TestCase;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\Type\Complex\Email;

class EmailTypeTest extends TestCase {

	/**
	 * @throws EntityError
	 */
	public function testFromVar(): void {
		$this->assertInstanceOf(Email::class, Email::fromVar('mujmail@email.cz', true));
		$this->assertInstanceOf(Email::class, Email::fromVar('MujMail@email.cz', true));
		$this->assertInstanceOf(Email::class, Email::fromVar('mujmail@EMAIL.cz', true));
	}

	/**
	 * @throws EntityError
	 */
	public function testFromVar2(): void {
		$email = Email::fromVar('MujMail@email.cz', true);
		$this->assertInstanceOf(Email::class, $email);
		$this->assertEquals('mujmail@email.cz', $email->getValue());
	}

	/**
	 * @throws EntityError
	 */
	public function testFromVarNull(): void {
		$this->assertNull(Email::fromVar(''));
		$this->assertNull(Email::fromVar(' '));
	}

	/**
	 * @throws NullError
	 */
	public function testFromVarNullException(): void {
		$this->expectException(EntityError::class);
		Email::fromVar(null, true);
	}

	/**
	 * @throws NullError|EntityError
	 */
	public function testFromVarNullException2(): void {
		$this->expectException(TypeError::class);
		Email::fromVar('ahoj', true);
	}

	/**
	 * @throws NullError|EntityError
	 */
	public function testFromVarNullException3(): void {
		$this->expectException(TypeError::class);
		Email::fromVar('ahoj@domena', true);
	}

	/**
	 * @throws NullError
	 */
	public function testFromVarNullException4(): void {
		$this->expectException(EntityError::class);
		Email::fromVar('ahoj@domenadomenadomenadomenadomenadomenadomenadomenadomenadomenadomenadomenadomenadomenadomenadomenadomenadomenadomenadomenadomena.cz', true);
	}
}
