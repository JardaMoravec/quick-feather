<?php

namespace QuickFeather\EventLogger;

use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Error\TypeError;


class Message {

	public const ERROR = 'error';
	public const INFO = 'event';
	public const WARNING = 'warning';

	/**
	 * @return bool
	 */
	public static function exist(): bool {
		return isset($_SESSION["message"]);
	}

	/**
	 * @return bool
	 */
	public static function existErrors(): bool {
		return isset($_SESSION["message"]['error']);
	}

	/**
	 * zprava - výsledný status události
	 * @param string $text
	 * @param string $type
	 */
	public static function add(string $text, string $type): void {
		$_SESSION["message"][$type][] = $text;
	}

	/**
	 * @param string|null $cssClass
	 */
	public static function render(?string $cssClass = ''): void {
		if (isset($_SESSION["message"]) && count($_SESSION["message"]) > 0) {
			echo "<div class=\"toast {$cssClass}\" role=\"alert\" aria-live=\"assertive\" aria-atomic=\"true\">";
			foreach ($_SESSION["message"] as $type => $messages) {
				$label = "";
				if ($type === self::INFO) {
					$type = "bg-success";
					$label = "Úspěch";
				} else if ($type === self::ERROR) {
					$type = "bg-danger";
					$label = "Chyba";
				} else if ($type === self::WARNING) {
					$type = "bg-warning";
					$label = "Upozornění";
				}

				echo '<div class="toast-header ' . $type . ' text-white">';
				echo '<strong class="me-auto">' . $label . '</strong>';
				echo '<button type="button" class="btn-close text-white" data-bs-dismiss="toast" aria-label="Zavřít"></button>';
				echo '</div>';
				echo '<div class="toast-body">';
				foreach ($messages as $message) {
					echo "<p>" . $message . "</p>";
				}
				echo '</div>';
			}
			echo '</div>';
		}
		unset($_SESSION["message"]);
	}

	/**
	 * clean all messages
	 */
	public static function clear(): void {
		unset($_SESSION["message"]);
	}

	/**
	 * @return array|null
	 */
	public static function getMessages(): ?array {
		return $_SESSION["message"];
	}

	/**
	 * @param callable $event
	 * @param string|null $messagePrefix
	 * @param string|null $messageSuffix
	 * @return mixed
	 */
	public static function errorToMessage(callable $event, ?string $messagePrefix = null, ?string $messageSuffix = null): mixed {
		try {
			return $event();
		} catch (NullError|TypeError $error) {
			self::add($messagePrefix . $error->getMessage() . $messageSuffix, self::ERROR);
		}
		return null;
	}
}
