<?php
// Load config
include "config/index.php";

// Define required variables
define("PHP_ERROR_REPORTING", "php_error_reporting");
define("PHP_SET_TIME_LIMIT", "php_set_time_limit");
define("PHP_MEMORY_LIMIT", "php_memory_limit");
define("PHP_INI_MEMORY_LIMIT", "memory_limit");
define("PHP_REQUEST_METHOD", "REQUEST_METHOD");
define("PHP_REQUEST_POST", "POST");
define("PHP_FILE_NAME", "name");
define("PHP_FILE_SIZE", "size");

define("TYPE_STRING", "string");
define("TYPE_FLOAT", "float");
define("TYPE_INTEGER", "integer");
define("TYPE_BOOLEAN", "boolean");
define("TYPE_ARRAY", "array");
define("TYPE_JSON", "json");
define("TYPE_FILE", "file");

define("VARIANTS_BOOLEAN", ["true", "false"]);

define("PARAM_RESPONSE_PARAMETERS", "response_parameters");
define("PARAM_RESPONSE_HEADERS", "response_headers");
define("PARAM_RESPONSE_ADD_HEADERS", "response_add_headers");
define("PARAM_RESULT_CODE_ROOT", "result_code_root");
define("PARAM_SUCCESSFUL_RESPONSE_ROOT", "successful_response_root");
define("PARAM_ERROR_RESPONSE_ROOT", "error_response_root");
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

define("INPUT_PARAMETERS", 0);
define("INPUT_HEADERS", 1);

define("HTTP_HEADER_PREFIX", "HTTP_");
define("HTTP_RESPONSE_200", 200);
define("HTTP_ERROR_400", 400);
define("HTTP_ERROR_500", 500);

// Suppress errors
//error_reporting($config[PHP_ERROR_REPORTING]);

// Disable time limit
set_time_limit($config[PHP_SET_TIME_LIMIT]);
ini_set(PHP_INI_MEMORY_LIMIT, $config[PHP_MEMORY_LIMIT]);

// Add required headers to output
function addHeader($code, $config) {
	http_response_code($code);
	for ($i=0; $i<count($config[PARAM_RESPONSE_ADD_HEADERS]); $i++) {
		header($config[PARAM_RESPONSE_ADD_HEADERS][$i]);
	}
}

// Render response
function renderResponse($code, $rootName, $rootValue, $config) {
	addHeader($code, $config);
	
	$response = [
		$config[PARAM_RESULT_CODE_ROOT] => $code,
		"$rootName" => $rootValue
	];
	
	echo json_encode($response);
	
	exit;
}

// Render result
function returnResult($body, $config) {
	renderResponse(HTTP_RESPONSE_200, $config[PARAM_SUCCESSFUL_RESPONSE_ROOT], $body, $config);
}

// Render error with code and message
function returnError($code, $message, $config) {
	renderResponse($code, $config[PARAM_ERROR_RESPONSE_ROOT], $message, $config);
}

// Render missing parameter error
function returnErrorMissingParameter($parameter, $config) {
	returnError(HTTP_ERROR_400, "Missing parameter: $parameter", $config);
}

// Render wrong parameter type error
function returnErrorWrongParameterType($parameter, $config) {
	returnError(HTTP_ERROR_400, "Wrong parameter type: $parameter", $config);
}

// Render wrong parameter configuration error
function returnErrorWrongParameterConfiguration($parameter, $config) {
	returnError(HTTP_ERROR_400, "Wrong parameter configuration: $parameter", $config);
}

// Render missing header error
function returnErrorMissingHeader($header, $config) {
	returnError(HTTP_ERROR_400, "Missing header: $header", $config);
}

// Render wrong header type error
function returnErrorWrongHeaderType($header, $config) {
	returnError(HTTP_ERROR_400, "Wrong header type: $header", $config);
}

// Render wrong header configuration error
function returnErrorWrongHeaderConfiguration($header, $config) {
	returnError(HTTP_ERROR_400, "Wrong header configuration: $header", $config);
}

// Render internal server error
function returnInternalServerError($config) {
	returnError(HTTP_ERROR_500, "Internal server error", $config);
}

// Render wrong type error for input type
function returnErrorWrongType($name, $config, $inputType) {
	switch ($inputType) {
		case INPUT_PARAMETERS:
			returnErrorWrongParameterType($name, $config);
			break;

		case INPUT_HEADERS:
			returnErrorWrongHeaderType($name, $config);
			break;
		
		default:
			returnInternalServerError($config);
	}
}

// Render missing error for input type
function returnErrorMissing($name, $config, $inputType) {
	switch ($inputType) {
		case INPUT_PARAMETERS:
			returnErrorMissingParameter($name, $config);
			break;

		case INPUT_HEADERS:
			returnErrorMissingHeader($name, $config);
			break;

		default:
			returnInternalServerError($config);
	}
}

// Render wrong configuration error for input type
function returnErrorConfiguration($name, $config, $inputType) {
	switch ($inputType) {
		case INPUT_PARAMETERS:
			returnErrorWrongParameterConfiguration($name, $config);
			break;

		case INPUT_HEADERS:
			returnErrorWrongHeaderConfiguration($name, $config);
			break;

		default:
			returnInternalServerError($config);
	}
}

// Get request parameter by name
function getParam($name, $isPost = false) {
	$result = null;

	if ($isPost === false) {
		if (isset($_GET[$name])) {
			$result = $_GET[$name];	
		}
	} else {
		if (isset($_POST[$name])) {
			$result = $_POST[$name];	
		}
	}
	
	if (strlen($result) == 0) return false;
	
	return $result;
}

// Get request header by name
function getHeader($name) {
	$result = null;
	$name = HTTP_HEADER_PREFIX . strtoupper(str_replace("-", "_", $name));

	if (isset($_SERVER[$name])) {
		$result = $_SERVER[$name];	
	}
	
	if (strlen($result) == 0) return false;

	return $result;
}

// Check if string is a valid JSON
function isJson($string) {
	json_decode($string);
	return json_last_error() === JSON_ERROR_NONE;
}

// Process parameters or headers
function processRequestInput($config, $input, $inputType) {
	$result = [];
	
	try {
		foreach ($input as $parameter=>$options) {
			if ($inputType == INPUT_PARAMETERS)
				$value = getParam($parameter, $config[PARAM_USE_POST]);
			else 
				$value = getHeader($parameter);
			
			$type = strtolower($options[PARAM_TYPE]);

			if (($value == null && $options[PARAM_IS_REQUIRED] == true) && ($inputType == INPUT_PARAMETERS && $type != TYPE_FILE))
				returnErrorMissing($parameter, $config, $inputType);
			
			if ($value == null & $type != TYPE_FILE)
				continue;

			if ($type != TYPE_FILE)
				if (array_key_exists(PARAM_DEFAULT, $options))
					$value = $options[PARAM_DEFAULT];

			if ($inputType == INPUT_PARAMETERS && ($type == TYPE_FILE && $_SERVER[PHP_REQUEST_METHOD] != PHP_METHOD_POST))
				returnErrorWrongType($parameter, $config, $inputType);

			if ($inputType == INPUT_PARAMETERS && ($type == TYPE_FILE && $_FILES == null))
				returnErrorMissing($parameter, $config, $inputType);

			if ($type == TYPE_STRING) {
				if (array_key_exists(PARAM_REGEX, $options) && preg_match($options[PARAM_REGEX], $value) == false)
					returnErrorWrongType($parameter, $config, $inputType);

				if (array_key_exists(PARAM_MIN, $options) && strlen($value) < intval($options[PARAM_MIN]))
					returnErrorWrongType($parameter, $config, $inputType);

				if (array_key_exists(PARAM_MAX, $options) && strlen($value) > intval($options[PARAM_MAX]))
					returnErrorWrongType($parameter, $config, $inputType);
			}

			switch (strtolower($type)) {
				
				case TYPE_STRING:
					if (array_key_exists(PARAM_REGEX, $options) && preg_match($options[PARAM_REGEX], $value) == false)
						returnErrorWrongType($parameter, $config);

					if (array_key_exists(PARAM_MIN, $options) && strlen($value) < intval($options[PARAM_MIN]))
						returnErrorWrongType($parameter, $config);

					if (array_key_exists(PARAM_MAX, $options) && strlen($value) > intval($options[PARAM_MAX]))
						returnErrorWrongType($parameter, $config);					

					$value = strval($value);
					break;
					
				case TYPE_BOOLEAN:
					if (!in_array(strtolower($value), VARIANTS_BOOLEAN))
						returnErrorWrongType($parameter, $config, $inputType);

					$value = boolval($value);
					break;
				
				case TYPE_INTEGER:
					if (!is_numeric($value))
						returnErrorWrongType($parameter, $config, $inputType);

					if (array_key_exists(PARAM_MIN, $options) && intval($value) < intval($options[PARAM_MIN]))
						returnErrorWrongType($parameter, $config, $inputType);

					if (array_key_exists(PARAM_MAX, $options) && intval($value) > intval($options[PARAM_MAX]))
						returnErrorWrongType($parameter, $config, $inputType);

					$value = intval($value);
					break;
				
				case TYPE_FLOAT:
					if (!is_numeric($value))
						returnErrorWrongType($parameter, $config, $inputType);

					if (array_key_exists(PARAM_MIN, $options) && floatval($value) < floatval($options[PARAM_MIN]))
						returnErrorWrongType($parameter, $config, $inputType);

					if (array_key_exists(PARAM_MAX, $options) && floatval($value) > floatval($options[PARAM_MAX]))
						returnErrorWrongType($parameter, $config, $inputType);

					$value = floatval($value);
					break;
					
				case TYPE_ARRAY:
					$count = count(explode($config[PARAM_ARRAY_DELIMITER], $value));
					
					if ($count == 0)
						returnErrorWrongType($parameter, $config, $inputType);
					
					if (array_key_exists(PARAM_MIN, $options) && $count < intval($options[PARAM_MIN]))
						returnErrorWrongType($parameter, $config, $inputType);
						
					
					if (array_key_exists(PARAM_MAX, $options) && $count > intval($options[PARAM_MAX]))
						returnErrorWrongType($parameter, $config, $inputType);
					
					$array = explode($config[PARAM_ARRAY_DELIMITER], $value);
					
					if (array_key_exists(PARAM_REGEX, $options)) {
						for ($i=0; $i<count($array); $i++) {
							if (preg_match($options[PARAM_REGEX], $array[$i]) == false)
								returnErrorWrongType($parameter, $config);
						}
					}
					
					$value = $array;
					break;
					
				case TYPE_JSON:
					if (!isJson(strtolower($value)))
						returnErrorWrongType($parameter, $config, $inputType);
					else
						$value = json_decode($value);

					break;
					
				case TYPE_FILE:
					if ($inputType == INPUT_PARAMETERS) {
						$value = $_FILES[$parameter];
						
						if (array_key_exists(PARAM_REGEX, $options) && preg_match($options[PARAM_REGEX], $value[PHP_FILE_NAME]) == false)
							returnErrorWrongType($parameter, $config);

						if (array_key_exists(PARAM_MIN, $options) && intval($value[PHP_FILE_SIZE]) < intval($options[PARAM_MIN]))
							returnErrorWrongType($parameter, $config, $inputType);

						if (array_key_exists(PARAM_MAX, $options) && intval($value[PHP_FILE_SIZE]) > intval($options[PARAM_MAX]))
							returnErrorWrongType($parameter, $config, $inputType);
						
					} else {
						returnErrorWrongType($parameter, $config, $inputType);
					}

					break;

				default:
					returnErrorConfiguration($parameter, $config, $inputType);
			}

			if ($inputType == INPUT_PARAMETERS)
				$result[strtolower($parameter)] = $value;
			else
				$result[$parameter] = $value;
		}

	} catch (Exception $e) {
		returnInternalServerError($config);
	}
	
	return $result;
}

// Process both parameters and headers
function processRequest($config) {
	return [
		$config[PARAM_RESPONSE_PARAMETERS] => processRequestInput($config, $config[PARAM_PARAMETERS], INPUT_PARAMETERS), 
		$config[PARAM_RESPONSE_HEADERS] => processRequestInput($config, $config[PARAM_HEADERS], INPUT_HEADERS)
	];
}
?>