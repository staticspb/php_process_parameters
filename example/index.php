<?php
include "../process_parameters.php";

try {
	// Render response
	$requestData = processRequest($config);
	returnResult($requestData, $config);

} catch (Exception $e) {
	returnInternalServerError($config);
}
?>