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

					// Ambil instance pertama untuk preview
					$instanceId = $studyDetails['Instances'][0]; // Mengambil instance pertama
					?>
					<p>Preview Gambar:</p>
					<img src="<?php echo getImagePreview($orthancUrl, $instanceId); ?>" alt="DICOM Preview">
				</li>
			<?php endforeach; ?>
		</ul>
	<?php elseif (isset($patientId)): ?>
		<p>Tidak ada studi ditemukan untuk Patient ID: <?php echo htmlspecialchars($patientId); ?></p>
	<?php endif; ?>

</body>

</html>