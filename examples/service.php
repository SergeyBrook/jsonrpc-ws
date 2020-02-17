<?php
/**
 * Example JSON RPC service.
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

// Process request and output response:
$jrpc->respond();

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
