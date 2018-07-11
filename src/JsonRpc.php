<?php
/**
 * JsonRpc.php
 *
 * @author Sergey Brook
 * @copyright 2018 Sergey Brook
 * @license MIT (see LICENSE)
 */
namespace SBrook;

/**
 * JsonRpc Class
 *
 * @see http://www.jsonrpc.org/specification
 */
class JsonRpc {
	private $service = "UNKNOWN";
	private $userIsAuth = false;
	private $methods = [];
	private $requests = [];
	private $responses = [];
	private $errors = [
		"server" => [	// -32000 to -32099: JSON-RPC v2.0 Spec - Reserved for implementation-defined server-errors.
			/*0*/	["code" => -32000, "message" => "Unhandled server error"],
			/*1*/	["code" => -32001, "message" => "User not authenticated"]
		],
		"jsonrpc" => [	// -32100 to -32768: JSON-RPC v2.0 Spec - JSON-RPC Pre-defined errors.
			/*0*/	["code" => -32600, "message" => "Invalid request"],		// The JSON sent is not a valid Request object.
			/*1*/	["code" => -32601, "message" => "Method not found"],	// The method does not exist / is not available.
			/*2*/	["code" => -32602, "message" => "Invalid params"],		// Invalid method parameter(s).
			/*3*/	["code" => -32603, "message" => "Internal error"],		// Internal JSON-RPC error.
			/*4*/	["code" => -32700, "message" => "Parse error"]			// Invalid JSON was received by the server. An error occurred on the server while parsing the JSON text.
		],
		"service" => []	// -32769 and on: JSON-RPC v2.0 Spec - Available for application defined errors.
	];


	public function __construct($methods = []) {
		// TODO: sanitize
		$this->methods = $methods;

		// TODO: Catch parse error (-32700) here.
		$r = json_decode(file_get_contents("php://input"), true);

		if (is_array($r)) {
			if (array_key_exists("jsonrpc", $r)) {
				// Single request:
				$this->requests[] = $r;
			} else {
				// Batch request:
				// TODO: Check every single request.
				$this->requests = $r;
			}
		}
	}

	public function __destruct() {
		unset(
			$this->methods,
			$this->requests,
			$this->responses,
			$this->errors
		);
	}

	public function setServiceErrors(array $errors) {
		// TODO: Sanitize.
		$this->errors["service"] = $errors;
	}

	public function setServiceName(string $name) {
		// TODO: Sanitize.
		$this->service = $name;
	}

	public function setAuth(bool $auth) {
		$this->userIsAuth = $auth;
	}

	public function getError(string $type, int $idx) {
		$result = [
			"type" => "error",
			"value" => []
		];

		// If invalid parameters supplied fall back to default error:
		if (!array_key_exists($type, $this->errors) || !array_key_exists($idx, $this->errors[$type])) {
			$type = "server";
			$idx = 0;
		}

		// Set error message:
		$result["value"]["code"] = $this->errors[$type][$idx]["code"];
		$result["value"]["message"] = $this->errors[$type][$idx]["message"];
		$result["value"]["data"] = $this->service;

		return $result;
	}

	public function respond() {
		$requestsCount = count($this->requests);

		if ($requestsCount > 0) {
			for ($i = 0; $i < $requestsCount; $i++) {
				$r = $this->processRequest($this->requests[$i]);

				// Do not reply to notification messages (without id):
				if (array_key_exists("id", $this->requests[$i])) {
					$this->responses[] = [
						"jsonrpc" => "2.0",
						$r["type"] => $r["value"],
						"id" => $this->requests[$i]["id"]
					];
				}
			}
		} else {
			// Error (jsonrpc:0) -32600 Invalid request
			$r = $this->getError("jsonrpc", 0);

			$this->responses[] = [
				"jsonrpc" => "2.0",
				$r["type"] => $r["value"],
				"id" => null
			];
		}

		header("Content-Type: application/json");
		// TODO: Rewrite.
		echo json_encode(count($this->responses) > 1 ? $this->responses : $this->responses[0]);
	}

	// Process single request:
	private function processRequest($request) {
		$result = [];

		// Is user authenticated when required:
		if ($this->methods[$request["method"]]["auth"] && $this->userIsAuth || !$this->methods[$request["method"]]["auth"]) {
			// Is called registered method:
			if (array_key_exists($request["method"], $this->methods)) {
				// Is handle exists:
				if (function_exists($this->methods[$request["method"]]["handle"])) {
					$ok = true;
					foreach ($this->methods[$request["method"]]["params"] as $key => $value) {
						// Are all parameters set:
						if (!is_array($request["params"]) || !array_key_exists($key, $request["params"])) {
							// Error (jsonrpc:2) -32602 Invalid params
							$result = $this->getError("jsonrpc", 2);
							$ok = false;
							break;
						} else {
							$reqType = $this->methods[$request["method"]]["params"][$key];
							$setType = gettype($request["params"][$key]);
							// Are all parameters of required datatype:
							if ($setType != $reqType) {
								// Error (jsonrpc:2) -32602 Invalid params
								$result = $this->getError("jsonrpc", 2);
								$ok = false;
								break;
							}
							// Is string parameter has an empty value:
							else if ($reqType == "string" && $request["params"][$key] == "") {
								// Error (jsonrpc:2) -32602 Invalid params
								$result = $this->getError("jsonrpc", 2);
								$ok = false;
								break;
							}
						}
					}
					// Is everything ok:
					if ($ok) {
						$result = $this->methods[$request["method"]]["handle"]($this, $request["params"]);
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
?>
