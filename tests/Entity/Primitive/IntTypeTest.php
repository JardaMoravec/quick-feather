<?php

namespace Tests\Tool\Entity\Primitive;

use PHPUnit\Framework\TestCase;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\Primitive\IntType;


class IntTypeTest extends TestCase {

	/**
	 * @throws NullError
	 */
	public function testFromVar(): void {
		$this->assertSame(100, IntType::fromVar(100, true));
		$this->assertSame(100, IntType::fromVar(100.00, true));
		$this->assertSame(100, IntType::fromVar('100', true));
		$this->assertSame(1000, IntType::fromVar('1 000', true));
		$this->assertSame(1000, IntType::fromVar('(1 000)', true));
		$this->assertSame(1000, IntType::fromVar('1000,-', true));
		$this->assertSame(1000, IntType::fromVar('1000Kč', true));
		$this->assertSame(1000, IntType::fromVar('1000 Kč', true));
		$this->assertSame(100, IntType::fromVar('100.00', true));
		$this->assertSame(1, IntType::fromVar('1.5', true));
		$this->assertSame(1, IntType::fromVar('1,5', true));
		$this->assertSame(1, IntType::fromVar('1', true));
		$this->assertSame(0, IntType::fromVar('0', true));
		$this->assertSame(10, IntType::fromVar('010', true));
		$this->assertSame(10, IntType::fromVar('00010', true));
		$this->assertSame(10, IntType::fromVar('00010,00010', true));
	}

	/**
	 * @throws NullError
	 */
	public function testFromVarNull(): void {
		$this->assertNull(IntType::fromVar(''));
		$this->assertNull(IntType::fromVar(' '));
		$this->assertNull(IntType::fromVar('ahoj'));
		$this->assertNull(IntType::fromVar('.'));
		$this->assertNull(IntType::fromVar('\\'));
		$this->assertNull(IntType::fromVar('/'));
		$this->assertNull(IntType::fromVar('nasrat10'));
	}

	public function testFromVarNullException(): void {
		$this->expectException(NullError::class);
		IntType::fromVar(null, true);
	}

	public function testFromVarNullException2(): void {
		$this->expectException(NullError::class);
		IntType::fromVar('', true);
	}

	public function testFromVarNullException3(): void {
		$this->expectException(NullError::class);
		IntType::fromVar('test', true);
	}

	public function testFromVarNullException4(): void {
		$this->expectException(NullError::class);
		IntType::fromVar('/', true);
	}
}
