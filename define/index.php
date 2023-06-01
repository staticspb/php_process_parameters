<?php
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
?>