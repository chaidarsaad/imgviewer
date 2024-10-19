<?php
////////////////////////////////////////////////////////////////////////////////////////
// search.php
// ----------
// accepts POST and GET for various parameters and searches the current Orthanc database
// list the patients with links to open up the relevant studies in loadViewer.php
//
// examples:
// - search.php?PatientName=A*
// - search.php?PatientName=A*&&Modality=CT&&StudyDate=2014*
//
// emsy chan
////////////////////////////////////////////////////////////////////////////////////////

//Global params
include('config.php');

//Function to generate $data to be sent to curlSetOpt_PostFields. 
//This is similar to http_build_query, but I couldn't find a similar one for use with CURL, so I wrote my own.
function createCurlPostFieldsData($inputArray) {
	$string = "{";
	foreach ($inputArray as $tag => $value) {
		$string = $string . "\"" . $tag . "\":\"" . $value . "\",";
		}
	$string = rtrim($string, ',');
	$string = $string . "}";
	return $string;
	}

//Draw Header
$orthancInfo = json_decode(file_get_contents($orthanc . "/system"), TRUE);

echo "<center>\n";
echo "<font size=+2>Isengard Browser</font><br>\n";
echo "Current Orthanc Database: " . $orthancInfo['Name'] . "<br><br>";

//Draw Search Box

echo "<table width=500 cellpadding=4><form action=search.php method=POST>\n";
echo "<tr bgcolor=ddddff><td colspan=4 align=center>Search</td></tr>\n";
echo "<tr><td align=right>Patient Name:</td><td><input type=text size=30 name=\"PatientName\" value=\"" . $_POST['PatientName'] . "\"></td>";
echo "<td align=right>StudyDate:</td><td><input type=text size=10 name=\"StudyDate\" value=\"" . $_POST['StudyDate'] . "\"></td></tr>";
echo "<tr><td align=right>Patient ID:</td><td><input type=text size=10 name=\"PatientID\" value=\"" . $_POST['PatientID'] . "\"></td>";
echo "<td align=right>Modality:</td><td><input type=text size=3 name=\"Modality\" value=\"" . $_POST['Modality'] . "\"></td></tr>";
echo "<tr><td colspan=4 align=center><input type=submit value=\"Search\"></td></tr>\n";
echo "</form></table><br>\n";

//Populate Search Parameters through POST or GET
if ($_POST['PatientName']) { $_GET['PatientName'] = $_POST['PatientName']; }
if ($_POST['PatientID']) { $_GET['PatientID'] = $_POST['PatientID']; }
if ($_POST['StudyDate']) { $_GET['StudyDate'] = $_POST['StudyDate']; }
if ($_POST['Modality']) { $_GET['Modality'] = $_POST['Modality']; }

if (!$_GET['PatientName']) { $_GET['PatientName'] = "*"; }
if (!$_GET['PatientID']) { $_GET['PatientID'] = "*"; }
if (!$_GET['StudyDate']) { $_GET['StudyDate'] = "*"; }
if (!$_GET['Modality']) { $_GET['Modality'] = "*"; }

$dicomTags = array (
	"PatientName" => $_GET['PatientName'],
	"PatientID" => $_GET['PatientID'],
	"StudyDate" => $_GET['StudyDate'],
	"Modality" => $_GET['Modality']
	);

//Don't allow empty searches - Comment out if want to allow
if ($_GET['PatientName'] == "*" && $_GET['PatientID'] == "*" && $_GET['StudyDate'] == "*" && $_GET['Modality'] == "*") {
	die('<b>No Search Parameters Defined</b>');
	}

$data = createCurlPostFieldsData($dicomTags);


$curl = curl_init();
curl_setopt ($curl, CURLOPT_URL, $orthanc . '/modalities/local/find');
curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt ($curl, CURLOPT_POST, 1);
curl_setopt ($curl, CURLOPT_POSTFIELDS, $data);
$resp = curl_exec($curl);
curl_close($curl);

$result = json_decode($resp, TRUE);

//echo "<pre>";
//var_dump($result);
//$lineColor = "ddddff";

echo "<table width=1000 cellpadding=5 cellspacing=0>\n";
echo "<tr bgcolor=ddddff><td><b>Patient Name</td><td><b>PatientID</td>
<td><b>Study Date</td><td><b>Modality</td>
<td><b>Study Description</td><td><b>Series</td></tr>";
foreach ($result as $patient) {
	if (count($patient['Studies']) > 0) {
		foreach ($patient['Studies'] as $study) {
			if (count($study['Series']) > 0) {
				if ($patient['PatientID'] != $previousPatientID) {
					echo "<tr><td colspan=6><hr size=1></td></tr>";
//					if ($lineColor == "ddddff") { $lineColor = "ddffdd"; } else { $lineColor = "ddddff"; }
//					echo "<tr bgcolor=" . $lineColor . ">";

					echo "<tr><td valign=top><a href=loadViewer.php?patient=" . $patient['PatientID'] . " target=\"_blank\">" . $patient['PatientName'] . "</a></td>";
					echo "<td valign=top>" . $patient['PatientID'] . "</td>";
					$previousPatientID = $patient['PatientID'];
					} else {
//					echo "<tr bgcolor=" . $lineColor . ">";
					echo "<tr><td></td><td></td>";
					}
				echo "<td valign=top>" . $study['StudyDate'] . "</td>";
				echo "<td valign=top>" . $study['Series'][0]['Modality'] . "</td>";
				echo "<td valign=top><a href=loadViewer.php?study=" . $study['StudyInstanceUID'] . " target=\"_blank\">" . $study['StudyDescription'] . "</a></td>";

				echo "<td valign=top>";
				foreach ($study['Series'] as $series) {
					echo "<a href=loadViewer.php?series=" . $series['SeriesInstanceUID'] . " target=\"_blank\">" . $series['SeriesDescription'] . "</a>";
					echo "<br>\n";
					}
				echo "</td></tr>";
				}
			}
		}
	}
echo "<tr><td colspan=6><hr size=1></td></tr>";
echo "</table>";
/*
$patientUUID = $patient[0]["ID"];

if ($patientUUID != "") {
	echo "<a href=\"" . $orthanc . "/app/explorer.html#patient?uuid=" . $patientUUID . "\">Open " . $data . " in Orthanc</a>";
	} else {
	echo "Patient Not Found";
	}
*/
?>

