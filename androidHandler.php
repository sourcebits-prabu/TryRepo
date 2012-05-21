<?php
/*
 * androidHandler.php - Entry point to manage all android devices
 * Version - 1.0 
 * 
 */

// Report all PHP errors
error_reporting(E_ALL);
ini_set('display_errors', '1');
ob_implicit_flush(true);

// Include files for staging
require_once('filter.php');
require_once('settings.php');
require_once('outputFormatter.php');
require_once('devices.php');

$filter = new filter();
$output = new outputFormatter();

$action = $filter->text($_POST['action']);

/* Based on the request, let's proceed further */
switch($action) {

	/* addDevice request will be made to add a new device or to
	   update an existing device. */ 
	case "addDevice" :
		$deviceToken = $filter->text($_POST['deviceToken']);
		$userZip = $filter->text($_POST['userZip']);
		$reportingArea  = $filter->text($_POST['reportingArea']);
		$state = $filter->text($_POST['state']);

		/* Make sure the data passed is valid */
		if (!$filter->verify($deviceToken,$userZip,$reportingArea,$state) || !$filter->checkToken($deviceToken)) {
			$output->status = false;
			$output->error = 'Invalid Arguments';
			echo $output->jsonOutput();
			$errString = "Add Device Invalid Arg - DeviceToken:".$deviceToken." UserZip:".$userZip." ReportingArea:".$reportingArea." State:".$state;
			error_log($errString,0);
			exit;
		}
		$devicesC = new androidController();
		$device = new androidDevice($deviceToken,$userZip,$reportingArea,$state);
		$status = $devicesC->updateOrAddDevice($device, $action);
		break;

	/* This will be called when Google refreshes the registration id of a device */
	case "updateDeviceToken" :
		$oldToken = $filter->text($_POST['oldToken']);
		$newToken = $filter->text($_POST['newToken']);

		if(!$filter->verify($oldToken, $newToken) || !$filter->checkToken($oldToken) || !$filter->checkToken($newToken)) {
			$output->status = false;
			$output->error = "Invalid Arguments";
			echo $output->jsonOutput();
			$errString = "UpdateToken Invalid Arg - OldToken:".$oldToken." NewToken:".$newToken;
			error_log($errString,0);
			exit;
		}
		$devicesC = new androidController();
		$status = $devicesC->updateDeviceToken($oldToken,$newToken);
		break;

	/* This will be called when a device un-registers from Google at the time of App un-install" */
	case "removeDevice" :
		$deviceToken = 	$filter->text($_POST['deviceToken']);
		if(!$filter->verify($deviceToken) || !$filter->checkToken($deviceToken)) {
			$output->status = false;
			$output->error = "Invalid Arguments";
			echo $output->jsonOutput();
			$errString = "RemoveDevice Invalid Arg - DevToken:".$deviceToken;
			error_log($errString,0);
			exit;
		}
		$devicesC = new androidController();
		$status = $devicesC->removeDevice($deviceToken);
		break;

	case "getAuth":
		$devicesC = new androidController();
		$status = $devicesC->sendNotifications();
		break;

	/* OOPS.. */
	default:
		$output->status = false;
		$output->error = "Invalid Arguments";
		echo $output->jsonOutput();	
		$errString = "Unknown Request:".$action";
		error_log($errString,0);
		exit;
} // switch

$devicesC = null;
$output->status = $status;
$output->error = ($status == false) ? 'Unable to update data' : 'None';
echo $output->jsonOutput();
?>

