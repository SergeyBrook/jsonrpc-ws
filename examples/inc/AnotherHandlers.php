<?php

use SBrook\JsonRpc;

/**
 * Class AnotherHandlers
 */
class AnotherHandlers {
	public function handleThree(JsonRpc $jrpc, array $params): array {
		$result = [
			"type" => "result",
			"value" => "I need named parameters: paramOne = {$params["paramOne"]} and paramTwo = {$params["paramTwo"]}"
		];

		// Set user authentication from handler:
		$jrpc->setAuth(false);

		return $result;
	}
}
