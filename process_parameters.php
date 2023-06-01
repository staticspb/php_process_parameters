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
define("INPUT_PARAMETERS", 0);
define("INPUT_HEADERS", 1);

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
	
	if (strlen($result) == 0) return false;
	
	return $result;
}

// Get request header by name
function getHeader($name) {
	$result = false;
	$name = "HTTP_" . strtoupper(str_replace("-", "_", $name));

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
	$result;
	
	try {
		foreach ($input as $parameter=>$options) {
			if ($inputType == INPUT_PARAMETERS)
				$value = getParam($parameter, $config[PARAM_USE_POST]);
			else 
				$value = getHeader($parameter);
			
			$type = strtolower($options[PARAM_TYPE]);

			if ($value === false && $type != TYPE_FILE)
				if (array_key_exists(PARAM_DEFAULT, $options))
					$value = $options[PARAM_DEFAULT];

			if (($value === false && $options[PARAM_IS_REQUIRED] == true) && ($inputType == INPUT_PARAMETERS && $type != TYPE_FILE))
				returnErrorMissing($parameter, $config, $inputType);

			if ($inputType == INPUT_PARAMETERS && ($type == TYPE_FILE && $_SERVER["REQUEST_METHOD"] != "POST"))
				returnErrorWrongType($parameter, $config, $inputType);

			if ($inputType == INPUT_PARAMETERS && ($type == TYPE_FILE && $_FILES == null))
				returnErrorMissing($parameter, $config, $inputType);

			if ($value !== false && ($type == TYPE_STRING)) {
				if (array_key_exists(PARAM_REGEX, $options) && preg_match($options[PARAM_REGEX], $value) == false)
					returnErrorWrongType($parameter, $config, $inputType);

				if (array_key_exists(PARAM_MIN, $options) && strlen($value) < intval($options[PARAM_MIN]))
					returnErrorWrongType($parameter, $config, $inputType);

				if (array_key_exists(PARAM_MAX, $options) && strlen($value) > intval($options[PARAM_MAX]))
					returnErrorWrongType($parameter, $config, $inputType);
			}

			if ($value !== false) {
				switch (strtolower($type)) {
					
					case TYPE_STRING:
						if (array_key_exists(PARAM_REGEX, $options) && preg_match($options[PARAM_REGEX], $value) == false)
							returnErrorWrongParameterType($parameter, $config);

						if (array_key_exists(PARAM_MIN, $options) && strlen($value) < intval($options[PARAM_MIN]))
							returnErrorWrongParameterType($parameter, $config);

						if (array_key_exists(PARAM_MAX, $options) && strlen($value) > intval($options[PARAM_MAX]))
							returnErrorWrongParameterType($parameter, $config);					

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
									returnErrorWrongParameterType($parameter, $config);
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
						if ($inputType != INPUT_PARAMETERS)
							returnErrorWrongType($parameter, $config, $inputType);
						else
							$value = $_FILES[$parameter];

						break;

					default:
						returnErrorWrongConfiguration($parameter, $config, $inputType);
				}
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
	$parameters = processRequestInput($config, $config[PARAM_PARAMETERS], INPUT_PARAMETERS);
	$headers = processRequestInput($config, $config[PARAM_HEADERS], INPUT_HEADERS);
	return ["parameters" => $parameters, "headers" => $headers];
}
?>