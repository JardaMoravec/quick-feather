<?php

namespace QuickFeather\Html;

use Dao\Cms\File\FileDao;
use Entity\Base\Page\PageId;
use Entity\Cms\Menu\MenuItem;
use Entity\Cms\Menu\MenuTree;
use QuickFeather\Context;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\SQLError;
use QuickFeather\EventLogger\EventLogger;
use QuickFeather\Routing\PermissionError;
use ReflectionException;
use Service\Base\PageService;
use Service\Cms\ArticleService;
use Service\Cms\MenuService;

class Menu {

	/**
	 * @param Context $context
	 * @param string $menuName
	 * @param array|null $cssClass
	 * @param array|string|null $ulId
	 * @return void
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws EntityError|PermissionError
	 */
	public static function dropdownMenu(Context $context, string $menuName, array $cssClass = null, array|string $ulId = null): void {
		$menu = MenuService::getTreeByMenuName($context, $menuName);

		$lastLevel = 0;

		/** @var MenuTree $item */
		foreach ($menu as $item) {
			if ($lastLevel === $item->level) {
				echo "\t\t</li>";
			}
			if ($lastLevel > $item->level) {
				echo "\t\t</li></ul></li>";
			}

			if ($lastLevel < $item->level) {
				if (is_array($cssClass['ul'])) {
					$ulCssClass = $cssClass['ul'][$item->level];
				} else {
					$ulCssClass = $cssClass['ul'];
				}
				if (is_array($ulId)) {
					$ulIdRes = $ulId['ul'][$item->level];
				} else {
					$ulIdRes = $ulId;
				}
				echo "\n<ul class='{$ulCssClass}' id='$ulIdRes'>\n";
			}

			if (is_array($cssClass['li'])) {
				$liCssClass = $cssClass['li'][$item->level];
			} else {
				$liCssClass = $cssClass['li'];
			}
			if (is_array($cssClass['a'])) {
				$aCssClass = $cssClass['a'][$item->level];
			} else {
				$aCssClass = $cssClass['a'];
			}

			// build Link
			echo "<li class='{$liCssClass}'>";
			echo "<a href=\"" . self::getLink($context, $item) . "\"	" . ($item->linkType === MenuItem::LINK_TYPE_URL ? ' target="_blank" ' : '') . " class='{$aCssClass}'>";
			if ($item->icon !== null) {
				echo "<span class=\"" . $item->icon->getValue() . "\"></span> ";
			}
			echo "<span title=\"" . $item->title . "\">" . $item->name . "</span>";
			echo "</a>";

			$lastLevel = $item->level;
		}
		echo "</li>";
		echo "</ul>";
	}

	/**
	 * @param Context $context
	 * @param string $menuName
	 * @param array|null $cssClass
	 * @param array|string|null $ulId
	 * @return void
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws EntityError|PermissionError
	 */
	public static function iconMenu(Context $context, string $menuName, array $cssClass = null, array|string $ulId = null): void {
		$menu = MenuService::getTreeByMenuName($context, $menuName);

		echo "<div class='{$cssClass['ul']}' id='{$ulId}'>";

		/** @var MenuTree $item */
		foreach ($menu as $item) {
			echo "<a href=\"" . self::getLink($context, $item) . "\"	" . ($item->linkType === MenuItem::LINK_TYPE_URL ? ' target="_blank" ' : '') . " class='{$cssClass['a']}' >";
			echo "<span title=\"" . $item->title . "\" class='{$item->icon}'></span>";
			echo "</a>";
		}
		echo "</div>";
	}

	/**
	 * @param Context $context
	 * @param string $menuName
	 * @param array|null $cssClass
	 * @return void
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws EntityError|PermissionError
	 * @todo css leveling
	 */
	public static function adminMenu(Context $context, string $menuName, array $cssClass = null): void {
		$menu = MenuService::getTreeByMenuName($context, $menuName);
		$last_level = 0;
		$lastItemId = null;

		/** @var MenuTree $item */
		foreach ($menu as $item) {
			if ($last_level === $item->level) {
				echo "\t\t</li>";
			}
			if ($last_level > $item->level) {
				echo "\t\t</li></ul></li>";
			}
			if ($last_level < $item->level) {
				$ulCssClass = $cssClass['ul'] ?? '';
				$ulId = '';
				$attr = '';

				if ($item->level > 1) {
					$ulCssClass .= " accordion-collapse collapse list-unstyled";
					$ulId = "flush-collapse-{$lastItemId}";
					$attr = "aria-labelledby=\"flush-heading-{$lastItemId}\" data-bs-parent=\"#accordionFlushMenu\"";
				}

				if ($item->level === 1) {
					$ulCssClass .= " accordion accordion-flush list-unstyled border rounded overflow-hidden";
					$ulId = "accordionFlushMenu";

				}
				echo "\n<ul class=\"{$ulCssClass}\" id='{$ulId}' {$attr} >\n";
			}

			$cssClassLi = $cssClass['li'] ?? '';
			$cssClassA = $cssClass['a'] ?? '';

			if ($item->childCount > 0) {
				$cssClassA .= " accordion-button collapsed ";
				$cssClassLi .= " accordion-item";
			}
			if ($item->level > 1) {
				$cssClassA .= "fs-6 c-dark level-" . $item->level;
				$cssClassLi .= " text-decoration-none px-3 py-2";
			} else if ($item->level === 1 && $item->childCount === 0) {
				$cssClassA .= " accordion-button collapsed no-after-image";
				$cssClassLi .= " accordion-item";
			}
			echo "<li class=\"{$cssClassLi}\">";
			$link = self::getLink($context, $item);
			if ($link) {
				echo "<div class=\"accordion-header\" id=\"flush-heading-{$item->id}\">";
				echo "<a href=\"" . self::getLink($context, $item) . "\"	" . ($item->linkType === MenuItem::LINK_TYPE_URL ? ' target="_blank" ' : '') . " class=\"{$cssClassA} text-decoration-none\"";
				if ($item->childCount > 0) {
					echo " data-bs-toggle=\"collapse\" data-bs-target=\"#flush-collapse-{$item->id}\" aria-expanded=\"false\" aria-controls=\"flush-collapse-{$item->id}\" ";
				}
				echo ">";
				if ($item->icon !== null) {
					echo "<i class=\"c-blue " . $item->icon->getValue() . "\"></i> ";
				}
				echo "<span class=\"ms-2\" title=\"" . $item->title . "\">" . $item->name . "</span>";
				echo "</a>";
				echo '</div>';
			}
			$last_level = $item->level;
			$lastItemId = $item->id;
		}
		echo "</li>";
		echo "</ul>";
	}

	/**
	 * @param Context $context
	 * @param MenuTree $item
	 * @return string|null
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws EntityError|PermissionError
	 */
	private static function getLink(Context $context, MenuTree $item): ?string {
		if ($item->linkType === MenuItem::LINK_TYPE_URL) {
			return $item->aim;
		}

		if ($item->linkType === MenuItem::LINK_TYPE_FILE) {
			if ($item->fileId > 0) {
				$page = PageService::getPageLink($context, new PageId(109), false);
				$file = FileDao::getOneById($context->pdo, $item->fileId->id);
				if ($file === null) {
					$context->eventLogger->logEvent("The menu contains not existing file reference (" . $item->fileId . ").", EventLogger::ERROR);
					return $context->config->getBaseUrl();
				}
				$page->addSeoVar($file->path);
				return $page->toString();
			}

			$context->eventLogger->logEvent("The menu contains not existing file reference (" . $item->fileId . ").", EventLogger::ERROR);
			return $context->config->getBaseUrl();
		}

		if ($item->linkType === MenuItem::LINK_TYPE_PAGE) {
			if ($item->pageId === null || $item->pageId->id === 0) {
				return '#';
			}
			return PageService::getPageLinkOrNull($context, $item->pageId)?->toString();
		}

		if ($item->linkType === MenuItem::LINK_TYPE_TEXT_PAGE) {
			if ($item->textPageId === null || $item->textPageId->id === 0) {
				return '#';
			}
			return ArticleService::getArticleLink($context, $item->textPageId)?->toString();
		}

		if ($item->linkType === MenuItem::LINK_TYPE_HELP_PAGE) {
			if ($item->textPageId === null || $item->textPageId->id === 0) {
				return '#';
			}
			return ArticleService::getArticleLink($context, $item->textPageId, 362)?->toString();
		}

		return '#';
	}
}
