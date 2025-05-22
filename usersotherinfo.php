<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Other Health Info</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Show popup if redirected with ?success=1 -->
<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
<script>
    alert("✅ Data successfully submitted!");
    // Remove ?success=1 from the URL
    if (history.replaceState) {
        history.replaceState(null, null, window.location.pathname);
    }
</script>
<?php endif; ?>

<!-- Navbar copied from dashboard.php -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Healthmate</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
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

<!-- Form Section -->
<div class="container mt-5">
    <h2 class="mb-4">Submit Your Health Info</h2>
    <form action="process_userinfo.php" method="POST">
        <div class="mb-3">
            <label for="sensor_id" class="form-label">ECG Reading ID (from device):</label>
            <input type="number" class="form-control" name="sensor_id" required>
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
            <label class="form-label">Height (in cm):</label>
            <input type="text" class="form-control" name="height" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Weight (in kg):</label>
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
