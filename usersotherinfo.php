<?php
// Connect to DB
include 'db_connect.php';

// Fetch reading IDs from the 'readings' table
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
    function fetchECGAverage(readingId) {
        if (!readingId) return;

        const xhr = new XMLHttpRequest();
        xhr.open("GET", "get_ecgaverage.php?id=" + readingId, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                const ecg = xhr.responseText;
                document.getElementById("ecgAverage").value = ecg;
                document.getElementById("ecgAverageHidden").value = ecg;
            }
        };
        xhr.send();
    }
    </script>
</head>
<body class="bg-light">

<!-- Success Popup -->
<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
<script>
    alert("✅ Data successfully submitted!");
    if (history.replaceState) {
        history.replaceState(null, null, window.location.pathname);
    }
</script>
<?php endif; ?>

<!-- Navbar -->
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

<!-- Form -->
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
            <input type="number" class="form-control" name="cholesterol" required>
        </div>

        <button type="submit" class="btn btn-success">Submit</button>
    </form>
</div>

</body>
</html>
