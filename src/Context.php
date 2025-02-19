<?php

namespace QuickFeather;

use Entity\Base\Page\Page;
use Entity\Base\Page\PageFamily;
use PDO;
use QuickFeather\EntityManager\EntityManager;
use QuickFeather\EventLogger\EventLogger;
use Tool\Linker;


readonly class Context {
	public PDO $pdo;
	public EntityManager $entityManager;
	public Config $config;
	public Current $currentUser;
	public PageFamily $pageFamily;
	public Page $page;
	public Linker $link;
	public EventLogger $eventLogger;
	public string|null $sessionId;

	/**
	 * @param PDO $pdo
	 * @param Config $config
	 * @param Current $currentUser
	 * @param PageFamily $pageFamily
	 * @param Linker $link
	 */
	public function __construct(PDO $pdo, Config $config, Current $currentUser, PageFamily $pageFamily, Linker $link) {
		$this->pdo = $pdo;
		$this->entityManager = new EntityManager($pdo);
		$this->config = $config;
		$this->currentUser = $currentUser;
		$this->pageFamily = $pageFamily;
		$this->page = $this->pageFamily->getSourcePage();
		$this->link = $link;
		$this->eventLogger = new EventLogger($this);
		if (session_id() !== '' || session_id() !== false) {
			$this->sessionId = (string)session_id();
		} else {
			$this->sessionId = null;
		}
	}
}
