<?php

namespace QuickFeather\EventLogger;

use DateTime;
use Entity\Base\Audit\Audit;
use Entity\User\User\UserId;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use QuickFeather\Context;
use QuickFeather\Current;
use QuickFeather\EntityManager\Error\EntityError;
use QuickFeather\EntityManager\Error\SQLError;
use QuickFeather\EntityManager\Type\Complex\String\String10;
use QuickFeather\EntityManager\Type\Complex\String\String500;
use ReflectionException;
use Throwable;
use Tool\Linker;


class EventLogger implements IEventLogger {

	public const FATAL = 'fatal';
	public const ERROR = 'error';
	public const WARNING = 'warning';
	public const INFO = 'event';
	public const ADD = 'add';
	public const EDIT = 'edit';
	public const DELETE = 'delete';
	public const LOGIN = 'login';

	private Context $context;

	/**
	 * @param Context $context
	 */
	public function __construct(Context $context) {
		$this->context = $context;
	}

	/**
	 * @param string $message
	 * @param string $type
	 * @param Current|null $user
	 * @return int
	 * @throws ReflectionException
	 * @throws \QuickFeather\EntityManager\Error\SQLError
	 * @throws \QuickFeather\EntityManager\Error\EntityError
	 */
	public function logEvent(string $message, string $type, Current|null $user = null): int {
		$link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		if ($user === null) {
			$user = $this->context->currentUser;
		}

		// get ip
		if (isset($_SERVER["REMOTE_ADDR"])) {
			$host = gethostbyname($_SERVER["REMOTE_ADDR"]) . "-" . gethostbyaddr($_SERVER["REMOTE_ADDR"]);
		} else {
			$host = "localhost";
		}

		$entity = new Audit(
			0,
			new String500(substr($message, 0, 500)),
			new String500(substr($host, 0, 500)),
			new String500(substr($link, 0, 500)),
			new DateTime(),
			new String10(substr($type, 0, 10)),
			$user->isRealUser ? new UserId($user->id) : null
		);

		return $this->context->entityManager->insert($entity);
	}

	/**
	 * @param Throwable $e
	 * @param Current|null $user
	 * @return int
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws \QuickFeather\EntityManager\Error\EntityError
	 */
	public function logException(Throwable $e, Current|null $user = null): int {
		return $this->logEvent($e->getMessage(), self::ERROR, $user);
	}

	/**
	 * @param Throwable $e
	 * @param Current|null $user
	 * @return void
	 * @throws ReflectionException
	 * @throws SQLError
	 * @throws EntityError
	 */
	public function logFatalException(Throwable $e, Current|null $user = null): void {
		if ($user === null) {
			$user = $this->context->currentUser;
		}

		// db log is off when db is not connected
		$id = null;
		if ($this->context->pdo) {
			$id = $this->logEvent($e->getMessage(), self::FATAL, $user);
		}
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

		if ($id !== null) {
			$filename = "error_" . $id;
		} else {
			$filename = "error_" . date("Y-m-d_H-i-s");
		}
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
