<?php
include "../process_parameters.php";

try {
	// Process request data
	$requestData = processRequest($config);
	// Render response
	returnResult($requestData, $config);

} catch (Exception $e) {
	returnInternalServerError($config);
}
?>