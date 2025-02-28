<?php

namespace Tests\Tool\Entity\Primitive;

use PHPUnit\Framework\TestCase;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\Primitive\BoolType;

class BoolTypeTest extends TestCase {

	/**
	 * @throws NullError
	 */
	public function testFromVarTrue(): void {
		$this->assertTrue(BoolType::fromVar(true, true));
		$this->assertTrue(BoolType::fromVar('true', true));
		$this->assertTrue(BoolType::fromVar('1', true));
		$this->assertTrue(BoolType::fromVar('True', true));
		$this->assertTrue(BoolType::fromVar('TRUE', true));
		$this->assertTrue(BoolType::fromVar('yes', true));
		$this->assertTrue(BoolType::fromVar('YES', true));
	}

	/**
	 * @throws NullError
	 */
	public function testFromVarFalse(): void {
		$this->assertFalse(BoolType::fromVar(false, true));
		$this->assertFalse(BoolType::fromVar('false', true));
		$this->assertFalse(BoolType::fromVar('0', true));
		$this->assertFalse(BoolType::fromVar('False', true));
		$this->assertFalse(BoolType::fromVar('FALSE', true));
		$this->assertFalse(BoolType::fromVar('no', true));
		$this->assertFalse(BoolType::fromVar('NO', true));
	}

	/**
	 * @throws NullError
	 */
	public function testFromVarNull(): void {
		$this->assertNull(BoolType::fromVar(null));
		$this->assertNull(BoolType::fromVar('...'));
		$this->assertNull(BoolType::fromVar('abc'));
		$this->assertNull(BoolType::fromVar('100'));
		$this->assertNull(BoolType::fromVar(''));
		$this->assertNull(BoolType::fromVar(' '));
		$this->assertNull(BoolType::fromVar('"'));
	}

	public function testFromVarNullException(): void {
		$this->expectException(NullError::class);
		BoolType::fromVar(null, true);
	}

	public function testFromVarNullException2(): void {
		$this->expectException(NullError::class);
		BoolType::fromVar('', true);
	}

	public function testFromVarNullException3(): void {
		$this->expectException(NullError::class);
		BoolType::fromVar(' ', true);
	}

	public function testFromVarNullException4(): void {
		$this->expectException(NullError::class);
		BoolType::fromVar('blbost', true);
	}
}
