<?php
/**
 * SBrook\JsonRpc
 */

namespace SBrook;

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
			/*1*/ ["code" => -32001, "message" => "User not authenticated"]
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

	public function respond() {
		// Cleanup:
		$this->responses = [];

		// Get request data:
		if ($this->getRequest()) {
			// Process request(s):
			if (count($this->requests) > 0) {
				foreach ($this->requests as $request) {
					$response = $this->processRequest($request);

					// Do not reply to notification messages (those without id):
					if (array_key_exists("id", $request)) {
						$this->pushResponse($response, $request["id"]);
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

		// Respond:
		header("Content-Type: application/json");
		// TODO: Rewrite.
		echo json_encode(count($this->responses) > 1 ? $this->responses : $this->responses[0]);
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
		$r = json_decode(file_get_contents("php://input"), true);

		if (is_array($r)) {
			if (array_key_exists("jsonrpc", $r)) {
				// Single request:
				$this->requests[] = $r;
				$result = true;
			} else {
				// Batch request:
				// TODO: Check every single request.
				$this->requests = $r;
				$result = true;
			}
		}

		return $result;
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
	 * @param $request
	 * @return array
	 */
	private function processRequest($request) {
		$result = [];

		// Is user authenticated (when required):
		if ($this->methods[$request["method"]]["auth"] && $this->userAuth || !$this->methods[$request["method"]]["auth"]) {
			// Is called registered method:
			if (array_key_exists($request["method"], $this->methods)) {
				$handle = $this->methods[$request["method"]]["handle"];

				// Is handle callable:
				if (is_callable($handle)) {
					$ok = true;

					// Check parameters:
					foreach ($this->methods[$request["method"]]["params"] as $key => $requiredType) {
						// Are all parameters set?
						if (!is_array($request["params"]) || !array_key_exists($key, $request["params"])) {
							// Error (jsonrpc:2) -32602 Invalid params
							$result = $this->getError("jsonrpc", 2);
							$ok = false;
							break;
						} else {
							$actualType = gettype($request["params"][$key]);

							// Is parameter of a required datatype?
							if ($actualType != $requiredType) {
								// Error (jsonrpc:2) -32602 Invalid params
								$result = $this->getError("jsonrpc", 2);
								$ok = false;
								break;
							}

							// Is string parameter has an empty value?
							else if ($requiredType == "string" && $request["params"][$key] == "") {
								// Error (jsonrpc:2) -32602 Invalid params
								$result = $this->getError("jsonrpc", 2);
								$ok = false;
								break;
							}
						}
					}

					// Is everything ok?
					if ($ok) {
						$params = [$this, array_key_exists("params", $request) ? $request["params"] : []];
						$result = call_user_func_array($handle, $params);
					}
				} else {
					// Handle not exists:
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
}
