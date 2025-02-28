<?php

namespace QuickFeather;

use Dao\Base\Settings\SettingsDao;
use Dao\Cms\Gallery\SizeSettingDao;
use Entity\Cms\Gallery\SizeSetting;
use PDO;
use QuickFeather\EntityManager\db;
use QuickFeather\EntityManager\Error\SQLError;
use ReflectionException;
use Entity\Base\Settings;
use Service\Base\SettingsService;

class Config {

	private string $baseUrl;
	private string $baseDir;
	private mixed $template;
	private string $templateBaseDir;
	private string $staticBaseUrl;
	private string $staticBaseDir;
	private string $staticUrl;
	private string $staticDir;
	private array $imageSizes = [];
	private null|Settings\Settings $settings;
	private string $author;
	private bool $testMode;
	private bool $developerMode;
	private int $cssCompileVersion;
	private float $apiVersion;
	private string $watermark;
	private int $systemTempUserId;
	private array $mailer;
	private string $croneSecureHash;
	private array $pays;
	private string $developerEmail;
	private array $mediaApi;

	/**
	 * @param PDO $pdo
	 * @param array $configArray
	 * @param bool $isBackend
	 * @param bool $withDatabaseConfig
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	public function __construct(PDO $pdo, array $configArray, bool $isBackend, bool $withDatabaseConfig = true) {

		$this->template = $configArray['template'];
		$this->author = $configArray['author'];
		$this->developerMode = $configArray['developer_mode'];
		$this->testMode = $configArray['test_mode'];
		$this->cssCompileVersion = $configArray['css_compile_version'];
		$this->apiVersion = $configArray['api_version'];
		$this->watermark = $configArray['watermark'];
		$this->systemTempUserId = $configArray['system_temp_user_id'];
		$this->croneSecureHash = $configArray['crone_secure_hash'];
		$this->mailer = $configArray['mailer'];
		$this->pays = $configArray['pays'];
		$this->developerEmail = $configArray['developer_email'];
		$this->mediaApi = $configArray['media-api'];

		if (PHP_SAPI === "cli") {
			$this->baseUrl = 'localhost';
			$this->baseDir = $_SERVER['PWD'] . '/';
		} else {
			if (str_contains($_SERVER['HTTP_HOST'], '.devel')) {
				$this->baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/';
			} else {
				$this->baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/';
			}
			$this->baseDir = $_SERVER['DOCUMENT_ROOT'] . '/';
		}

		$this->loadPathConfig($isBackend);

		if ($withDatabaseConfig) {
			$this->loadImageSizes($pdo);
			$this->loadConfigFromDatabase($pdo);
		}
	}

	/**
	 * @param bool $isBackend
	 * @return void
	 */
	public function loadPathConfig(bool $isBackend): void {
		if ($isBackend) {
			$this->templateBaseDir = $this->baseDir . 'src/Templates/admin/';
			$this->staticBaseUrl = $this->baseUrl . 'static/admin/';
			$this->staticBaseDir = $this->baseDir . 'static/admin/';
		} else {
			$this->templateBaseDir = $this->baseDir . 'src/Templates/' . $this->template . '/';
			$this->staticBaseUrl = $this->baseUrl . 'static/' . $this->template . '/';
			$this->staticBaseDir = $this->baseDir . 'static/' . $this->template . '/';
		}
		$this->staticUrl = $this->baseUrl . 'static/';
		$this->staticDir = $this->baseDir . 'static/';
	}

	/**
	 * @param PDO $pdo
	 * @return void
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	private function loadImageSizes(PDO $pdo): void {
		$imageSizes = SizeSettingDao::getList($pdo,
			where: db::is(SizeSetting::active, true),
			orderBy: db::asc(SizeSetting::id)
		);

		foreach ($imageSizes as $imageSize) {
			$this->imageSizes[$imageSize->id] = $imageSize;
		}
	}

	/**
	 * @param int $sizeId
	 * @return SizeSetting
	 */
	public function getImageSize(int $sizeId): SizeSetting {
		return $this->imageSizes[$sizeId];
	}

	/**
	 * @param PDO $pdo
	 * @return void
	 * @throws ReflectionException
	 * @throws SQLError
	 */
	private function loadConfigFromDatabase(PDO $pdo): void {
		$this->settings = SettingsDao::getOneById($pdo, SettingsService::DEFAULT_SETTING_ID);
	}

	/**
	 * @return string
	 */
	public function getBaseUrl(): string {
		return $this->baseUrl;
	}

	/**
	 * @return string
	 */
	public function getBaseDir(): string {
		return $this->baseDir;
	}

	/**
	 * @return mixed
	 */
	public function getTemplate(): mixed {
		return $this->template;
	}

	/**
	 * @return string
	 */
	public function getTemplateBaseDir(): string {
		return $this->templateBaseDir;
	}

	/**
	 * @return string
	 */
	public function getStaticBaseUrl(): string {
		return $this->staticBaseUrl;
	}

	/**
	 * @return string
	 */
	public function getStaticBaseDir(): string {
		return $this->staticBaseDir;
	}

	/**
	 * @return array
	 */
	public function getImageSizes(): array {
		return $this->imageSizes;
	}

	/**
	 * @return Settings\Settings|null
	 */
	public function getSettings(): ?Settings\Settings {
		return $this->settings;
	}

	/**
	 * @return string
	 */
	public function getAuthor(): string {
		return $this->author;
	}

	/**
	 * @return bool
	 */
	public function isTestMode(): bool {
		return $this->testMode;
	}

	/**
	 * @return bool
	 */
	public function isDeveloperMode(): bool {
		return $this->developerMode;
	}

	/**
	 * @return int
	 */
	public function getCssCompileVersion(): int {
		return $this->cssCompileVersion;
	}

	/**
	 * @return float
	 */
	public function getApiVersion(): float {
		return $this->apiVersion;
	}

	/**
	 * @return string
	 */
	public function getWatermark(): string {
		return $this->watermark;
	}

	/**
	 * @return int
	 */
	public function getSystemTempUserId(): int {
		return $this->systemTempUserId;
	}

	/**
	 * @return array
	 */
	public function getMailer(): array {
		return $this->mailer;
	}

	/**
	 * @return string
	 */
	public function getCroneSecureHash(): string {
		return $this->croneSecureHash;
	}

	/**
	 * @return array
	 */
	public function getPays(): array {
		return $this->pays;
	}

	/**
	 * @return string
	 */
	public function getDeveloperEmail(): string {
		return $this->developerEmail;
	}

	/**
	 * @return string
	 */
	public function getStaticUrl(): string {
		return $this->staticUrl;
	}

	/**
	 * @return string
	 */
	public function getStaticDir(): string {
		return $this->staticDir;
	}

	/**
	 * @return array
	 */
	public function getMediaApi(): array {
		return $this->mediaApi;
	}
}
