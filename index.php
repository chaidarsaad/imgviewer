<?php
$orthancUrl = 'http://localhost:8042';

function findStudiesByPatientIdAndDate($orthancUrl, $patientId, $studyDate)
{
	$url = $orthancUrl . '/tools/find';

	$data = json_encode([
		'Query' => [
			'PatientID' => $patientId,
			'StudyDate' => $studyDate
		],
		'Expand' => true
	]);

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$response = curl_exec($ch);
	curl_close($ch);

	if (!$response) {
		return ['error' => 'Tidak ada respons dari server Orthanc.'];
	}

	return json_decode($response, true);
}

function createDateRange($startDate, $endDate)
{
	$start = new DateTime($startDate);
	$end = new DateTime($endDate);
	$end = $end->modify('+1 day');

	$interval = new DateInterval('P1D');
	$period = new DatePeriod($start, $interval, $end);

	$dateRange = [];
	foreach ($period as $date) {
		$dateRange[] = $date->format('Ymd');
	}

	return $dateRange;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['patient_id']) && isset($_POST['startDate']) && isset($_POST['endDate'])) {
	$patientId = $_POST['patient_id'];
	$startDate = $_POST['startDate'];
	$endDate = $_POST['endDate'];

	$dates = createDateRange($startDate, $endDate);

	$results = [];
	foreach ($dates as $currentDate) {
		$dailyResults = findStudiesByPatientIdAndDate($orthancUrl, $patientId, $currentDate);

		if (!empty($dailyResults)) {
			foreach ($dailyResults as $instance) {
				$instance['SearchDate'] = $currentDate;
				$results[] = $instance;
			}
		}
	}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Orthanc DICOM Viewer by Patient ID</title>
	<link rel="stylesheet" type="text/css" href="style.css">
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
			<?php if (isset($results) && !empty($results)): ?>
				<h1>Studi yang Ditemukan Pasien ID: <?php echo htmlspecialchars($patientId); ?></h1>
				<ul>
					<?php foreach ($results as $instance): ?>
						<li>
							<h3>Instance ID: <?php echo htmlspecialchars($instance['ID']); ?> (Tanggal: <?php echo htmlspecialchars($instance['SearchDate']); ?>)</h3>
							<p>Preview Gambar: <img src="<?php echo $orthancUrl . '/instances/' . htmlspecialchars($instance['ID']) . '/preview'; ?>" alt="DICOM Preview"></p>

							<!-- Tombol Explorer dan VolView -->
							<p>
								<a href="<?php echo $orthancUrl . '/app/explorer.html#patient?uuid=' . htmlspecialchars($instance['ID']); ?>" target="_blank">
									<button>Explorer</button>
								</a>
								<a href="<?php echo $orthancUrl . '/volview/index.html?names=[archive.zip]&urls=[../studies/' . htmlspecialchars($instance['ID']) . '/archive]'; ?>" target="_blank">
									<button>VolView</button>
								</a>
							</p>
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