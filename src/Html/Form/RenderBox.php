<?php

namespace QuickFeather\Html\Form;

use Entity\Base\AdminHelp\AdminHelp;
use Entity\Base\Page\PageId;
use QuickFeather\Html\Form\Css\FormCSSClass;


abstract class RenderBox extends RenderCommon {

	/**
	 * @param string $field
	 * @param string $identifier
	 * @param string|null $label
	 * @param string|null $elementId
	 * @param string|AdminHelp|null $description
	 * @param bool|null $require
	 * @param FormCSSClass|null $cssClass
	 * @param array|null $attributes
	 * @param array|null $dataAttributes
	 * @param array|null $events
	 * @param bool|null $labelBefore
	 * @param string|null $error
	 * @param PageId|null $pageId
	 * @param bool|null $showEmptyHelpBox
	 * @return string
	 */
	public static function renderBox(string                $field,
									 string                $identifier,
									 string|null           $label = null,
									 string|null           $elementId = null,
									 string|AdminHelp|null $description = null,
									 bool|null             $require = false,
									 FormCSSClass|null     $cssClass = null,
									 array|null            $attributes = [],
									 array|null            $dataAttributes = [],
									 array|null            $events = [],
									 bool|null             $labelBefore = true,
									 string|null           $error = null,
									 PageId|null           $pageId = null,
									 bool|null             $showEmptyHelpBox = true
	): string {
		if ($cssClass === null) {
			$cssClass = new FormCSSClass();
		}

		if ($error !== null && $error !== '') {
			$cssClass->box[] = 'error';
		}

		$str = "<div ";
		if ($require) {
			$cssClass->box[] = 'required';
		}
		$str .= self::createCssClass($cssClass->box);

		$attributes['id'] = $elementId . "_box";
		$str .= self::createAttributes($attributes);
		$str .= self::createDataAttributes($dataAttributes);
		$str .= self::createEvents($events);

		$str .= " >\n";

		if ($label !== null && $label !== '' && $labelBefore) {
			$str .= self::createLabel($label, $elementId, $error, $cssClass->label);
		}

		$str .= $field;

		if ($label !== null  && $label !== '' && !$labelBefore) {
			$str .= self::createLabel($label, $elementId, $error, $cssClass->label);
		}

		$str .= self::createHelpDescription($description, $identifier, $pageId, $showEmptyHelpBox);

		$str .= "</div>\n";

		return $str;
	}

	/**
	 * @param string $field
	 * @param string|null $elementId
	 * @param bool|null $showEye
	 * @return string
	 */
	public static function renderPasswordBox(string      $field,
											 string|null $elementId = null,
											 bool|null   $showEye = true
	): string {
		$str = '';

		if ($showEye) {
			$str .= "<div class='input-group' id='pswd_{$elementId}'>";
		}
		$str .= $field;

		if ($showEye) {
			$str .= "<div class=\"input-group-text\">\n";
			$str .= "<span class=\"fa fa-eye password-toggle\"></span>\n";
			$str .= "</div>\n";
			$str .= "</div>\n";
		}
		return $str;
	}

	/**
	 * @param string $label
	 * @param string $elementId
	 * @param string|null $error
	 * @param array|null $cssClass
	 * @return string
	 */
	private static function createLabel(string $label, string $elementId, string|null $error = null, array|null $cssClass = []): string {
		$cssClassList = implode(' ', $cssClass);
		$str = "<label for=\"{$elementId}\" class=\"$cssClassList\">";
		$str .= $label;
		if ($error !== null && $error !== '') {
			$str .= " <span class=\"badge bg-danger\">{$error}</span>";
		}
		$str .= "</label>\n";
		return $str;
	}

	/**
	 * @param string|AdminHelp|null $description
	 * @param string $identifier
	 * @param PageId|null $pageId
	 * @param bool|null $showEmptyHelpBox
	 * @return string
	 */
	public static function createHelpDescription(string|AdminHelp|null $description, string $identifier, PageId|null $pageId = null, bool|null $showEmptyHelpBox = true): string {
		$str = '';

		if ($description !== null && $description !== '') {
			if ($description instanceof AdminHelp) {
				$str .= "<span class=\"help-block-edit\" data-id=\"{$description->id}\" data-page-id=\"{$description->pageId->id}\">";
				$str .= "<span class=\"fa fa-lightbulb-o\"></span> ";
				$str .= "<div>{$description->context->getValue()}</div> ";
			} else {
				if ($pageId === null) {
					$str .= "<span>";
				} else {
					$str .= "<span class=\"help-block-edit\" data-page-id=\"{$pageId->id}\" data-relationship='$identifier'>";
					$str .= "<span class=\"fa fa-lightbulb-o\"></span> ";
				}
				$str .= "<div>{$description}</div>";
			}
			$str .= "</span>\n";
		} else if ($pageId !== null && $showEmptyHelpBox) {
			$str .= "<span class=\"help-block-edit\" data-page-id=\"{$pageId->id}\" data-relationship='$identifier'>";
			$str .= "<span class=\"fa fa-lightbulb-o\"></span> ";
			$str .= "<div>&nbsp;</div> ";
			$str .= "</span>\n";
		}
		return $str;
	}

}
