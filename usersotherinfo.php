<?php
include 'db_connect.php';

//fetch reading IDs from the 'readings' table
$readingOptions = "";
$sql = "SELECT id FROM readings ORDER BY id DESC";
$result = $health_conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $readingOptions .= "<option value='{$row['id']}'>{$row['id']}</option>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Submit Other Health Info</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script>
        let ajaxReady = false;

        function fetchECGAverage(readingId) {
            if (!readingId) return;

            ajaxReady = false;

            const xhr = new XMLHttpRequest();
            xhr.open("GET", "get_reading_values.php?id=" + readingId, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    document.getElementById("ecgAverage").value = data.ecgAverage;
                    document.getElementById("ecgAverageHidden").value = data.ecgAverage;

                    document.getElementById("sbpDisplay").value = data.sbp;
                    document.getElementById("sbpHidden").value = data.sbp;

                    document.getElementById("dbpDisplay").value = data.dbp;
                    document.getElementById("dbpHidden").value = data.dbp;

                    ajaxReady = true;
                }
            };
            xhr.send();
        }

        document.addEventListener("DOMContentLoaded", function () {
            document.querySelector("form").addEventListener("submit", function (e) {
                if (!ajaxReady) {
                    e.preventDefault();
                    alert("⏳ Please wait for the blood pressure values to load before submitting.");
                }
            });
        });
    </script>
</head>

<body class="bg-light">

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <script>
            alert("✅ Data successfully submitted!");
            if (history.replaceState) {
                history.replaceState(null, null, window.location.pathname);
            }
        </script>
    <?php endif; ?>

    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Healthmate</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
                <a href="dashboard.php" class="btn btn-outline-primary ms-3">← Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="mb-4">Submit Your Health Info</h2>
        <form action="process_userinfo.php" method="POST">
            <div class="mb-3">
                <label for="sensor_id" class="form-label">Select ECG Reading ID:</label>
                <select class="form-control" name="sensor_id" onchange="fetchECGAverage(this.value)" required>
                    <option value="">-- Choose Reading ID --</option>
                    <?= $readingOptions ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">ECG Average:</label>
                <input type="text" class="form-control" id="ecgAverage" disabled>
                <input type="hidden" name="ecgAverage" id="ecgAverageHidden">

                <label class="form-label mt-3">Systolic BP (SBP):</label>
                <input type="text" class="form-control" id="sbpDisplay" disabled>
                <input type="hidden" name="sbp" id="sbpHidden">

                <label class="form-label mt-3">Diastolic BP (DBP):</label>
                <input type="text" class="form-control" id="dbpDisplay" disabled>
                <input type="hidden" name="dbp" id="dbpHidden">
            </div>



            <div class="mb-3">
                <label class="form-label">Age:</label>
                <input type="number" class="form-control" name="age" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Gender:</label>
                <select class="form-control" name="gender" required>
                    <option value="">Select...</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Height (cm):</label>
                <input type="text" class="form-control" name="height" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Weight (kg):</label>
                <input type="text" class="form-control" name="weight" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Cholesterol Level:</label>
                <select class="form-control" name="cholesterol" required>
                    <option value="">Select...</option>
                    <option value="1">1 - Normal</option>
                    <option value="2">2 - Borderline High</option>
                    <option value="3">3 - High</option>
                </select>
            </div>


            <button type="submit" class="btn btn-success">Submit</button>
        </form>
    </div>

</body>

</html>