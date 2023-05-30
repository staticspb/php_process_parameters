<?php
// Suppress errors
error_reporting(0);

// Disable time limit
set_time_limit(0);
ini_set("memory_limit", "-1");

// Load config
include "config/index.php";

// Define required variables
define("TYPE_STRING", "string");
define("TYPE_FLOAT", "float");
define("TYPE_INTEGER", "integer");
define("TYPE_BOOLEAN", "boolean");
define("TYPE_ARRAY", "array");
define("TYPE_JSON", "json");
define("TYPE_FILE", "file");
define("VARIANTS_BOOLEAN", ["true", "false"]);
define("PARAM_PARAMETERS", "parameters");
define("PARAM_HEADERS", "headers");
define("PARAM_USE_POST", "use_post_method");
define("PARAM_TYPE", "type");
define("PARAM_DEFAULT", "default");
define("PARAM_IS_REQUIRED", "is_required");
define("PARAM_MIN", "min");
define("PARAM_MAX", "max");
define("PARAM_REGEX", "regex");
define("PARAM_ARRAY_DELIMITER", "array_delimiter");

// Add required headers to output
function addHeader($code, $config) {
	http_response_code($code);
	for ($i=0; $i<count($config["response_headers"]); $i++) {
		header($config["response_headers"][$i]);
	}
}

// Render response
function renderResponse($code, $rootName, $rootValue, $config) {
	addHeader($code, $config);

	echo json_encode(
		array(
			$config["result_code_root"] => $code,
			"$rootName" => $rootValue
		)
	);
	
	exit;
}

// Render result
function returnResult($body, $config) {
	renderResponse(200, $config["successful_response_root"], $body, $config);
}

// Render error with code and message
function returnError($code, $message, $config) {
	renderResponse($code, $config["error_response_root"], $message, $config);
}

// Render missing parameter error
function returnErrorMissingParameter($parameter, $config) {
	returnError(400, "Missing parameter: $parameter", $config);
}

// Render wrong parameter type error
function returnErrorWrongParameterType($parameter, $config) {
	returnError(400, "Wrong parameter type: $parameter", $config);
}

// Render wrong parameter configuration error
function returnErrorWrongParameterConfiguration($parameter, $config) {
	returnError(400, "Wrong parameter configuration: $parameter", $config);
}

// Render missing header error
function returnErrorMissingHeader($header, $config) {
	returnError(400, "Missing header: $header", $config);
}

// Render wrong header type error
function returnErrorWrongHeaderType($header, $config) {
	returnError(400, "Wrong header type: $header", $config);
}

// Render wrong header configuration error
function returnErrorWrongHeaderConfiguration($header, $config) {
	returnError(400, "Wrong header configuration: $header", $config);
}

// Render internal server error
function returnInternalServerError($config) {
	returnError(500, "Internal server error", $config);
}

// Get request parameter by name
function getParam($name, $isPost = false) {
	$result = false;

	if ($isPost === false) {
		if (isset($_GET[$name])) {
			$result = $_GET[$name];	
		}
	} else {
		if (isset($_POST[$name])) {
			$result = $_POST[$name];	
		}
	}
	
	if (strlen($result) < 1) return false;
	
	return $result;
}

// Get request header by name
function getHeader($name) {
	$result = false;
	$name = "HTTP_" . strtoupper(str_replace("-", "_", $name));

	if (isset($_SERVER[$name])) {
		$result = $_SERVER[$name];	
	}
	
	if (strlen($result) < 1) return false;

	return $result;
}

// Check if string is a valid JSON
function isJson($string) {
	json_decode($string);
	return json_last_error() === JSON_ERROR_NONE;
}

// Validate and process request parameters
function processRequestParameters($config) {
	$result;
	
	try {
		foreach ($config[PARAM_PARAMETERS] as $parameter=>$options) {
			$value = getParam($parameter, $config[PARAM_USE_POST]);
			$type = strtolower($options[PARAM_TYPE]);

			if ($value === false && $type != TYPE_FILE)
				if (array_key_exists(PARAM_DEFAULT, $options))
					$value = $options[PARAM_DEFAULT];

			if (($value === false && $options[PARAM_IS_REQUIRED] == true) && $type != TYPE_FILE)
				returnErrorMissingParameter($parameter, $config);

			if ($type == TYPE_FILE && $_SERVER["REQUEST_METHOD"] != "POST")
				returnErrorWrongParameterType($parameter, $config);

			if ($type == TYPE_FILE && $_FILES == null)
				returnErrorMissingParameter($parameter, $config);

			if ($value !== false && ($type == TYPE_STRING)) {
				if (array_key_exists(PARAM_REGEX, $options) && preg_match($options[PARAM_REGEX], $value) == false)
					returnErrorWrongParameterType($parameter, $config);

				if (array_key_exists(PARAM_MIN, $options) && strlen($value) < intval($options[PARAM_MIN]))
					returnErrorWrongParameterType($parameter, $config);

				if (array_key_exists(PARAM_MAX, $options) && strlen($value) > intval($options[PARAM_MAX]))
					returnErrorWrongParameterType($parameter, $config);
			}

			if ($value !== false && ($type == TYPE_BOOLEAN)) {
				if (!in_array(strtolower($value), VARIANTS_BOOLEAN))
					returnErrorWrongParameterType($parameter, $config);
			}

			if ($value !== false && ($type == TYPE_INTEGER)) {
				if (!is_numeric($value))
					returnErrorWrongParameterType($parameter, $config);

				if (array_key_exists(PARAM_MIN, $options) && intval($value) < intval($options[PARAM_MIN]))
					returnErrorWrongParameterType($parameter, $config);

				if (array_key_exists(PARAM_MAX, $options) && intval($value) > intval($options[PARAM_MAX]))
					returnErrorWrongParameterType($parameter, $config);
			}
			
			if ($value !== false && ($type == TYPE_FLOAT)) {
				if (!is_numeric($value))
					returnErrorWrongParameterType($parameter, $config);

				if (array_key_exists(PARAM_MIN, $options) && floatval($value) < floatval($options[PARAM_MIN]))
					returnErrorWrongParameterType($parameter, $config);

				if (array_key_exists(PARAM_MAX, $options) && floatval($value) > floatval($options[PARAM_MAX]))
					returnErrorWrongParameterType($parameter, $config);
			}

			if ($value !== false && ($type == TYPE_JSON)) {
				if (!isJson(strtolower($value)))
					returnErrorWrongParameterType($parameter, $config);
			}

			if ($value !== false && ($type == TYPE_ARRAY)) {
				$count = count(explode($config[PARAM_ARRAY_DELIMITER], $value));
				
				if ($count == 0)
					returnErrorWrongParameterType($parameter, $config);
				
				if (array_key_exists(PARAM_MIN, $options) && $count < intval($options[PARAM_MIN]))
					returnErrorWrongParameterType($parameter, $config);
				
				if (array_key_exists(PARAM_MAX, $options) && $count > intval($options[PARAM_MAX]))
					returnErrorWrongParameterType($parameter, $config);
			}

			if ($type == TYPE_FILE) {
				/* Nothing here */
			}
			
			switch (strtolower($type)) {
				case TYPE_STRING:
					$value = strval($value);
					break;
					
				case TYPE_BOOLEAN:
					$value = boolval($value);
					break;
					
				case TYPE_INTEGER:
					$value = intval($value);
					break;
					
				case TYPE_FLOAT:
					$value = floatval($value);
					break;
				
				case TYPE_JSON:
					$value = json_decode($value);
					break;
				
				case TYPE_ARRAY:
					$value = explode($config[PARAM_ARRAY_DELIMITER], $value);
					break;
				
				case TYPE_FILE:
					$value = $_FILES[$parameter];
					break;
				
				default:
					returnErrorWrongParameterConfiguration($parameter, $config);
			}

			$result[strtolower($parameter)] = $value;
		}

	} catch (Exception $e) {
		returnInternalServerError($config);
	}
	
	return $result;
}

// Validate and process request headers
function processRequestHeaders($config) {
	$result;
	
	try {
		foreach ($config[PARAM_HEADERS] as $header=>$options) {
			$value = getHeader($header);
			$type = strtolower($options[PARAM_TYPE]);

			if ($value === false)
				if (array_key_exists(PARAM_DEFAULT, $options))
					$value = $options[PARAM_DEFAULT];

			if ($value === false && $options[PARAM_IS_REQUIRED] == true)
				returnErrorMissingHeader($header, $config);

			if ($value !== false && ($type == TYPE_STRING)) {
				if (array_key_exists(PARAM_REGEX, $options) && preg_match($options[PARAM_REGEX], $value) == false)
					returnErrorWrongHeaderType($header, $config);

				if (array_key_exists(PARAM_MIN, $options) && strlen($value) < intval($options[PARAM_MIN]))
					returnErrorWrongHeaderType($header, $config);

				if (array_key_exists(PARAM_MAX, $options) && strlen($value) > intval($options[PARAM_MAX]))
					returnErrorWrongHeaderType($header, $config);
			}

			if ($value !== false && ($type == TYPE_BOOLEAN)) {
				if (!in_array(strtolower($value), VARIANTS_BOOLEAN))
					returnErrorWrongHeaderType($header, $config);
			}

			if ($value !== false && ($type == TYPE_INTEGER)) {
				if (!is_numeric($value))
					returnErrorWrongHeaderType($header, $config);

				if (array_key_exists(PARAM_MIN, $options) && intval($value) < intval($options[PARAM_MIN]))
					returnErrorWrongHeaderType($header, $config);

				if (array_key_exists(PARAM_MAX, $options) && intval($value) > intval($options[PARAM_MAX]))
					returnErrorWrongHeaderType($header, $config);
			}
			
			if ($value !== false && ($type == TYPE_FLOAT)) {
				if (!is_numeric($value))
					returnErrorWrongHeaderType($header, $config);

				if (array_key_exists(PARAM_MIN, $options) && floatval($value) < floatval($options[PARAM_MIN]))
					returnErrorWrongHeaderType($header, $config);

				if (array_key_exists(PARAM_MAX, $options) && floatval($value) > floatval($options[PARAM_MAX]))
					returnErrorWrongHeaderType($header, $config);
			}

			if ($value !== false && ($type == TYPE_JSON)) {
				if (!isJson(strtolower($value)))
					returnErrorWrongHeaderType($header, $config);
			}

			if ($value !== false && ($type == TYPE_ARRAY)) {
				$count = count(explode($config[PARAM_ARRAY_DELIMITER], $value));
				
				if ($count == 0)
					returnErrorWrongHeaderType($header, $config);
				
				if (array_key_exists(PARAM_MIN, $options) && $count < intval($options[PARAM_MIN]))
					returnErrorWrongHeaderType($header, $config);
				
				if (array_key_exists(PARAM_MAX, $options) && $count > intval($options[PARAM_MAX]))
					returnErrorWrongHeaderType($header, $config);
			}

			switch (strtolower($type)) {
				case TYPE_STRING:
					$value = strval($value);
					break;
					
				case TYPE_BOOLEAN:
					$value = boolval($value);
					break;
					
				case TYPE_INTEGER:
					$value = intval($value);
					break;
					
				case TYPE_FLOAT:
					$value = floatval($value);
					break;
				
				case TYPE_JSON:
					$value = json_decode($value);
					break;
				
				case TYPE_ARRAY:
					$value = explode($config[PARAM_ARRAY_DELIMITER], $value);
					break;
				
				default:
					returnErrorWrongHeaderConfiguration($header, $config);
			}

			$result[$header] = $value;
		}

	} catch (Exception $e) {
		returnInternalServerError($config);
	}
	
	return $result;
}
?>