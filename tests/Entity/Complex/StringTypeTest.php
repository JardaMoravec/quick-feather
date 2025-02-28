<?php

namespace Tests\Tool\Entity\Complex;

use PHPUnit\Framework\TestCase;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\Complex\String\String100;
use QuickFeather\EntityManager\Type\Complex\String\String200;
use QuickFeather\EntityManager\Type\Primitive\StringType;

class StringTypeTest extends TestCase {

	/**
	 * @throws NullError|TypeError|EntityError
	 */
	public function testFromVar(): void {
		$this->assertInstanceOf(String100::class, String100::fromVar('test', true));
		$this->assertInstanceOf(String200::class, String200::fromVar('test', false));
	}

	/**
	 * @throws NullError
	 * @throws EntityError
	 */
	public function testCheckValues(): void {
		$this->assertEquals('test', String100::fromVar('test', true)->getValue());
		$this->assertEquals('test', String200::fromVar('test', false)->getValue());

		$this->assertEquals('testtest', String200::fromVar('test\\test', backSlash: BaseType::remove)->getValue());
		$this->assertEquals('testtest', String200::fromVar('test\\\\test', backSlash: BaseType::remove)->getValue());
		$this->assertEquals('test\\test', String200::fromVar('test\\test', backSlash: BaseType::decode)->getValue());
		$this->assertEquals('test\\\\test', String200::fromVar('test\\\\test', backSlash: BaseType::decode)->getValue());

		$this->assertEquals('test', String100::fromVar(" t e s t ", whiteSpace: BaseType::remove)->getValue());
		$this->assertEquals('test', String100::fromVar("\nt e s t ", whiteSpace: BaseType::remove)->getValue());
		$this->assertEquals('est', String100::fromVar("\t e s t ", whiteSpace: BaseType::remove)->getValue());
		$this->assertEquals('test', String100::fromVar("t e s t\t ", whiteSpace: BaseType::remove)->getValue());
		$this->assertEquals('test', String100::fromVar("\nt\x0Be s t ", whiteSpace: BaseType::remove)->getValue());

		$this->assertEquals('test', String100::fromVar('<b>test</b>', html: BaseType::remove)->getValue());
		$this->assertEquals("&lt;b&gt;test&lt;/b&gt;", String100::fromVar("<b>test</b>", html: BaseType::encode)->getValue());
		$this->assertEquals("<b>test</b>", String100::fromVar("&lt;b&gt;test&lt;/b&gt;", html: BaseType::decode)->getValue());
		$this->assertEquals('test&amp;', String100::fromVar("test&", html: BaseType::encode)->getValue());
		$this->assertEquals('test&', String100::fromVar("test&amp;", html: BaseType::decode)->getValue());

		$this->assertEquals('Pli luouk k pl belsk dy.', String100::fromVar('Příliš žluťoučký kůň pěl ďábelské údy.', diacritic: BaseType::remove)->getValue());
		$this->assertEquals('Prilis zlutoucky kun pel dabelske ody.', String100::fromVar('Příliš žluťoučký kůň pěl ďábelské ódy.', diacritic: BaseType::strip)->getValue());

		$this->assertEquals('test koblížek', String100::fromVar("Test KoBlíŽek", transform: BaseType::lowerCase)->getValue());
		$this->assertEquals('TEST KOBLÍŽEK', String100::fromVar("Test KoBlíŽek", transform: BaseType::upperCase)->getValue());

		$this->assertEquals('jsem bůch', String100::fromVar('jsem\' "bůch"', quote: BaseType::remove)->getValue());
		$this->assertEquals('jsem bůch', String100::fromVar("jsem' \"bůch\"", quote: BaseType::remove)->getValue());
		$this->assertEquals('jsem&apos; &quot;bůch&quot;', StringType::fromVar("jsem' \"bůch\"", quote: BaseType::encode));
		$this->assertEquals('jsem&apos; &quot;bůch&quot;', StringType::fromVar('jsem\' "bůch"', quote: BaseType::encode));
		$this->assertEquals('jsem "bůch"', String100::fromVar('jsem &quot;bůch&quot;', quote: BaseType::decode)->getValue());

		$this->assertEquals('Test-zek-JeliTo', String100::fromVar('Test-řízek-&JeliTo', all: BaseType::remove)->getValue());
		$this->assertEquals('test-zek-jelito', String100::fromVar('Test-řízek-&JeliTo', transform: BaseType::lowerCase, all: BaseType::remove)->getValue());
		$this->assertEquals('test-zek-jelito', String100::fromVar('Test#-ří%zek-&Jel%iTo', transform: BaseType::lowerCase, all: BaseType::remove)->getValue());
		$this->assertEquals('test-zek-jelito', String100::fromVar('Test#-"ří%zek-&"J"el%iTo', transform: BaseType::lowerCase, all: BaseType::remove)->getValue());
		$this->assertEquals('test-zek-jelito', String100::fromVar("Test#-\"ří%zek-&\'J\"el%iTo", transform: BaseType::lowerCase, all: BaseType::remove)->getValue());
	}

}
