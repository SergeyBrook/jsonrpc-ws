<?php
/**
 * SBrook\JsonRpc
 */

namespace SBrook;

use stdClass;

/**
 * Class JsonRpc
 * @package SBrook
 */
class JsonRpc {
	public $userData = [];

	private $name = "";
	private $userAuth = false;

	private $methods = [];
	private $requests = [];
	private $responses = [];

	/**
	 * Errors.
	 * @var array $errors
	 */
	private $errors = [
		"server" => [ // -32000 to -32099: JSON-RPC v2.0 Spec - Reserved for implementation-defined server-errors.
			/*0*/ ["code" => -32000, "message" => "General server error"],
			/*1*/ ["code" => -32001, "message" => "User not authenticated"],
			/*2*/ ["code" => -32002, "message" => "Malformed response data"]
		],
		"jsonrpc" => [ // -32100 to -32768: JSON-RPC v2.0 Spec - JSON-RPC Pre-defined errors.
			/*0*/ ["code" => -32600, "message" => "Invalid request"], // The JSON sent is not a valid Request object.
			/*1*/ ["code" => -32601, "message" => "Method not found"], // The method does not exist / is not available.
			/*2*/ ["code" => -32602, "message" => "Invalid params"], // Invalid method parameter(s).
			/*3*/ ["code" => -32603, "message" => "Internal error"], // Internal JSON-RPC error.
			/*4*/ ["code" => -32700, "message" => "Parse error"] // Invalid JSON was received by the server. An error occurred on the server while parsing the JSON text.
		],
		"service" => [] // -32769 and on: JSON-RPC v2.0 Spec - Available for application defined errors.
	];

	/**
	 * JsonRpc constructor.
	 * @param array $methods
	 */
	public function __construct(array $methods = []) {
		$this->methods = $methods;
	}

	/**
	 * JsonRpc destructor.
	 */
	public function __destruct() {
		unset(
			$this->userData,
			$this->methods,
			$this->requests,
			$this->responses
		);
	}

	/**
	 * Set service-level errors.
	 * @param array $errors
	 */
	public function setServiceErrors(array $errors) {
		$this->errors["service"] = $errors;
	}

	/**
	 * Set service name.
	 * !!! DEPRECATED !!!
	 * @param string $name
	 */
	public function setServiceName(string $name = "") {
		$this->setName($name);
	}

	/**
	 * Set service name.
	 * @param string $name
	 */
	public function setName(string $name = "") {
		$this->name = trim($name);
	}

	/**
	 * Get service name.
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Set user authentication.
	 * @param bool $auth
	 */
	public function setAuth(bool $auth) {
		$this->userAuth = $auth;
	}

	/**
	 * Get error by type and index.
	 * @param string $type (server / jsonrpc / service).
	 * @param int $idx
	 * @return array
	 */
	public function getError(string $type, int $idx): array {
		$result = [
			"type" => "error",
			"value" => []
		];

		if (array_key_exists($type, $this->errors) && array_key_exists($idx, $this->errors[$type])) {
			// Set error message:
			$result["value"]["code"] = $this->errors[$type][$idx]["code"];
			$result["value"]["message"] = $this->errors[$type][$idx]["message"];
		} else {
			// Fall back to default error:
			$result["value"]["code"] = $this->errors["server"][0]["code"];
			$result["value"]["message"] = $this->errors["server"][0]["message"];
		}

		if (strlen($this->name) > 0) {
			$result["value"]["data"]["name"] = $this->name;
		}

		return $result;
	}

	/**
	 * Respond.
	 */
	public function respond() {
		// Cleanup:
		$this->responses = [];

		// Get request data:
		if ($this->getRequest()) {
			// Process request(s):
			if (count($this->requests) > 0) {
				foreach ($this->requests as $request) {
					if ($this->validateRequest($request)) {
						$response = $this->processRequest($request);

						// Do not reply to notification messages (those without id):
						if (property_exists($request, "id")) {
							if ($this->validateResponse($response)) {
								$this->pushResponse($response, $request->id);
							} else {
								// Error (server:2) -32002 Malformed response data
								$this->pushError("server", 2);
							}
						}
					} else {
						// Error (jsonrpc:0) -32600 Invalid request
						$this->pushError("jsonrpc", 0);
					}
				}
			} else {
				// Error (jsonrpc:0) -32600 Invalid request
				$this->pushError("jsonrpc", 0);
			}
		} else {
			// Error (jsonrpc:4) -32700 Parse error
			$this->pushError("jsonrpc", 4);
		}

		// Prepare response:
		$header = "Content-Type: application/json";
		$payload = "";

		$requestCount = count($this->requests);
		$responseCount = count($this->responses);

		if ($requestCount == 0) {
			// In case of 'parse error':
			$payload = json_encode($this->responses[0]);
		} else {
			if ($responseCount > 0) {
				if ($requestCount == 1) {
					// Single request:
					$payload = json_encode($this->responses[0]);
				} else {
					// Batch request:
					$payload = json_encode($this->responses);
				}
			} else {
				// In case of 'notification(s)':
				$header = "Content-Type: text/html";
			}
		}

		// Respond:
		header($header);
		echo $payload;
	}

	/**
	 * Get request.
	 * @return bool
	 */
	private function getRequest(): bool {
		$result = false;

		// Cleanup:
		$this->requests = [];

		// Get input data:
		$request = json_decode(file_get_contents("php://input"));

		if (!is_null($request)) {
			$result = true;

			if (is_array($request)) {
				// Batch request:
				foreach ($request as $r) {
					$this->pushRequest($r);
				}
			} else {
				// Single request:
				$this->pushRequest($request);
			}
		}

		return $result;
	}

	/**
	 * Push single request to 'requests':
	 * @param mixed $request
	 */
	private function pushRequest($request = null) {
		$this->requests[] = is_object($request) ? $request : new stdClass();
	}

	/**
	 * Push single response to 'responses'.
	 * @param array $response
	 * @param mixed $id
	 */
	private function pushResponse(array $response, $id = null) {
		$this->responses[] = [
			"jsonrpc" => "2.0",
			$response["type"] => $response["value"],
			"id" => $id
		];
	}

	/**
	 * Push single error to 'responses'.
	 * @param string $type
	 * @param int $idx
	 * @param mixed $id
	 */
	private function pushError(string $type, int $idx, $id = null) {
		$this->pushResponse($this->getError($type, $idx), $id);
	}

	/**
	 * Process single request.
	 * @param object $request
	 * @return array
	 */
	private function processRequest($request) {
		//$result = [];

		// Is user authenticated (when required)?
		if ($this->methods[$request->method]["auth"] && $this->userAuth || !$this->methods[$request->method]["auth"]) {
			// Is called registered method?
			if (array_key_exists($request->method, $this->methods)) {
				$handle = $this->methods[$request->method]["handle"];

				// Is handle callable?
				if (is_callable($handle)) {
					// Are all parameters valid?
					if ($this->validateParams($request)) {
						$params = [$this, $this->getParams($request)];
						$result = call_user_func_array($handle, $params);
					} else {
						// Error (jsonrpc:2) -32602 Invalid params
						$result = $this->getError("jsonrpc", 2);
					}
				} else {
					// Handle not callable:
					// Error (jsonrpc:1) -32601 Method not found
					$result = $this->getError("jsonrpc", 1);
				}
			} else {
				// Method not registered:
				// Error (jsonrpc:1) -32601 Method not found
				$result = $this->getError("jsonrpc", 1);
			}
		} else {
			// Error (server:1) -32001 User not authenticated
			$result = $this->getError("server", 1);
		}

		return $result;
	}

	/**
	 * Validate single request.
	 * @param mixed $request
	 * @return bool
	 */
	private function validateRequest($request): bool {
		return is_object($request) && property_exists($request, "jsonrpc") && property_exists($request, "method");
	}

	/**
	 * Validate single response.
	 * @param mixed $response
	 * @return bool
	 */
	private function validateResponse($response): bool {
		return is_array($response) && array_key_exists("type", $response) && array_key_exists("value", $response);
	}

	/**
	 * Validate request parameters.
	 * @param object $request
	 * @return bool
	 */
	private function validateParams($request): bool {
		// Allowed types:
		$types = [
			"boolean",
			"integer",
			"double",
			"string",
			"array"
		];

		// Is there required parameters?
		if (array_key_exists("params", $this->methods[$request->method]) && count($this->methods[$request->method]["params"]) > 0) {
			// Get request parameters:
			$params = $this->getParams($request);

			// Check each parameter:
			foreach ($this->methods[$request->method]["params"] as $key => $requiredType) {
				$ok = false;
				$requiredType = strtolower($requiredType);

				// Is required type allowed?
				if (in_array($requiredType, $types)) {
					// Is parameter set?
					if (array_key_exists($key, $params)) {
						$actualType = strtolower(gettype($params[$key]));

						// Is parameter of a required datatype?
						if ($actualType == $requiredType) {
							// Datatype specific checks:
							switch ($requiredType) {
								case "string":
									// Is string parameter has an empty value?
									$ok = strlen($params[$key]) > 0;
									break;
								default:
									$ok = true;
							}
						}
					}
				}

				// Return on first failed parameter:
				if (!$ok) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get request parameters.
	 * @param object $request
	 * @return array
	 */
	private function getParams($request): array {
		$result = [];

		if (property_exists($request, "params")) {
			if (is_array($request->params)) {
				$result = $request->params;
			} else if (is_object($request->params)) {
				foreach ($request->params as $key => $value) {
					$result[$key] = $value;
				}
			}
		}

		return $result;
	}
}
