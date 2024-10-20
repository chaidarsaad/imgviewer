<!DOCTYPE html>
<html>

<head>
	<title>Orthanc DICOM Viewer by Patient ID</title>
	<style>
		/* Styling untuk membuat gambar responsif */
		img {
			max-width: 100%;
			/* Gambar akan menyesuaikan lebar layar */
			height: auto;
			/* Tinggi gambar akan otomatis mengikuti rasio */
			display: block;
			/* Menghilangkan jarak bawah (jika ada) */
			margin: 10px 0;
			/* Memberi jarak antara gambar dengan elemen lainnya */
		}

		/* Styling untuk container agar konten terpusat */
		.container {
			max-width: 1000px;
			/* Batas lebar maksimal container */
			margin: 0 auto;
			/* Agar container berada di tengah */
			padding: 20px;
		}

		h1,
		h2,
		h3,
		h4 {
			text-align: center;
		}

		form {
			text-align: center;
			margin-bottom: 20px;
		}

		input[type="text"] {
			padding: 8px;
			font-size: 16px;
			width: 300px;
		}

		button {
			padding: 8px 15px;
			font-size: 16px;
			cursor: pointer;
		}
	</style>
</head>

<body>

	<div class="container">
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
	</div>

</body>

</html>