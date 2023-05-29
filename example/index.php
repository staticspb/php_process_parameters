<?php
include "../process_parameters.php";

try {
	// Load request parameters
	$parameters = processRequestParameters($config);

	// Load request headers
	$headers = processRequestHeaders($config);

	// Render response
	returnResult(["processed_parameters" => $parameters, "processed_headers" => $headers], $config);

} catch (Exception $e) {
	returnInternalServerError($config);
}
?>