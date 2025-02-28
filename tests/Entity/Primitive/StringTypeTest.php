<?php

namespace Tests\Tool\Entity\Primitive;

use PHPUnit\Framework\TestCase;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\Primitive\IntType;
use QuickFeather\EntityManager\Type\Primitive\StringType;

class StringTypeTest extends TestCase {

	/**
	 * @throws NullError
	 */
	public function testFromVarBackSlash(): void {
		$this->assertSame('tes\t', StringType::fromVar('tes\t', true));
		$this->assertSame('test', StringType::fromVar('te\st', true, backSlash: BaseType::remove));
		$this->assertSame('test', StringType::fromVar('te\\st', true, backSlash: BaseType::remove));
		$this->assertSame('test1', StringType::fromVar('te\st\1', true, backSlash: BaseType::remove));
		$this->assertSame('te&#92;st&#92;1', StringType::fromVar('te\st\1', true, backSlash: BaseType::encode));
		$this->assertSame('te\st\1', StringType::fromVar('te&#92;st&#92;1', true, backSlash: BaseType::decode));
	}

	/**
	 * @throws NullError
	 */
	public function testFromVarSlash(): void {
		$this->assertSame('/test/', StringType::fromVar('/test/', true));
		$this->assertSame('test', StringType::fromVar('te/st', true, slash: BaseType::remove));
		$this->assertSame('test', StringType::fromVar('te//st', true, slash: BaseType::remove));
		$this->assertSame('test1', StringType::fromVar('te/st/1', true, slash: BaseType::remove));
		$this->assertSame('te\&frasl;st&frasl;1', StringType::fromVar('te\/st/1', true, slash: BaseType::encode));
		$this->assertSame('te/st/1', StringType::fromVar('te&frasl;st&frasl;1', true, slash: BaseType::decode));
	}

	/**
	 * @throws NullError
	 */
	public function testFromVarQuotes(): void {
		$this->assertSame('"test"', StringType::fromVar('"test"', true));
		$this->assertSame('test', StringType::fromVar('"test"', true, quote: BaseType::remove));
		$this->assertSame('tests', StringType::fromVar('test\'s', true, quote: BaseType::remove));
		$this->assertSame('tete\st', StringType::fromVar('te"te\"st\'', true, quote: BaseType::remove));
		$this->assertSame('te&quot;te&quot;st&apos;', StringType::fromVar('te"te"st\'', true, quote: BaseType::encode));
		$this->assertSame('te"te"st\'', StringType::fromVar('te&quot;te&quot;st&apos;', true, quote: BaseType::decode));
	}

	/**
	 * @throws NullError
	 */
	public function testFromVarWhiteSpaces(): void {
		$this->assertSame('test 1', StringType::fromVar('test 1', true));
		$this->assertSame('test1', StringType::fromVar("\tte st \n1", true, whiteSpace: BaseType::remove));
		$this->assertSame("--te-st--1-", StringType::fromVar(" \tte st \n1 ", true, whiteSpace: BaseType::encode));
		$this->assertSame("te st \n1", StringType::fromVar(" \tte st \n1 ", true, whiteSpace: BaseType::trim));
	}

	/**
	 * @throws NullError
	 */
	public function testFromVarHTML(): void {
		$this->assertSame('<b>test 1</b>', StringType::fromVar('<b>test 1</b>', true));
		$this->assertSame('test 1', StringType::fromVar('<b>test 1</b>', true, html: BaseType::remove));
		$this->assertSame('&amp;test 1', StringType::fromVar('&test 1', true, html: BaseType::encode));
		$this->assertSame('&lt;b&gt;test 1&lt;/b&gt;', StringType::fromVar('<b>test 1</b>', true, html: BaseType::encode));
		$this->assertSame('<b>test 1</b>', StringType::fromVar('&lt;b&gt;test 1&lt;/b&gt;', true, html: BaseType::decode));
		$this->assertSame('&lt;b&gt;&amp;nbsp;test 1&lt;/b&gt;', StringType::fromVar('<b>&nbsp;test 1</b>', true, html: BaseType::encode));
		$this->assertSame('<b>&nbsp;test 1</b>', StringType::fromVar('&lt;b&gt;&amp;nbsp;test 1&lt;/b&gt;', true, html: BaseType::decode));
	}

	/**
	 * @throws NullError
	 */
	public function testFromVarTransform(): void {
		$this->assertSame('Test 1', StringType::fromVar('Test 1', true));
		$this->assertSame('test 1', StringType::fromVar('Test 1', true, transform: BaseType::lowerCase));
		$this->assertSame('TEST 1', StringType::fromVar('Test 1', true, transform: BaseType::upperCase));
	}

	/**
	 * @throws NullError
	 * @todo včetně úprav
	 */
	public function testFromVarNull(): void {
		$this->assertNull(StringType::fromVar(''));
		$this->assertNull(StringType::fromVar());
	}

	public function testFromVarNullException(): void {
		$this->expectException(NullError::class);
		IntType::fromVar(null, true);
	}

	/**
	 * @throws NullError
	 */
	public function testConvertToSEOFormat(): void {
		$text = "<b>%Žluťoučký kůň - hračka.</b>";
		$seo = StringType::fromVar($text,
			whiteSpace: BaseType::encode, html: BaseType::remove,
			diacritic: BaseType::encode, separator: BaseType::remove,
			specialChar: BaseType::remove, transform: BaseType::lowerCase
		);
		$this->assertSame('zlutoucky-kun---hracka', $seo);
	}
}
