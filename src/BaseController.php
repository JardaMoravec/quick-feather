<?php

namespace QuickFeather;

use Dao\Base\AdminHelp\AdminHelpDao;
use Entity\Base\AdminHelp\AdminHelp;
use Exception;
use PDO;
use QuickFeather\EntityManager\db;
use QuickFeather\EntityManager\Error\SQLError;
use ReflectionException;
use Service\System\MinifyCreatorService;
use Service\System\RouteService;


abstract class BaseController {

	/** @deprecated */
	public const FORM_ADD = 0;
	/** @deprecated */
	public const FORM_EDIT = 1;

	protected string|null $templatePath = null;
	protected Context $context;
	protected PDO $pdo;
	protected array $helpText;

	/**
	 * @param \QuickFeather\Context $context
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	public function __construct(Context $context) {
		$this->context = $context;
		$this->pdo = $context->pdo;

		$this->helpText = AdminHelpDao::getList($this->pdo,
			where: db::and(
				db::is(AdminHelp::pageId, $this->context->pageFamily->getSourcePage()->id),
			),
			orderBy: db::asc(AdminHelp::order)
		) ?? [];
	}

	/**
	 * @throws Exception
	 */
	public function renderTemplate(): void {
		if ($this->templatePath !== null && $this->templatePath !== '') {
			$controller = $this;
			include $this->context->config->getTemplateBaseDir() . $this->templatePath . '.tpl.php';
		}
	}

	/**
	 * @param string $sourcePath
	 * @return string
	 */
	public function getCssFilePath(string $sourcePath): string {
		return MinifyCreatorService::getCssFilePath(
			$this->context->config->getStaticBaseUrl(),
			$this->context->config->getStaticBaseDir(),
			$sourcePath,
			$this->context->config->getCssCompileVersion(),
			$this->context->config->isDeveloperMode()
		);
	}

	/**
	 * @param string $sourcePath
	 * @return string
	 */
	public function getJsFilePath(string $sourcePath): string {
		return MinifyCreatorService::getJsFilePath(
			$this->context->config->getStaticBaseUrl(),
			$this->context->config->getStaticBaseDir(),
			$sourcePath,
			$this->context->config->getCssCompileVersion(),
			$this->context->config->isDeveloperMode()
		);
	}

	/**
	 * @return string[]
	 */
	public function getMonthList(): array {
		return [1 => "leden",
			2 => "únor",
			3 => "březen",
			4 => "duben",
			5 => "květen",
			6 => "červen",
			7 => "červenec",
			8 => "srpen",
			9 => "září",
			10 => "říjen",
			11 => "listopad",
			12 => "prosinec",
		];
	}

	/**
	 * @return \QuickFeather\Config
	 */
	public function getConfig(): Config {
		return $this->context->config;
	}

	/**
	 * @return Context
	 */
	public function getContext(): Context {
		return $this->context;
	}

	/**
	 * @return array
	 */
	public function getHelpText(): array {
		return $this->helpText;
	}

	/**
	 * @param int $order
	 * @return AdminHelp|null
	 */
	public function getHelpTextByOrder(int $order): AdminHelp|null {
		return $this->helpText[$order];
	}

	/**
	 * @return void
	 * @throws \QuickFeather\NotFoundError
	 * @throws ReflectionException
	 */
	public function insertContent(): void {
		$this->context->pageFamily->next();

		if ($this->context->pageFamily->valid()) {
			RouteService::loadPageContent($this->context);
		}
	}
}
