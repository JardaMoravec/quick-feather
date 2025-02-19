<?php

namespace QuickFeather\Html\Form;

use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\Primitive\StringType;

abstract class RenderCommon {

	/**
	 * @param string $name
	 * @param string|null $suffix
	 * @return string
	 * @throws \QuickFeather\EntityManager\Error\NullError
	 */
	public static function createUniqueId(string $name, string $suffix = null): string {
		$elementId = StringType::fromVar($name,
			backSlash: BaseType::remove, slash: BaseType::remove, quote: BaseType::remove,
			whiteSpace: BaseType::remove, html: BaseType::remove, diacritic: BaseType::encode, specialChar: BaseType::remove
		);

		if ($suffix) {
			$elementId .= "_" . $suffix;
		}
		return $elementId;
	}

	/**
	 * @param array|null $cssClass
	 * @return string
	 */
	protected static function createCssClass(array|null $cssClass): string {
		if ($cssClass !== null && count($cssClass) > 0) {
			return " class=\"" . implode(' ', $cssClass) . "\" ";
		}
		return '';
	}

	/**
	 * @param array $attributes
	 * @return string
	 */
	protected static function createAttributes(array $attributes): string {
		$str = '';
		foreach ($attributes as $name => $attrValue) {
			$str .= " {$name} = \"{$attrValue}\" ";
		}
		return $str;
	}

	/**
	 * @param array $dataAttributes
	 * @return string
	 */
	protected static function createDataAttributes(array $dataAttributes): string {
		$str = '';
		foreach ($dataAttributes as $name => $dataValue) {
			$str .= " data-{$name} = \"{$dataValue}\" ";
		}
		return $str;
	}

	/**
	 * @param array $events
	 * @return string
	 */
	protected static function createEvents(array $events): string {
		$str = '';
		foreach ($events as $name => $action) {
			$str .= " {$name} = \"{$action}\" ";
		}
		return $str;
	}
}
