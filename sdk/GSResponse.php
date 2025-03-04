<?php
/**
 * Created by PhpStorm.
 * User: Yaniv Aran-Shamir
 * Date: 4/6/16
 * Time: 8:52 PM
 */

class GSResponse
{
	private $errorCode = 0;
	private $errorMessage = null;
	private $rawData = "";
	/**
	 * @var GSObject
	 */
	private $data;
	/**
	 * @var GSObject
	 */
	private static $errorMsgDic;
	/**
	 * @var GSObject
	 */
	private $params = null;
	private $method = null;
	private $traceLog = null;

	public static function init() {
		try {
			self::$errorMsgDic = new GSObject();
		} catch (Exception $e) {
			/* An exception can only be thrown if the constructor gets JSON input, which is not the case here. */
		}
		self::$errorMsgDic->put(400002, "Required parameter is missing");
		self::$errorMsgDic->put(400003, "You must set a certificate for HTTPS requests");
		self::$errorMsgDic->put(500000, "General server error");
	}

	public function getErrorCode() {
		return $this->errorCode;
	}

	/**
	 * @return string
	 * @throws GSKeyNotFoundException
	 */
	public function getErrorMessage() {
		if (isset($this->errorMessage))
			return $this->errorMessage;
		else
		{

			if ($this->errorCode == 0 || !self::$errorMsgDic->containsKey((int)$this->errorCode))
				return "";
			else
				return self::$errorMsgDic->getString($this->errorCode);
		}
	}

	public function getResponseText() {
		return $this->rawData;
	}

	public function getData() {
		return $this->data;
	}

	/**
	 * Get Boolean
	 *
	 * @param $key
	 * @param string $defaultValue
	 *
	 * @return bool
	 * @throws GSKeyNotFoundException
	 */
	public function getBool($key, $defaultValue = GSObject::DEFAULT_VALUE) {
		return $this->data->getBool($key, $defaultValue);
	}

	/**
	 * Get Integer
	 *
	 * @param $key
	 * @param string $defaultValue
	 *
	 * @return int
	 * @throws GSKeyNotFoundException
	 */
	public function getInt($key, $defaultValue = GSObject::DEFAULT_VALUE) {
		return $this->data->getInt($key, $defaultValue);
	}

	/**
	 * Get Float
	 *
	 * @param $key
	 * @param string $defaultValue
	 *
	 * @return float
	 * @throws GSKeyNotFoundException
	 */
	public function getFloat($key, $defaultValue = GSObject::DEFAULT_VALUE) {
		return $this->data->getFloat($key, $defaultValue);
	}

	/**
	 * Get String
	 *
	 * @param $key
	 * @param string $defaultValue
	 *
	 * @return string
	 * @throws GSKeyNotFoundException
	 */
	public function getString($key, $defaultValue = GSObject::DEFAULT_VALUE) {
		return $this->data->getString($key, $defaultValue);
	}

	/**
	 * Get GSObject
	 *
	 * @param $key
	 *
	 * @return object
	 * @throws GSKeyNotFoundException
	 */
	public function getObject($key) {
		return $this->data->getObject($key);
	}

	/**
	 * Get GSObject array
	 *
	 * @param $key
	 *
	 * @return mixed
	 * @throws GSKeyNotFoundException
	 */
	public function getArray($key) {
		return $this->data->getArray($key);
	}

	/**
	 * GSResponse constructor.
	 *
	 * @param $method
	 * @param $responseText
	 * @param $params
	 * @param $errorCode
	 * @param $errorMessage
	 * @param $traceLog
	 *
	 * @throws GSException
	 */
	public function __construct($method, $responseText = null, $params = null, $errorCode = null, $errorMessage = null, $traceLog = null) {
		$this->data = new GSObject();
		$this->traceLog = $traceLog;
		$this->method = $method;
		if (empty($params))
			$this->params = new GSObject();
		else
			$this->params = $params;

		if (!empty($responseText))
		{
			$this->rawData = $responseText;
			if (strpos(ltrim($responseText), "{") !== false)
			{
				$this->data = new GSObject($responseText);
				if (isset($this->data))
				{
					if ($this->data->containsKey("errorCode"))
					{
						$this->errorCode = $this->data->getInt("errorCode");
					}
					if ($this->data->containsKey("errorMessage"))
					{
						$this->errorMessage = $this->data->getString("errorMessage");
					}
				}
			}
			else
			{
				$matches = array();
				preg_match("~<errorCode\s*>([^<]+)~", $this->rawData, $matches);
				if (sizeof($matches) > 0)
				{
					$errCodeStr = $matches[1];
					if ($errCodeStr != null)
					{
						$this->errorCode = (int)$errCodeStr;

						$matches = array();
						preg_match("~<errorMessage\s*>([^<]+)~", $this->rawData, $matches);
						if (sizeof($matches) > 0)
						{
							$this->errorMessage = $matches[1];
						}
					}
				}
			}

		}
		else
		{

			$this->errorCode = $errorCode;
			$this->errorMessage = $errorMessage != null ? $errorMessage : self::getErrorMessage();
			$this->populateClientResponseText();
		}
	}

	/**
	 * @throws GSKeyNotFoundException
	 */
	private function populateClientResponseText() {
		if ($this->params->getString("format", "json"))
		{
			$this->rawData = "{errorCode:" . $this->errorCode . ",errorMessage:\"" . $this->errorMessage . "\"}";
		}
		else
		{
			$sb = array(
				"<?xml version=\"1.0\" encoding=\"utf-8\"?>"
			, "<" . $this->method . "Response xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"urn:com:gigya:api http://socialize-api.gigya.com/schema\" xmlns=\"urn:com:gigya:api\">"
			, "<errorCode>" . $this->errorCode . "</errorCode>"
			, "<errorMessage>" . $this->errorMessage . "</errorMessage>"
			, "</" . $this->method . "Response>",
			);

			$this->rawData = implode("\r\n", $sb);
		}
	}

	public function getLog() {
		return implode("\r\n", $this->traceLog);
	}

	public function __toString() {
		$sb = "";
		$sb .= "\terrorCode:";
		$sb .= $this->errorCode;
		$sb .= "\n\terrorMessage:";
		$sb .= $this->errorMessage;
		$sb .= "\n\tdata:";
		$sb .= $this->data;
		return $sb;
	}
}
