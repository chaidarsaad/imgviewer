<?php
// URL server Orthanc
$orthancUrl = 'http://localhost:8042';

// Fungsi untuk mencari studi berdasarkan Patient ID
function findStudiesByPatientId($orthancUrl, $patientId)
{
	$url = $orthancUrl . '/tools/find';

	// Query untuk mencari berdasarkan PatientID
	$data = json_encode([
		"Level" => "Study",
		"Query" => [
			"PatientID" => $patientId
		]
	]);

	// Inisialisasi cURL untuk POST request
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$response = curl_exec($ch);
	curl_close($ch);

	return json_decode($response, true);
}

// Fungsi untuk menampilkan preview gambar dari instance tertentu
function getImagePreview($orthancUrl, $instanceId)
{
	return $orthancUrl . '/instances/' . $instanceId . '/preview';
}

// Jika form dikirim dengan Patient ID
if (isset($_POST['patient_id'])) {
	$patientId = $_POST['patient_id'];

	// Cari studi berdasarkan Patient ID
	$studies = findStudiesByPatientId($orthancUrl, $patientId);
}
?>

<!DOCTYPE html>
<html>

<head>
	<title>Orthanc DICOM Viewer by Patient ID</title>
</head>

<body>

	<h1>Cari Studi DICOM Berdasarkan Patient ID</h1>

	<form method="POST" action="">
		<label for="patient_id">Patient ID:</label>
		<input type="text" id="patient_id" name="patient_id" required>
		<button type="submit">Cari</button>
	</form>

	<?php if (isset($studies) && !empty($studies)): ?>
		<h2>Studi yang Ditemukan:</h2>
		<ul>
			<?php foreach ($studies as $studyId): ?>
				<li>
					<h3>Study ID: <?php echo $studyId; ?></h3>
					<!-- Ambil detail dari study -->
					<?php
					// Ambil detail dari study
					$studyUrl = $orthancUrl . '/studies/' . $studyId;
					$ch = curl_init($studyUrl);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$studyDetails = json_decode(curl_exec($ch), true);
					curl_close($ch);

					// Debug: Tampilkan seluruh detail response untuk studi ini
					echo "<pre>";
					print_r($studyDetails); // Tampilkan detail lengkap response untuk debugging
					echo "</pre>";

					// Cek apakah ada series
					if (!empty($studyDetails['Series'])) {
						foreach ($studyDetails['Series'] as $seriesId) {
							// Ambil detail series
							$seriesUrl = $orthancUrl . '/series/' . $seriesId;
							$ch = curl_init($seriesUrl);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							$seriesDetails = json_decode(curl_exec($ch), true);
							curl_close($ch);

							echo "<h4>Series ID: $seriesId</h4>";

							// Cek apakah ada instance dalam series ini
							if (!empty($seriesDetails['Instances'])) {
								foreach ($seriesDetails['Instances'] as $instanceId) {
									// Tampilkan preview gambar dari instance ini
									echo "<p>Preview Gambar Instance ID: $instanceId</p>";
									echo "<img src='" . getImagePreview($orthancUrl, $instanceId) . "' alt='DICOM Preview'>";
								}
							} else {
								echo "<p>Tidak ada instance ditemukan untuk Series ID ini.</p>";
							}
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

</body>

</html>