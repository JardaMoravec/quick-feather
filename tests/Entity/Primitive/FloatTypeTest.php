<?php

namespace Tests\Tool\Entity\Primitive;

use PHPUnit\Framework\TestCase;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\Primitive\FloatType;


class FloatTypeTest extends TestCase {

	/**
	 * @throws NullError
	 */
	public function testFromVar(): void {
		$this->assertSame(100.00, FloatType::fromVar(100, true));
		$this->assertSame(100.00, FloatType::fromVar(100.0000, true));
		$this->assertSame(100.00, FloatType::fromVar('100', true));
		$this->assertSame(1000.00, FloatType::fromVar('1 000', true));
		$this->assertSame(1000.00, FloatType::fromVar('1000,-', true));
		$this->assertSame(1000.00, FloatType::fromVar('1000Kč', true));
		$this->assertSame(1000.00, FloatType::fromVar('1000 Kč', true));
		$this->assertSame(1000.00, FloatType::fromVar('1000.00', true));
		$this->assertSame(1.5, FloatType::fromVar('1.5', true));
		$this->assertSame(1.5, FloatType::fromVar('1,5', true));
		$this->assertSame(1.5, FloatType::fromVar(' 1,5 ', true));
		$this->assertSame(1.5, FloatType::fromVar('1 , 5', true));
		$this->assertSame(0.0, FloatType::fromVar('0', true));
		$this->assertSame(0.0, FloatType::fromVar('0,0', true));
	}

	/**
	 * @throws NullError
	 */
	public function testFromVarNull(): void {
		$this->assertNull(FloatType::fromVar(''));
		$this->assertNull(FloatType::fromVar(' '));
		$this->assertNull(FloatType::fromVar('ahoj'));
		$this->assertNull(FloatType::fromVar('.'));
		$this->assertNull(FloatType::fromVar('\\'));
		$this->assertNull(FloatType::fromVar('/'));
	}

	public function testFromVarNullException(): void {
		$this->expectException(NullError::class);
		FloatType::fromVar(null, true);
	}

	public function testFromVarNullException2(): void {
		$this->expectException(NullError::class);
		FloatType::fromVar('', true);
	}

	public function testFromVarNullException3(): void {
		$this->expectException(NullError::class);
		FloatType::fromVar('test', true);
	}

	public function testFromVarNullException4(): void {
		$this->expectException(NullError::class);
		FloatType::fromVar('/', true);
	}

	public function testFormat(): void {
		$this->assertEquals('1,23', FloatType::format(1.23456, 2));
		$this->assertEquals('1', FloatType::format(1.23456));
		$this->assertEquals('1,23', FloatType::format(1.23456, 2));
		$this->assertEquals('12-345.679', FloatType::format(12345.6789, 3, '.', '-'));
		$this->assertEquals('12 345.68', FloatType::format(12345.6789, 2, '.'));
	}
}
