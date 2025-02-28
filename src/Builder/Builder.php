<?php

namespace QuickFeather\Builder;

use Dao\Base\ChangeScript\ChangeScriptDao;
use Dao\Base\Page\PageDao;
use Dao\Base\TableInfo\TableInfoDao;
use DateTime;
use Entity\Base\ChangeScript\ChangeScript;
use Entity\Base\Page\Page;
use Entity\Base\Page\PageId;
use Entity\Base\TableInfo\TableInfo;
use InvalidArgumentException;
use PDO;
use PDOException;
use QuickFeather\Config;
use QuickFeather\EntityManager\db;
use QuickFeather\EntityManager\IEntity;
use QuickFeather\EntityManager\Type\Complex\String\String100;
use QuickFeather\EntityManager\Type\Complex\String\String300;
use QuickFeather\EntityManager\Type\Complex\String\String5;
use QuickFeather\EntityManager\Type\Complex\String\String500;
use QuickFeather\EntityManager\Type\Primitive\StringType;
use QuickFeather\EventLogger\Message;
use ReflectionClass;
use ReflectionException;
use RuntimeException;


class Builder {

	/**
	 * @param PDO $pdo
	 * @param string $label
	 * @param string $namespace
	 * @param string $daoName
	 * @param string $tableName
	 * @param Config $config
	 * @return void
	 * @throws ReflectionException
	 */
	public static function createAdminControllers(PDO $pdo, string $label, string $namespace, string $daoName, string $tableName, Config $config): void {
		$controllers = [
			"Base", "List", "Add", "Edit", "Delete",
		];

		$baseFolderName = StringType::getStringBefore(self::classNameToFileName($namespace, false), '/');

		if (!file_exists($baseFolderName)) {
			if (!mkdir($baseFolderName) && !is_dir($baseFolderName)) {
				throw new RuntimeException(sprintf('Directory "%s" was not created', $baseFolderName));
			}
			chmod($baseFolderName, 0777);
			Message::add("Created folder:" . $baseFolderName, Message::INFO);
		}
		$baseFolderName .= "Admin/";
		if (!file_exists($baseFolderName)) {
			if (!mkdir($baseFolderName) && !is_dir($baseFolderName)) {
				throw new RuntimeException(sprintf('Directory "%s" was not created', $baseFolderName));
			}
			chmod($baseFolderName, 0777);
			Message::add("Created folder:" . $baseFolderName, Message::INFO);
		}

		$path = explode('/', $baseFolderName);
		array_pop($path);
		$moduleName = $path[count($path) - 2];

		[$package, $table] = explode('.', $tableName);
		//$columns = TableInfoDao::getDbTableInfo($pdo, $table, $package);
		$columns = TableInfoDao::getList($pdo,
			where: db::and(db::is(TableInfo::tableName, $table, true), db::is(TableInfo::schemaName, $package, true)),
			orderBy: "CASE " . TableInfo::columnName . " WHEN 'id' THEN 1 ELSE 2 END, is_nullable DESC, " . TableInfo::columnName . " ASC"
		);

		$listPageId = null;
		$addPageId = null;
		$editPageId = null;
		$deletePageId = null;

		try {
			$pdo->beginTransaction();

			$listPageId = self::createPageForController($pdo, $moduleName, $label, 'list', 'seznam');
			$addPageId = self::createPageForController($pdo, $moduleName, $label, 'add', 'nový');
			$editPageId = self::createPageForController($pdo, $moduleName, $label, 'edit', 'editace');
			$deletePageId = self::createPageForController($pdo, $moduleName, $label, 'delete', 'smazání');

			$pdo->commit();
		} catch (PDOException $exception) {
			$pdo->rollBack();
			Message::add("Stránky se nepodařilo vytvořit. Možná již existují!", Message::ERROR);
			Message::add($exception->getMessage(), Message::ERROR);
		}

		$inList = [];
		$inForm = [];

		/** @var TableInfo $column */
		foreach ($columns as $column) {

			if ($column->isNullable === true) {
				$require = ', require: true';
			} else {
				$require = '';
			}

			$columnName = StringType::convert2camelcase($column->columnName, capitalizeFirstCharacter: false);

			if (str_contains($column->columnName, '_id')) {
				$inForm[] = "\t\t\${$columnName} = new SelectField(\"{$columnName}\", _('{$columnName}'){$require});\n" .
					"\t\t\$basicBox->addElement(\${$columnName});";
				$inList[] = "\t\t\tTable::createColumn('{$columnName}', label: _('{$columnName}'))";
			} else if ($column->dataType === "int4" && $column->columnName !== "id") {
				$inForm[] = "\t\t\${$columnName} = new NumberField(\"{$columnName}\", _('{$columnName}'){$require});\n" .
					"\t\t\$basicBox->addElement(\${$columnName});";
				$inList[] = "\t\t\tTable::createColumn('{$columnName}', Column::\$renderNumber, label: _('{$columnName}'))";
			} else if ($column->dataType === "varchar" && $column->dimension > 0 && $column->columnName !== "id") {
				$inForm[] = "\t\t\${$columnName} = new InputField(\"{$columnName}\", _('{$columnName}'), languageId: \$this->context->languageId, length: {$column->size}{$require});\n" .
					"\t\t\$basicBox->addElement(\${$columnName});";
				$inList[] = "\t\t\tTable::createColumn('{$columnName}', label: _('{$columnName}'), languageId: \$this->context->getLanguageId)";
			} else if ($column->dataType === "varchar" && $column->columnName !== "id") {
				$inForm[] = "\t\t\${$columnName} = new InputField(\"{$columnName}\", _('{$columnName}'), length: {$column->size}{$require});\n" .
					"\t\t\$basicBox->addElement(\${$columnName});";
				$inList[] = "\t\t\tTable::createColumn('{$columnName}', label: _('{$columnName}'))";
			} else if ($column->dataType === "bool" && $column->columnName !== "id") {
				$inForm[] = "\t\t\${$columnName} = new CheckBoxField(\"{$columnName}\", _('{$columnName}'), labelBefore: false{$require});\n" .
					"\t\t\$basicBox->addElement(\${$columnName});";
				$inList[] = "\t\t\tTable::createColumn('{$columnName}',  Column::\$renderBool, label: _('{$columnName}'))";
			} else if ($column->dataType === "numeric" && $column->columnName !== "id") {
				$inForm[] = "\t\t\${$columnName} = new NumberField(\"{$columnName}\", _('{$columnName})'{$require});\n" .
					"\t\t\$basicBox->addElement(\${$columnName});";
				$inList[] = "\t\t\tTable::createColumn('{$columnName}', Column::\$renderNumber, label: _('{$columnName}'))";
			} else if ($column->dataType === "float8" && $column->columnName !== "id") {
				$inForm[] = "\t\t\${$columnName} = new NumberField(\"{$columnName}\", _('{$columnName})'{$require});\n" .
					"\t\t\$basicBox->addElement(\${$columnName});";
				$inList[] = "\t\t\tTable::createColumn('{$columnName}', Column::\$renderNumber, label: _('{$columnName}'))";
			} else if ($column->dataType === "timestamp" && $column->columnName !== "id") {
				$inForm[] = "\t\t\${$columnName} = new DateTimeField(\"{$columnName}\", _('{$columnName}'), length: {$column->size}{$require});\n" .
					"\t\t\$basicBox->addElement(\${$columnName});";
				$inList[] = "\t\t\tTable::createColumn('{$columnName}', Column::\$renderDateTime, label: _('{$columnName}'))";
			} else if ($column->dataType === "date" && $column->columnName !== "id") {
				$inForm[] = "\t\t\${$columnName} = new DateField(\"{$columnName}\", _('{$columnName}'), length: {$column->size}{$require});\n" .
					"\t\t\$basicBox->addElement(\${$columnName});";
				$inList[] = "\t\t\tTable::createColumn('{$columnName}', Column::\$renderDate, label: _('{$columnName}'))";
			} else if ($column->dataType === "json" && $column->columnName !== "id") {
				$inForm[] = "\t\t\${$columnName} = new InputField(\"{$columnName}\", _('{$columnName}'), length: {$column->size}{$require});\n" .
					"\t\t\$basicBox->addElement(\${$columnName});";
				$inList[] = "\t\t\tTable::createColumn('{$columnName}', label: _('{$columnName}'))";
			} else if ($column->columnName !== "id") {
				$inForm[] = "\t\t\${$columnName} = new InputField(\"{$columnName}\", _('{$columnName}'), length: {$column->size}{$require});\n" .
					"\t\t\$basicBox->addElement(\${$columnName});";
				$inList[] = "\t\t\tTable::createColumn('{$columnName}', label: _('{$columnName}'))";

				Message::add('undefined column type: ' . $column->dataType, Message::ERROR);
			}
		}

		$definition = [
			'RmamespaceR',
			'Rclase_nameR',
			'Rdao_classR',
			'Rservice_classR',
			'R_module_name_R',
			'R_label_R',
			'Rlist_pageR',
			'Redit_pageR',
			'Radd_pageR',
			'Rdelete_pageR',
			'//R_form_fields_R',
			'//R_list_R',
		];
		$final = [
			$namespace . "Admin",
			"BaseController",
			$daoName,
			str_replace('Dao', 'Service', $daoName),
			$moduleName,
			$label,
			$listPageId,
			$editPageId,
			$addPageId,
			$deletePageId,
			implode("\n\n", $inForm),
			implode(",\n", $inList),
		];

		foreach ($controllers as $controllerPrefix) {
			$fileName = $baseFolderName . $controllerPrefix . "Controller.php";
			Message::add("File:" . $fileName, Message::INFO);
			$file = FOpen($fileName, "w");

			if (!$fileName) {
				Message::add("ERROR: file was not created!", Message::ERROR);
				return;
			}
			chmod($fileName, 0777);

			$final[1] = $controllerPrefix . "Controller";

			$content = file_get_contents($config->getBaseDir() . "src/Tool/BuildTemplates/admin/{$controllerPrefix}Controller.php");
			$content = str_replace($definition, $final, $content);

			FWrite($file, $content);
			FClose($file);

			Message::add("CONTROLLER READY: {$fileName}", Message::INFO);
		}
	}

	/**
	 * @param $pdo
	 * @param string $controllerName
	 * @param string $moduleLabel
	 * @param string $type
	 * @param string $label
	 * @return int
	 * @throws ReflectionException
	 */
	public static function createPageForController($pdo, string $controllerName, string $moduleLabel, string $type, string $label): int {
		$page = new Page(0,
			true,
			new String100($moduleLabel . ' - ' . $label),
			new String5('HTML'),
			new String100(str_replace('Controller\\', '', $controllerName) . '::' . $type),
			false,
			new String500($moduleLabel . ' -  ' . $label),
			null,
			new String100($moduleLabel . ' - ' . $label),
			new PageId(4),
			new String100('admin4web/' . strtolower($controllerName) . '/' . $type),
			true
		);
		return PageDao::insert($pdo, $page);
	}

	/**
	 * @param PDO $pdo
	 * @param string $className
	 * @param string $tableName
	 * @param Config $config
	 * @return void
	 * @throws ReflectionException
	 */
	public static function createEntityFile(PDO $pdo, string $className, string $tableName, Config $config): void {
		$fileName = self::classNameToFileName($className);
		Message::add("File:" . $fileName, Message::INFO);

		$file = FOpen($fileName, "w");

		if (!$file) {
			Message::add("ERROR: file was not created!", Message::ERROR);
			return;
		}
		chmod($fileName, 0777);

		$path = explode('\\', $className);

		$className = $path[count($path) - 1];
		array_pop($path);

		$namespace = implode('\\', $path);

		[$package, $table] = explode('.', $tableName);

		$columns = TableInfoDao::getList($pdo,
			where: db::and(db::is(TableInfo::tableName, $table, true), db::is(TableInfo::schemaName, $package, true)),
			orderBy: "CASE " . TableInfo::columnName . " WHEN 'id' THEN 1 ELSE 2 END, is_nullable DESC, " . TableInfo::columnName . " ASC"
		);

		$columnList = [];
		$construct = [];
		$constants = [];
		$params = [];

		$constants[] = "\n\tconst source = '{$tableName}';\n\n";

		/** @var TableInfo $column */
		foreach ($columns as $column) {

			$columnName = StringType::convert2camelcase($column->columnName, capitalizeFirstCharacter: false);

			if (str_contains($column->columnName, '_id')) {
				$type = StringType::convert2camelcase($column->columnName);
				$columnList[] = "\t * @param " . ($column->isNullable === true ? '?' : '') . "{$type} \${$columnName}";
				$params[] = "\t\t\tpublic " . ($column->isNullable === true ? '?' : '') . "{$type} \${$columnName}" . ($column->isNullable === true ? ' = null' : '');
			} else if ($column->dataType === "int4" && $column->columnName !== "id") {
				$columnList[] = "\t * @param " . ($column->isNullable === true ? '?' : '') . "int \${$columnName}";
				$params[] = "\t\t\tpublic " . ($column->isNullable === true ? '?' : '') . "int \${$columnName}" . ($column->isNullable === true ? ' = null' : '');
			} else if ($column->dataType === "varchar" && $column->dimension > 0 && $column->columnName !== "id") {
				$columnList[] = "\t * @param " . ($column->isNullable === true ? '?' : '') . "PgArray \${$columnName}";
				$params[] = "\t\t\tpublic " . ($column->isNullable === true ? '?' : '') . "PgArray \${$columnName}" . ($column->isNullable === true ? ' = null' : '');
			} else if ($column->dataType === "varchar" && $column->columnName !== "id") {
				$type = $column->size > 0 ? 'String' . $column->size : 'string';
				$columnList[] = "\t * @param " . ($column->isNullable === true ? '?' : '') . "{$type} \${$columnName}";
				$params[] = "\t\t\tpublic " . ($column->isNullable === true ? '?' : '') . "{$type} \${$columnName}" . ($column->isNullable === true ? ' = null' : '');
			} else if ($column->dataType === "bool" && $column->columnName !== "id") {
				$columnList[] = "\t * @param " . ($column->isNullable === true ? '?' : '') . "bool \${$columnName}";
				$params[] = "\t\t\tpublic " . ($column->isNullable === true ? '?' : '') . "bool \${$columnName}" . ($column->isNullable === true ? ' = null' : '');
			} else if ($column->dataType === "numeric" && $column->columnName !== "id") {
				$columnList[] = "\t * @param " . ($column->isNullable === true ? '?' : '') . "float \${$columnName}";
				$params[] = "\t\t\tpublic " . ($column->isNullable === true ? '?' : '') . "float \${$columnName}" . ($column->isNullable === true ? ' = null' : '');
			} else if ($column->dataType === "float8" && $column->columnName !== "id") {
				$columnList[] = "\t * @param " . ($column->isNullable === true ? '?' : '') . "float \${$columnName}";
				$params[] = "\t\t\tpublic " . ($column->isNullable === true ? '?' : '') . "float \${$columnName}" . ($column->isNullable === true ? ' = null' : '');
			} else if ($column->dataType === "timestamp" && $column->columnName !== "id") {
				$columnList[] = "\t * @param " . ($column->isNullable === true ? '?' : '') . "\\DateTime \${$columnName}";
				$params[] = "\t\t\tpublic " . ($column->isNullable === true ? '?' : '') . "\\DateTime \${$columnName}" . ($column->isNullable === true ? ' = null' : '');
			} else if ($column->dataType === "date" && $column->columnName !== "id") {
				$columnList[] = "\t * @param " . ($column->isNullable === true ? '?' : '') . "\\DateTime \${$columnName}";
				$params[] = "\t\t\tpublic " . ($column->isNullable === true ? '?' : '') . "\\DateTime \${$columnName}" . ($column->isNullable === true ? ' = null' : '');
			} else if ($column->dataType === "json" && $column->columnName !== "id") {
				$columnList[] = "\t * @param " . ($column->isNullable === true ? '?' : '') . "string \${$columnName}";
				$params[] = "\t\t\tpublic " . ($column->isNullable === true ? '?' : '') . "string \${$columnName}" . ($column->isNullable === true ? ' = null' : '');
			} else if ($column->columnName !== "id") {
				$columnList[] = "\t * @param mixed \${$columnName}";
				$params[] = "\t\t\tpublic mixed \${$columnName}" . ($column->isNullable === true ? ' = null' : '');
				Message::add('undefined column type: ' . $column->dataType, Message::ERROR);
			}

			$constants[] = "\tconst {$columnName} = '{$table}.{$column->columnName}';\n";

			if (isset($column->possibleValue) && is_array($column->possibleValue) && count($column->possibleValue) > 0) {
				$str = "\n";
				foreach (array_unique($column->possibleValue) as $value) {
					$str .= "\tconst " . strtoupper($column->dataType) . '_' . strtoupper(str_replace(' ', '_', $value)) . " = '" . $value . "';\n";
				}
				$constants[] = $str;
			}
			if ($column->columnName !== "id") {
				$construct[] = "\t\t\$this->{$columnName} = \${$columnName};\n";
			}
		}

		$content = file_get_contents($config->getBaseDir() . "src/Tool/BuildTemplates/createEntity.php");

		$definition = [
			'RmamespaceR',
			'Rclase_nameR',
			'RcolumnsR',
			'/*RconstructR*/',
			'/*RconstsR*/',
			'/*RparamsR*/',
		];
		$final = [
			$namespace,
			$className,
			implode("\n", $columnList),
			implode("\n", $construct),
			implode('', $constants),
			implode(",\n", $params),
		];

		$content = str_replace($definition, $final, $content);

		FWrite($file, $content);
		FClose($file);

		Message::add("ENTITY READY: " . $className, Message::INFO);
	}

	/**
	 * @param string $className
	 * @param Config $config
	 * @return void
	 */
	public static function createEntityIdFile(string $className, Config $config): void {
		$fileName = self::classNameToFileName($className . "Id");

		$folderName = StringType::getStringBefore($fileName, '/');
		if (!file_exists($folderName)) {
			if (!mkdir($folderName) && !is_dir($folderName)) {
				throw new RuntimeException(sprintf('Directory "%s" was not created', $folderName));
			}
			chmod($folderName, 0777);
			Message::add("Created folder:" . $folderName, Message::INFO);
		}

		Message::add("File:" . $fileName, Message::INFO);
		$file = FOpen($fileName, "w");

		if (!$file) {
			Message::add("ERROR: file was not created!", Message::ERROR);
			return;
		}
		chmod($fileName, 0777);

		$path = explode('\\', $className);

		$className = $path[count($path) - 1];
		array_pop($path);

		$namespace = implode('\\', $path);

		$content = file_get_contents($config->getBaseDir() . "src/Tool/BuildTemplates/createEntityId.php");

		$definition = [
			'RmamespaceR',
			'Rclase_nameR',
		];
		$final = [
			$namespace,
			$className,
		];

		$content = str_replace($definition, $final, $content);

		FWrite($file, $content);
		FClose($file);

		Message::add("ENTITY READY: " . $className . "Id", Message::INFO);
	}

	/**
	 * @param PDO $pdo
	 * @param string $className
	 * @param string $entity
	 * @param Config $config
	 * @return void
	 * @throws ReflectionException
	 */
	public static function createDaoFile(PDO $pdo, string $className, string $entity, Config $config): void {
		$fileName = self::classNameToFileName($className);

		$folderName = StringType::getStringBefore($fileName, '/');
		if (!file_exists($folderName)) {
			if (!mkdir($folderName) && !is_dir($folderName)) {
				throw new RuntimeException(sprintf('Directory "%s" was not created', $folderName));
			}
			chmod($folderName, 0777);
			Message::add("Created folder:" . $folderName, Message::INFO);
		}

		Message::add("File:" . $fileName, Message::INFO);
		$file = FOpen($fileName, "w");

		if (!$file) {
			Message::add("ERROR: file was not created!", Message::ERROR);
			return;
		}
		chmod($fileName, 0777);

		$path = explode('\\', $className);

		$className = $path[count($path) - 1];
		array_pop($path);

		$namespace = implode('\\', $path);

		[$package, $table] = explode('.', ($entity)::source);

		$columns = TableInfoDao::getList($pdo,
			where: db::and(db::is(TableInfo::tableName, $table, true), db::is(TableInfo::schemaName, $package, true)),
			orderBy: "CASE " . TableInfo::columnName . " WHEN 'id' THEN 1 ELSE 2 END, is_nullable DESC, " . TableInfo::columnName . " ASC"
		);

		$entityName = StringType::getStringAfter($entity, '\\');

		$fromArrayParam = [];

		/** @var TableInfo $column */
		foreach ($columns as $column) {
			$columnName = StringType::convert2camelcase($column->columnName, capitalizeFirstCharacter: false);
			$fromArrayParam[] = "\t\t\t\$data[{$entityName}::{$columnName}]";
		}

		$content = file_get_contents($config->getBaseDir() . "src/Tool/BuildTemplates/createDao.php");

		$definition = [
			'RmamespaceR',
			'Rclase_nameR',
			'RentityNamespaceR',
			'RentityR',
			'/*FROM_ARRAY_PARAM*/',
		];
		$final = [
			$namespace,
			$className,
			$entity,
			$entityName,
			implode(",\n ", $fromArrayParam),
		];

		$content = str_replace($definition, $final, $content);

		FWrite($file, $content);
		FClose($file);

		Message::add("MAPPER READY: " . $className . "Mapper", Message::INFO);
	}

	/**
	 * @param string $className
	 * @param int $maxLength
	 * @param bool $addDescription
	 * @param Config $config
	 * @param string|null $templateName
	 * @return string
	 * @throws ReflectionException
	 */
	public static function createForm(string $className, int $maxLength, bool $addDescription, Config $config, ?string $templateName = null): string {

		if (!is_subclass_of($className, IEntity::class)) {
			throw new InvalidArgumentException("Třída $className musí implementovat rozhraní IEntity.");
		}

		$columns = $className::getProperties();
		$entity = $className::getEntity();

		$str = "<?php /** @noinspection PhpUnhandledExceptionInspection */\n";
		$str .= "use QuickFeather\Html\Form\FormEntity;\n";
		$str .= "use QuickFeather\Html\ToolBar;\n";
		$str .= "/*\n";
		$str .= "\$savedValues = \\{$className}::getOneById(\$this->pdo, \$this->key);\n";
		$str .= "\$this->form = new FormEntity(\n";
		$str .= "\taction: \$this->context->link,\n";
		$str .= "\tdaoClass: \\{$className}::class,\n";
		$str .= "\tlanguageId: \$this->context->languageId,\n";
		$str .= "\tdefaultData: \$savedValues,\n";
		$str .= "\tunusedFields: [{$entity}::id]\n";
		$str .= ");\n\n";
		$str .= "// save entity\n";
		$str .= "\$entity = \$this->form->getPostedEntity();\n";
		$str .= "\\{$className}::updateById(\$this->pdo, \$entity, \$savedValues->id);\n";
		$str .= "*/\n\n";

		$str .= "/** @var AddController|EditController \$controller */\n";
		$str .= "/** @var FormEntity \$form */\n";
		$str .= "\$form = \$controller->getForm();\n";
		$str .= "/** @var ToolBar \$toolBar */\n";
		$str .= "\$toolBar = \$controller->getToolBar();\n";

		$str .= "\$form->startForm();\n";
		$str .= "\$toolBar?->Create();\n\n";
		$str .= "\$form->startBox(_('Základní informace'));\n\n";

		$str .= "// renderCheckbox | renderSelect | renderInput | renderTextarea | renderUpload\n\n";

		foreach ($columns as $column) {
			if ($column['name'] === 'id') {
				continue;
			}

			if (strpos($column['type'], 'Id') === (strlen($column['type']) - 2)) {
				$method = 'renderSelect';
				$extends = '';
			} else if ($column['type'] === 'bool') {
				$method = 'renderCheckbox';
				$extends = ', labelBefore: false ';
			} else if (str_contains($column['type'], 'MultiString')) {
				$reflect = new ReflectionClass($column['type']);
				$length = (int)substr($reflect->getShortName(), 11);
				if ($length > $maxLength || $length === 0) { // zero mean unlimited
					$method = 'renderTextarea';
					$extends = ', extension: TextAreaField::TYPE_WYSIWYG ';
				} else {
					$method = 'renderInput';
					$extends = '';
				}
			} else {
				$method = 'renderInput';
				$extends = '';
			}

			if ($addDescription) {
				$extends .= ', description: _(\'...\')';
			}

			$str .= "\$form->{$method}(\\{$entity}::{$column['name']}, label: _('{$column['name']}'){$extends});\n";
		}

		$str .= "\n\$form->closeForm();\n";

		if ($templateName !== null && $templateName !== '') {
			$file = FOpen($config->getBaseDir() . 'src/Templates/' . $templateName, "w");

			if (!$file) {
				Message::add("ERROR: Šablonu se nepodařilo vytvořit!", Message::ERROR);
			} else {
				FWrite($file, $str);
				FClose($file);
				chmod($config->getBaseDir() . 'src/Templates/' . $templateName, 0777);

				Message::add("Šablona úspěšně vytvořena!", Message::INFO);
			}
		}

		return $str;
	}

	/**
	 * @return void
	 */
	public static function checkExtension(): void {
		$extensionList = ["mbstring", "pdo_pgsql", "pgsql", "bz2", "curl", "fileinfo", "gd", "gettext", "intl", "imap", "mbstring", "exif"];

		foreach ($extensionList as $extension) {
			if (extension_loaded($extension)) {
				Message::add($extension . ": OK", Message::INFO);
			} else {
				Message::add($extension . ": FAILED", Message::INFO);
			}
		}
	}

	/**
	 * @param string $className
	 * @param bool $isPhpFile
	 * @return string
	 */
	public static function classNameToFileName(string $className, bool $isPhpFile = true): string {
		$pathItems = explode('\\', $className);
		if (PHP_SAPI === "cli") {
			$file = $_SERVER['PWD'] . '/src/';
		} else {
			$file = $_SERVER['DOCUMENT_ROOT'] . '/src/';
		}
		foreach ($pathItems as $key => $item) {
			if ($key === 0) {
				$file .= $item . '/';
			} else if ($key < (count($pathItems) - 1)) {
				$file .= $item . '/';
			} else {
				$file .= $item;
			}
		}
		if ($isPhpFile) {
			$file .= '.php';
		}
		return $file;
	}

	/**
	 * @param PDO $pdo
	 * @return array
	 */
	public static function upgradeViews(PDO $pdo): array {
		$log = [];

		$folder = scandir('db/view');
		$pdo->beginTransaction();

		try {
			foreach ($folder as $item) {
				if (str_contains($item, '.')) {
					continue;
				}
				$log[] = '<h3><span class="label-info">Schema</span>:' . $item . '</h3>';

				$packageContent = scandir('db/view/' . $item);
				foreach ($packageContent as $view) {
					if (str_contains($view, '.sql')) {
						if (str_contains($view, '__')) {
							$viewName = substr($view, strpos($view, '__') + 2);
						} else {
							$viewName = $view;
						}
						$viewName = $item . '.' . str_replace('.sql', '', $viewName);

						$content = file_get_contents('db/view/' . $item . '/' . $view);
						if (!$content) {
							$log[] = '<span class="label-danger">CREATE ERROR</span>: ' . $viewName;
						} else {
							$pdo->query($content);
							$log[] = '<span class="label-success">CREATE</span>: ' . $viewName;
						}
					}
				}
			}
			$pdo->commit();
		} catch (PDOException $exception) {
			$pdo->rollBack();
			$log[] = '<span class="label-danger">ERROR</span>: ' . $exception;
		}
		return $log;
	}

	/**
	 * @param PDO $pdo
	 * @param array $dropViews
	 * @return array
	 */
	public static function dropViews(PDO $pdo, array $dropViews): array {
		$log = [];

		foreach ($dropViews as $view) {
			$pdo->query('DROP VIEW IF EXISTS ' . $view);
			$log[] = '<span class="label-warning">DROP</span>: ' . $view;
		}
		return $log;
	}

	/**
	 * @param PDO $pdo
	 * @param Config $config
	 * @return bool
	 * @throws ReflectionException
	 */
	public static function runDbChanges(PDO $pdo, Config $config): bool {
		$directory = $config->getBaseDir() . '/db/changes';
		$extension = 'sql';

		$files = scandir($directory);

		foreach ($files as $file) {
			$fileExtension = (string)pathinfo($file, PATHINFO_EXTENSION);
			if ($fileExtension === $extension) {
				/** @var ChangeScript|null $scriptFile */
				$scriptFile = ChangeScriptDao::getOne($pdo,
					where: db::is(ChangeScript::file, $file, true)
				);

				if ($scriptFile && $scriptFile->runQueriesCount === $scriptFile->totalQueriesCount && $file !== end($files)) {
					continue;
				}

				$fileContent = file_get_contents($directory . '/' . $file);

				$sqlCommands = preg_split('/;[ \t\r\n]*\n/', $fileContent);
				$sqlCommands = array_map('trim', $sqlCommands);
				$sqlCommands = array_filter($sqlCommands, 'strlen');
				$sqlCommands = array_map(static function ($command) {
					return $command . ';';
				}, $sqlCommands);

				if ($scriptFile === null) {
					$pdo->beginTransaction();
					$scriptFile = new ChangeScript (
						-1,
						new String300($file),
						0,
						count($sqlCommands),
						new DateTime(),
					);
					$scriptFile->id = ChangeScriptDao::insert($pdo, $scriptFile);
					$pdo->commit();
				}

				$scriptFile->totalQueriesCount = count($sqlCommands);

				if ($scriptFile->runQueriesCount === $scriptFile->totalQueriesCount) {
					continue;
				}

				echo "<p><b>File: {$file}</b></p>";

				$ok = true;
				$startTime = microtime(true);

				foreach ($sqlCommands as $key => $query) {
					if ($scriptFile->runQueriesCount <= $key) {
						echo "<p>Run: {$key}";
						try {
							echo " <span class='query'>{$query} </span>";
							$pdo->query($query);
							$scriptFile->runQueriesCount++;
						} catch (PDOException $exception) {
							echo "<p style='color: red'>ERROR: {$exception->getMessage()}</p>";
							$ok = false;
							break;
						}
						echo "</p>";
					}
				}

				$scriptFile->runTime = (int)(microtime(true) - $startTime);
				$scriptFile->runStartTime = new DateTime();
				ChangeScriptDao::updateById($pdo, $scriptFile, $scriptFile->id);
				if (!$ok) {
					return false;
				}
			}
		}

		$log = self::upgradeViews($pdo);
		foreach ($log as $item) {
			echo "<p>{$item}</p>";
		}

		return true;
	}

	/**
	 * @param PDO $pdo
	 * @return bool
	 * @throws ReflectionException
	 */
	public static function dbChangesList(PDO $pdo): bool {
		$list = ChangeScriptDao::getList($pdo,
			orderBy: ChangeScript::id
		);

		/** @var ChangeScript $item */
		echo "<table>";
		echo "<tr>";
		echo "<th>ID</th>";
		echo "<th>File</th>";
		echo "<th>Run Queries Count</th>";
		echo "<th>Total Queries Count</th>";
		echo "<th>Run Start Time</th>";
		echo "<th>Run Time</th>";
		echo "</tr>";

		foreach ($list as $item) {
			echo "<tr class='" . ($item->runQueriesCount < $item->totalQueriesCount ? "not-ready" : "ready") . "'>";
			echo "<td>{$item->id}</td>";
			echo "<td>{$item->file}</td>";
			echo "<td>{$item->runQueriesCount}</td>";
			echo "<td>{$item->totalQueriesCount}</td>";
			echo "<td>{$item->runStartTime->format('d.m Y')}</td>";
			echo "<td>{$item->runTime}</td>";
			echo "</tr>";
		}
		echo "<table>";

		return true;
	}

	/**
	 * @param Config $config
	 * @return bool
	 */
	public static function errorsList(Config $config): bool {
		$directory = $config->getBaseDir() . 'data/errors';
		$extension = 'pdf';

		$files = scandir($directory);

		echo "<h3>Seznam chyb, celkem: " . (count($files) - 2) . "</h3>";
		echo "<table>";
		echo "<tr>";
		echo "<th>File</th>";
		echo "</tr>";

		foreach ($files as $file) {
			$fileExtension = (string)pathinfo($file, PATHINFO_EXTENSION);
			if ($fileExtension === $extension) {
				$link = $config->getBaseUrl() . 'data/errors/' . $file;
				echo "<tr>";
				echo "<td><a href='{$link}' target='_blank'>{$file}</a> </td>";
				echo "</tr>";
			}
		}
		echo "</table>";
		return true;
	}

	/**
	 * @param Config $config
	 * @return bool
	 */
	public static function tempFilesList(Config $config): bool {

		echo "Aktuální temp index v konfiguraci: " . $config->getCssCompileVersion() . " <br>";

		$dir = $config->getBaseDir() . 'static/admin/minified/';

		$files = scandir($dir);
		$fileCount = count($files) - 2;
		echo "Počet temp souborů v adresáři Admin: $fileCount. <br>";

		$dir = $config->getBaseDir() . 'static/bazarexpres/minified/';

		$files = scandir($dir);
		$fileCount = count($files) - 2;
		echo "Počet temp souborů v adresáři Bazarexpres: $fileCount. <br>";

		$dir = $config->getBaseDir() . 'static/common/minified/';

		$files = scandir($dir);
		$fileCount = count($files) - 2;
		echo "Počet temp souborů v adresáři Common: $fileCount. <br>";

		return true;
	}

	/**
	 * @param Config $config
	 * @return bool
	 */
	public static function deleteTempFiles(Config $config): bool {
		$dir = $config->getBaseDir() . 'static/admin/minified/';

		$files = scandir($dir);
		foreach ($files as $file) {
			$extension = (string)pathinfo($file, PATHINFO_EXTENSION);
			if ($extension === 'css' || $extension === 'js') {
				unlink($dir . $file);
			}
		}

		$dir = $config->getBaseDir() . 'static/bazarexpres/minified/';

		$files = scandir($dir);
		foreach ($files as $file) {
			$extension = (string)pathinfo($file, PATHINFO_EXTENSION);
			if ($extension === 'css' || $extension === 'js') {
				unlink($dir . $file);
			}
		}

		$dir = $config->getBaseDir() . 'static/common/minified/';

		$files = scandir($dir);
		foreach ($files as $file) {
			$extension = (string)pathinfo($file, PATHINFO_EXTENSION);
			if ($extension === 'css' || $extension === 'js') {
				unlink($dir . $file);
			}
		}

		echo "Temp soubory byly smazány.";
		return true;
	}

	/**
	 * @return bool
	 */
	public static function incrementCompileVersion(): bool {
		// Cesta k souboru
		$file = 'config/appConfig.php';

		// Načtení obsahu souboru
		$content = file_get_contents($file);

		// Zjištění aktuální hodnoty css_compile_version
		preg_match("/(config\['css_compile_version'] = )(\d+);/", $content, $matches);
		$currentVersion = isset($matches[2]) ? (int)$matches[2] : 0;

		// Nová hodnota pro css_compile_version
		$newVersion = $currentVersion + 1;

		// Nahrazení staré hodnoty novou
		$count = 0;
		$content = preg_replace("/(config\['css_compile_version'] = )(\d+);/", "config['css_compile_version'] = " . $newVersion . ";", $content, 1, $count);

		// Uložení změněného obsahu zpět do souboru
		file_put_contents($file, $content);

		return $count > 0;
	}
}
