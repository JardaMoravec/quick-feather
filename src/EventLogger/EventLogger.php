<?php

namespace QuickFeather\EventLogger;

use Mpdf\Mpdf;
use Mpdf\MpdfException;
use QuickFeather\Current;
use QuickFeather\EntityManager\EntityManager;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\SQLError;
use QuickFeather\Routing\Linker;
use Throwable;

class EventLogger implements IEventLogger {

	public const FATAL = 'fatal';
	public const ERROR = 'error';
	public const WARNING = 'warning';
	public const INFO = 'event';
	public const ADD = 'add';
	public const EDIT = 'edit';
	public const DELETE = 'delete';
	public const LOGIN = 'login';
	private EntityManager $entityManager;
	private Current $currentUser;
	private mixed $logEventCallback;

	/**
	 * @param EntityManager $entityManager
	 * @param Current $currentUser
	 * @param callable $logEventCallback
	 */
	public function __construct(EntityManager $entityManager, Current $currentUser, callable $logEventCallback) {
		$this->entityManager = $entityManager;
		$this->currentUser = $currentUser;
		$this->logEventCallback = $logEventCallback;
	}

	/**
	 * @param string $message
	 * @param string $type
	 * @param Current|null $user
	 * @return int
	 */
	public function logEvent(string $message, string $type, Current|null $user = null): int {
		$link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		if ($user === null) {
			$user = $this->currentUser;
		}

		// get ip
		if (isset($_SERVER["REMOTE_ADDR"])) {
			$host = gethostbyname($_SERVER["REMOTE_ADDR"]) . "-" . gethostbyaddr($_SERVER["REMOTE_ADDR"]);
		} else {
			$host = "localhost";
		}

		return ($this->logEventCallback)($this->entityManager, $message, $host, $link, $type, $user);
	}

	/**
	 * @param Throwable $e
	 * @param Current|null $user
	 * @return int
	 * @throws SQLError
	 * @throws EntityError
	 */
	public function logException(Throwable $e, Current|null $user = null): int {
		return $this->logEvent($e->getMessage(), self::ERROR, $user);
	}

	/**
	 * @param Throwable $e
	 * @param Current|null $user
	 * @return void
	 * @throws SQLError
	 * @throws EntityError
	 */
	public function logFatalException(Throwable $e, Current|null $user = null): void {
		if ($user === null) {
			$user = $this->currentUser;
		}

		$id = $this->logEvent($e->getMessage(), self::FATAL, $user);

		$html = '<html lang="cz">';
		$html .= '<head><title>' . $e->getMessage() . '</title>';
		$html .= '<meta charset="utf-8">';
		$html .= '</head>';
		$html .= '<body>';
		$html .= '<p><b>Datum:</b> ' . date("Y-m-d_H-i-s") . ']</p>';
		$html .= '<p><b>Chyba:</b> ' . $e->getMessage() . '[' . $e->getFile() . ' - ' . $e->getLine() . ']</p>';
		$html .= '<p><b>Uživatel:</b> ' . ($user->isRealUser === true ? $user->id : 'nepřihlášený') . '</p>';
		$html .= '<p><b>IP:</b> ' . gethostbyname($_SERVER["REMOTE_ADDR"]) . "-" . gethostbyaddr($_SERVER["REMOTE_ADDR"]) . '</p>';
		$html .= '<p><b>Klient:</b> ' . $_SERVER['HTTP_USER_AGENT'] . '</p>';
		if ($_SERVER['HTTP_HOST'] !== '' && $_SERVER['HTTP_HOST'] !== null) {
			$link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		} else {
			$link = 'cli';
		}
		$html .= '<p><b>Odkaz:</b> ' . $link . '</p>';
		$html .= '<p><b>Soubor:</b><pre>' . $e->getTraceAsString() . '</pre></p>';
		if (Linker::isPost()) {
			$html .= '<p><b>Post:</b><pre>' . print_r($_POST, true) . '</pre></p>\';';
		}
		$html .= '</body>';
		$html .= '</html>';

		$filename = "error_" . $id;

		try {
			$mpdf = new Mpdf();
			$mpdf->title = $filename;
			$mpdf->WriteHTML($html);
			$pdf = $mpdf->Output('', 'S');

			file_put_contents("data/errors/" . $filename . ".pdf", $pdf);
		} catch (MpdfException) {

		}
	}
}
