<?php
$config = array(
	"result_code_root" => "result", /* Element for result code in response */
	"successful_response_root" => "body", /* Root data element for succesful response */
	"error_response_root" => "error", /* Root data element for error response */
	"array_delimiter" => ",", /* Array delimiter for parameters */
	"use_post_method" => false, /* Set to "true" to use POST request method */

	"parameters" => [ /* Request parameters names and storage variables */
		"email" => [
			"type" => "string",
			"min" => 5,
			"max" => 120,
			"is_required" => true,
			"regex" => "/^\S+@\S+\.\S+$/"
		],
		"age" => [
			"type" => "integer",
			"min" => 18,
			"max" => 90,
			"is_required" => true
		],
		"active" => [
			"type" => "boolean",
			"is_required" => true
		],
		"height" => [
			"type" => "float",
			"min" => 10.0,
			"max" => 100.0,
			"is_required" => true
		],
		"features" => [
			"type" => "array",
			"min" => 1,
			"max" => 3,
			"is_required" => true
		],
		"config" => [
			"type" => "json",
			"is_required" => true
		],
		"nonce" => [
			"type" => "integer",
			"is_required" => false
		]
	]
)
?>