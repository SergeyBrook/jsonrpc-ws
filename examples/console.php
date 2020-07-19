<?php
/**
 * Example JSON RPC console app.
 */

// Imports:
use SBrook\JsonRpc;

// Stand-alone:
require_once("../src/JsonRpc.php");
// Composer:
//require_once("../vendor/autoload.php");

// Some classes to play with:
require_once("./inc/UserClass.php");
require_once("./inc/StaticHandlers.php");
require_once("./inc/AnotherHandlers.php");


// Handlers:
$handlers = new AnotherHandlers();

/*
 * Service methods declaration:
 * key				= Exposed method name.
 * value["handle"]	= Method handle (callable).
 * value["auth"]	= Method requires authentication.
 * value["params"]	= Method parameters type or name => type. Allowed types: boolean, integer, double, string, array.
 */
$methods = [
	"methodOne" => [
		"handle" => "handleOne", // Function.
		"auth" => false
		//"params" => [] // No parameters - may be empty array or omitted.
	],
	"methodTwo" => [
		"handle" => "StaticHandlers::handleTwo", // Static class method.
		"auth" => true,
		"params" => ["integer", "string"] // Positional (indexed) parameters.
	],
	"methodThree" => [
		"handle" => [$handlers, "handleThree"], // Class method.
		"auth" => true,
		"params" => ["paramOne" => "string", "paramTwo" => "integer"] // Named parameters.
	]
];

// Service level errors definition:
// -32769 and on: JSON-RPC v2.0 Spec - Available for application defined errors.
$errors = [
	/*0*/ ["code" => -32800, "message" => "General service error"],
	/*1*/ ["code" => -32801, "message" => "Service error two"],
	/*2*/ ["code" => -32802, "message" => "Service error three"]
];


// JSON-RPC - create instance and register service methods:
$jrpc = new JsonRpc($methods);

// Set service name (optional):
$jrpc->setName("JSON-RPC-TEST");

// Set service level errors (optional):
$jrpc->setServiceErrors($errors);

// Set user authentication (optional, by default set to FALSE):
$jrpc->setAuth(true);

// Add UserClass instance to userData (optional):
$jrpc->userData["objectOne"] = new UserClass();


// Requests:
$request_one = [
	"jsonrpc" => "2.0",
	"method" => "methodOne",
	"id" => "single-1"
];

$request_two = [
	"jsonrpc" => "2.0",
	"method" => "methodThree",
	"params" => ["paramOne" => "foo", "paramTwo" => 123],
	"id" => "single-3"
];

/**
 * Process request and output response.
 * Sets the HTTP headers and echoes the payload (not really useful for console app).
 * Note: when running as a web-service, writing to output after headers were sent, will produce a warning:
 *  "Warning: Cannot modify header information - headers already sent ..."
 */
$ok = $jrpc->respond(json_encode($request_two));
echo "\nThe response " . ($ok ? "was" : "wasn't") . " sent.\n";

/**
 * Process request and return response.
 * Returns the complete response array (for further processing).
 * Response array example structure:
 *  "status" => ["code" => 0, "message" => "Ok"] : Request processing status - code => (int), message => (string). 0 = Ok, 1 = No requests processed.
 *  "headers" => ["Content-Type" => "application/json"] : HTTP headers as name-value pairs - name (string) => value (string).
 *  "payload" => "..." : JSON encoded response - (string).
 */
print_r($jrpc->getResponse(json_encode($request_one)));

// Exit:
exit();


function handleOne(JsonRpc $jrpc, array $params): array {
	$result = [
		"type" => "result",
		"value" => "I need no parameters and I can print " . $jrpc->userData["objectOne"]->message()
	];

	// Set user authentication from handler:
	$jrpc->setAuth(true);

	return $result;
}
