<?php
// 类的 static 数组在定义时无法包含匿名函数，只能使用曲线方法
DBz::$_lTypeNeedConvert = [
	function ($aMeta) {
		$lType = ["DATE", "DATETIME", "TIMESTAMP"];
		if (!in_array($aMeta["native_type"], $lType)) {
			return FALSE;
		}
		return "strtotime";
	},
	function ($aMeta) {
		$lType = ["LONG", "LONGLONG", 'SHORT', 'NEWDECIMAL'];
		if (!in_array($aMeta["native_type"], $lType)) {
			return FALSE;
		}
		return "intval";
	},
	function ($aMeta) {
		if (substr($aMeta["name"], 0, 3) !== "is_") {
			return FALSE;
		}

		return function($sValue) {
			return $sValue === "Y";
		};
	}
];

class DBz extends PDO {

	public $sName;
	protected $_lColumnNeedConvert;

	public $aConfig = [];

	public static $_lTypeNeedConvert = [];

	public function __construct() {

		$aConfig = require __DIR__.'/config.inc.php';
		$aConfig = $aConfig['mysql'];

		$this->sName = $aConfig["name"];

		$this->aConfig = [
			'dsn' => $aConfig['dsn'],
			'user' => $aConfig['user'],
			'password' => $aConfig['password'],
			'option' => [
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			]
		];

		$this->_connect();
	}

	protected function _connect() {

		$sName = $this->sName;
		$sDSN = $this->aConfig["dsn"];

		try {

			parent::__construct(
				$this->aConfig["dsn"],
				$this->aConfig["user"],
				$this->aConfig["password"],
				$this->aConfig["option"]
			);

		} catch (Exception $e) {

			error_log(
				$_SERVER[PHP_SAPI === "cli" ? "SCRIPT_FILENAME" : "REQUEST_URI"]."\n\n"
				."\tError: MySQL Connect fail, Database \"".$sName."\"\n\n"
				."\t".$e->getMessage()."\n\n"
				."\tDSN ".$sDSN."\n"
			);
			exit;
		}

	}

	/*
	 * 自动重连
	 */
	protected function _connectSmart() {
		if ($this->errorInfo()[1] == 2006) { // MySQL server has gone away
			$this->_connect();
			return TRUE;
		}
		return FALSE;
	}

	protected function _ColumnConvertScan($oResult) {

		// 转换变量类型 确定要转换的字段
		$iCount = $oResult->columnCount();
		$lConvert = [];

		foreach (range(0, $iCount - 1) as $iColumn) {

			$aMeta = $oResult->getColumnMeta($iColumn);
			$aMeta["native_type"] = ($sTmp =& $aMeta["native_type"]) ?: "UNKNOWN";

			$fnConvert = FALSE;
			foreach (self::$_lTypeNeedConvert as $iKey => $fnCheck) {
				if ($fnConvert = $fnCheck($aMeta)) {
					break;
				}
			}

			if ($fnConvert) {
				$lConvert[$aMeta["name"]] = $fnConvert;
			}
		}

		$this->_lColumnNeedConvert = $lConvert;
	}

	protected function _ColumnConvertDo($aRow) {

		// 转换变量类型 执行转换
		foreach ($this->_lColumnNeedConvert as $sKey => $fnConvert) {
			$aRow[$sKey] = $fnConvert($aRow[$sKey]);
		}

		return $aRow;
	}

	public function query() {

		$aArg = func_get_args();

		if (empty($aArg[0])) {
			debug_print_backtrace();
			die("empty query");
		}

		do {
			$oResult = call_user_func_array(array("parent", "query"), $aArg);
		} while ($this->_connectSmart());

		return $oResult;
	}

	public function exec($sQuery) {

		if (empty($sQuery)) {
			debug_print_backtrace();
			die("empty query");
		}

		do {
			$iAffected = parent::exec($sQuery);
		} while ($this->_connectSmart());

		return $iAffected;
	}

	public function getInsertID($sQuery) {
		if (!$this->exec($sQuery)) {
			return FALSE;
		}
		return $this->lastInsertId();
	}

	public function getAll($sQuery, $bByKey = TRUE) {

		if (!$oResult = $this->query($sQuery)) {
			return FALSE;
		}

		$aData = $oResult->fetchAll();
		if (empty($aData)) {
			return $aData;
		}

		self::_ColumnConvertScan($oResult);

		$iColumnCount = $oResult->columnCount();
		if ($bByKey && $iColumnCount == 1) {
			$bByKey = FALSE;
		}
		if ($bByKey) {
			$aKey = [];
		}
		$bPeelArray = ($iColumnCount == ($bByKey ? 2 : 1));
		foreach ($aData as $iRowKey => $aRow) {

			$aRow = self::_ColumnConvertDo($aRow);

			// 取第一个字段为 key，替代原来的顺序数字
			if ($bByKey) {
				$aKey[$iRowKey] = $bPeelArray ? array_shift($aRow) : current($aRow);
			}

			// 如果只有一列，则不再需要原来的 array 套了
			if ($bPeelArray) {
				$aRow = current($aRow);
			}
			$aData[$iRowKey] = $aRow;
		}

		if ($bByKey) {
			$aData = array_combine($aKey, $aData);
		}

		return $aData;
	}

	// 只取第一行
	public function getRow($sQuery) {

		if (!$oResult = $this->query($sQuery)) {
			return FALSE;
		}

		$aRow = $oResult->fetch();

		if (!empty($aRow)) {
			self::_ColumnConvertScan($oResult);
			$aRow = self::_ColumnConvertDo($aRow);
		}

		return $aRow;
	}

	// 只取第一行的第一个字段
	public function getSingle($sQuery) {

		if (!$aRow = $this->getRow($sQuery)) {
			return FALSE;
		}

		$aRow = current($aRow);

		return $aRow;
	}
}
