<?php
$orthancUrl = 'http://localhost:8042';

function findStudiesByPatientIdAndDateRange($orthancUrl, $patientId, $startDate, $endDate)
{
	$url = $orthancUrl . '/tools/find';

	$data = json_encode([
		"Level" => "Study",
		"Query" => [
			"PatientID" => $patientId,
			"StudyDate" => [$startDate . '-' . $endDate]
		]
	]);

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$response = curl_exec($ch);
	curl_close($ch);

	return json_decode($response, true);
}

function getImagePreview($orthancUrl, $instanceId)
{
	return $orthancUrl . '/instances/' . $instanceId . '/preview';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['patient_id']) && isset($_POST['startDate']) && isset($_POST['endDate'])) {
	$patientId = $_POST['patient_id'];

	$startDate = (new DateTime($_POST['startDate']))->format('Ymd');
	$endDate = (new DateTime($_POST['endDate']))->format('Ymd');

	$studies = findStudiesByPatientIdAndDateRange($orthancUrl, $patientId, $startDate, $endDate);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Orthanc DICOM Viewer by Patient ID</title>
	<link rel="stylesheet" href="style.css">
</head>

<body>
	<div class="container">
		<form method="POST" action="">
			<label for="patient_id">Patient ID:</label>
			<input type="text" id="patient_id" name="patient_id" value="<?php echo isset($_POST['patient_id']) ? htmlspecialchars($_POST['patient_id']) : ''; ?>" required>

			<label for="startDate">Start Date:</label>
			<input type="date" id="startDate" name="startDate" value="<?php echo isset($_POST['startDate']) ? htmlspecialchars($_POST['startDate']) : ''; ?>" required>

			<label for="endDate">End Date:</label>
			<input type="date" id="endDate" name="endDate" value="<?php echo isset($_POST['endDate']) ? htmlspecialchars($_POST['endDate']) : ''; ?>" required>

			<input type="submit" value="Cari">
		</form>

		<div class="results">
			<?php if (isset($studies) && !empty($studies)): ?>
				<h1>Studi yang Ditemukan Pasien ID: <?php echo htmlspecialchars($patientId); ?></h1>
				<ul>
					<?php foreach ($studies as $studyId): ?>
						<li>
							<h3>Study ID: <?php echo $studyId; ?></h3>
							<?php
							$studyUrl = $orthancUrl . '/studies/' . $studyId;
							$ch = curl_init($studyUrl);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							$studyDetails = json_decode(curl_exec($ch), true);
							curl_close($ch);

							echo "<pre>";
							print_r($studyDetails);
							echo "</pre>";

							if (!empty($studyDetails['Series'])) {
								foreach ($studyDetails['Series'] as $seriesId) {
									$seriesUrl = $orthancUrl . '/series/' . $seriesId;
									$ch = curl_init($seriesUrl);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
									$seriesDetails = json_decode(curl_exec($ch), true);
									curl_close($ch);
									echo "<pre>";

									echo "<h4>Series ID: $seriesId</h4>";

									if (!empty($seriesDetails['Instances'])) {
										foreach ($seriesDetails['Instances'] as $instanceId) {
											echo "<p>Preview Gambar Instance ID: $instanceId</p>";
											echo "<img src='" . getImagePreview($orthancUrl, $instanceId) . "' alt='DICOM Preview'>";
										}
									} else {
										echo "<p>Tidak ada instance ditemukan untuk Series ID ini.</p>";
									}
									echo "</pre>";
								}
							} else {
								echo "<p>Tidak ada series ditemukan untuk Study ID ini.</p>";
							}
							?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php elseif (isset($patientId)): ?>
				<p>Tidak ada studi ditemukan untuk Patient ID: <?php echo htmlspecialchars($patientId); ?></p>
			<?php endif; ?>
		</div>
	</div>
</body>

</html>