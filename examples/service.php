<?php
/**
 * Example JSON RPC service.
 */

// Stand-alone:
require_once("../src/JsonRpc.php");
// Composer:
//require_once("../vendor/autoload.php");


// Service methods declaration:
// key				= Exposed method name.
// value["handle"]	= Method handle function.
// value["auth"]	= Method requires authentication.
// value["params"]	= Method parameters type or name => type.
//						Types: boolean, integer, double, string, array
$methods = [
	"methodOne" => [
		"handle" => "handleOne",
		"auth" => false/*,
		"params" => []*/ // No parameters
	],
	"methodTwo" => [
		"handle" => "handleTwo",
		"auth" => false,
		"params" => ["integer", "string"] // Positional (indexed) parameters
	],
	"methodThree" => [
		"handle" => "handleThree",
		"auth" => false,
		"params" => ["paramOne" => "string", "paramTwo" => "integer"] // Named parameters
	]
];

// Service level errors definition:
// -32769 and on: JSON-RPC v2.0 Spec - Available for application defined errors.
$errors = [
	/*0*/ ["code" => -32800, "message" => "General service error"],
	/*1*/ ["code" => -32801, "message" => "Error two"],
	/*2*/ ["code" => -32802, "message" => "Error three"]
];


// JSON-RPC - create instance and register service methods:
$jrpc = new SBrook\JsonRpc($methods);

// Set service name (optional):
$jrpc->setName("JSON-RPC-TEST");

// Set service level errors (optional):
$jrpc->setServiceErrors($errors);

// Set user authentication (optional, by default set to FALSE):
if (/*$userAuthenticated ===*/ true) {
	$jrpc->setAuth(true);
}

// Process request (single or batch) and output response:
$jrpc->respond();

// Exit:
exit();


function handleOne($jrpc, $params) {
	$result = [
		"type" => "result",
		"value" => "I need no parameters :)"
	];

	// Set user authentication from handler:
	//$jrpc->setAuth(true);

	// Set error from handler:
	//$result = $jrpc->getError("service", 0);

	return $result;
}

function handleTwo($jrpc, $params) {
	$result = [
		"type" => "result",
		"value" => "I need positional parameters: 0 = {$params[0]} and 1 = {$params[1]}"
	];

	// Set user authentication from handler:
	//$jrpc->setAuth(false);

	// Set error from handler:
	//$result = $jrpc->getError("service", 1);

	return $result;
}

function handleThree($jrpc, $params) {
	$result = [
		"type" => "result",
		"value" => "I need named parameters: paramOne = {$params["paramOne"]} and paramTwo = {$params["paramTwo"]}"
	];

	// Set error from handler:
	//$result = $jrpc->getError("service", 2);

	return $result;
}
