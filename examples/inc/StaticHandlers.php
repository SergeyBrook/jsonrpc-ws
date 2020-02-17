<?php

use SBrook\JsonRpc;

/**
 * Class StaticHandlers
 */
class StaticHandlers {
	public static function handleTwo(JsonRpc $jrpc, array $params): array {
		$result = [
			"type" => "result",
			"value" => "I need positional parameters: 0 = {$params[0]} and 1 = {$params[1]}"
		];

		// Set error from handler:
		if ($params[0] < 0) {
			$result = $jrpc->getError("service", 1);
		}

		return $result;
	}
}
