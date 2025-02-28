<?php

namespace QuickFeather\FileManager;

use JetBrains\PhpStorm\NoReturn;
use QuickFeather\EntityManager\Error\NullError;
use QuickFeather\EntityManager\Type\BaseType;
use QuickFeather\EntityManager\Type\Primitive\StringType;
use QuickFeather\EventLogger\Message;

class FileManager {

	public const FILE_MAX_SIZE = 8388608;

	private static array $trueTypes = ["text/plain", "text/richtext", "application/pdf", "application/download",
		"application/mspowerpoint", "application/x-zip-compressed",
		"application/zip", "text/html", "multipart/x-tar", "multipart/x-zip",
		"multipart/x-gzip", "application/excel", "application/msword", "text/xml",
	];

	/**
	 * @param string $name
	 * @param string|null $baseDir
	 * @param string|null $entityDir
	 * @return string|null
	 * @throws NullError
	 */
	public static function uploadFromForm(string $name, ?string $baseDir = null, ?string $entityDir = null): ?string {
		if (is_uploaded_file($_FILES[$name]['tmp_name'])) {
			$fileName = $_FILES[$name]['name'];
			$fileName = BaseName($fileName);
			$fileName = trim($fileName);
			$info = pathinfo($fileName);
			$baseName = $info['filename'];
			$extension = $info['extension'];
			$baseName = StringType::fromVar($baseName, true, diacritic: BaseType::strip, all: BaseType::remove);
			$fileName = $baseName . '.' . $extension;
			$newPath = self::createPath($fileName, $baseDir, $entityDir);

			if ($_FILES[$name]['size'] > self::FILE_MAX_SIZE) {
				Message::add(_('Nahraný soubor je příliš veliký!'), Message::ERROR);
				return false;
			}

			if (!in_array($_FILES[$name]['type'], self::$trueTypes, true)) {
				Message::add(_('Formát souboru není podporován!'), Message::ERROR);
				return false;
			}

			if (!move_uploaded_file($_FILES[$name]['tmp_name'], $newPath)) { //copy
				Message::add(_('Soubor se nepodařilo nahrát na server!'), Message::ERROR);
				return false;
			}

			return $fileName;
		}
		return null;
	}

	/**
	 * @param string $title
	 * @param string $fileName
	 * @param string|null $baseDir
	 * @param string|null $entityDir
	 * @return void
	 */
	#[NoReturn]
	public static function showFileContent(string $title, string $fileName, ?string $baseDir = null, ?string $entityDir = null): void {
		$path = self::createPath($fileName, $baseDir, $entityDir);

		if (file_exists($path)) {
			//set force download headers
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . $title . '"');
			header('Content-Transfer-Encoding: binary');
			header('Connection: Keep-Alive');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . sprintf("%u", filesize($path)));

			//open and output file contents
			$fh = fopen($path, "rb");
			while (!feof($fh)) {
				echo fgets($fh);
				flush();
			}
			fclose($fh);
			exit;
		}

		//LogEventService::logEvent("File with id " . $file['id'] . " does not existing!", \QuickFeather\EventLogger\EventLogger::ERROR);
		header("HTTP/1.0 404 Not Found");
		exit('File not found!');
	}

	/**
	 * @param string $fileName
	 * @param string|null $baseDir
	 * @param string|null $entityDir
	 * @return bool
	 */
	public static function delete(string $fileName, ?string $baseDir = null, ?string $entityDir = null): bool {
		$path = self::createPath($fileName, $baseDir, $entityDir);
		return unlink($path);
	}

	/**
	 * @param string $fileName
	 * @param string|null $baseDir
	 * @param string|null $entityDir
	 * @return int
	 */
	public static function getSize(string $fileName, ?string $baseDir = null, ?string $entityDir = null): int {
		$path = self::createPath($fileName, $baseDir, $entityDir);
		return filesize($path);
	}

	/**
	 * @param string $fileName
	 * @param string|null $baseDir
	 * @param string|null $entityDir
	 * @return string
	 */
	public static function createPath(string $fileName, ?string $baseDir = null, ?string $entityDir = null): string {
		$path = '';
		if ($baseDir !== null && $baseDir !== '') {
			$path .= strrpos($baseDir, '/', -1) !== false ? $baseDir : '/' . $baseDir;
		}
		if ($entityDir !== null && $entityDir !== '') {
			$path .= strrpos($entityDir, '/', -1) !== false ? $entityDir : '/' . $entityDir;
		}

		return $path . $fileName;
	}
}
