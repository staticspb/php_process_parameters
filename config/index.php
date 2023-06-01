<?php
$config = array(
	"result_code_root" => "result", 		/* Element for result code in response */
	"successful_response_root" => "body",	/* Root data element for successful response */
	"error_response_root" => "error",		/* Root data element for error response */
	"array_delimiter" => ",",				/* Array delimiter for parameters */
	"use_post_method" => false,				/* Set to "true" to use POST request method */
	"php_error_reporting" => 0,				/* Set error reporting level */
	"php_set_time_limit" => 0,				/* Set script execurion time limit */
	"php_memory_limit" => "-1",				/* Set script memory limit */
	"response_headers" => [					/* Array with headers to add to response */
		"Content-Type: application/json",
		"Access-Control-Allow-Origin: *",
		"Access-Control-Allow-Methods: GET",
		"Access-Control-Allow-Headers: Content-Type",
	],
	"parameters" => [ /* List of parameters for validation and processing */
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
	],
	"headers" => [ /* List of HTTP headers for validation and processing */
		"Accept-Language" => [
			"type" => "string",
			"is_required" => false
		],
		"Connection" => [
			"type" => "string",
			"regex" => "/^keep-alive$/",
			"is_required" => false
		],
		"Authorization" => [
			"type" => "string",
			"regex" => "/^Bearer [A-Za-z0-9-_]{32}$/",
			"is_required" => false,
			"default" => "Bearer 76d80224611fc919a5d54f0ff9fba446"
		]
	]
)
?>