<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Health Info</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2>Submit Your Health Information</h2>
        <form action="submit_health.php" method="POST" class="mt-4">
            <div class="mb-3">
                <label for="sensor_id" class="form-label">ECG Reading ID (from device):</label>
                <input type="number" class="form-control" name="sensor_id" required>
            </div>
            <div class="mb-3">
                <label for="age" class="form-label">Age:</label>
                <input type="number" class="form-control" name="age" required>
            </div>
            <div class="mb-3">
                <label for="gender" class="form-label">Gender:</label>
                <select class="form-control" name="gender" required>
                    <option value="">Select...</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="height" class="form-label">Height (in cm):</label>
                <input type="text" class="form-control" name="height" required>
            </div>
            <div class="mb-3">
                <label for="weight" class="form-label">Weight (in kg):</label>
                <input type="text" class="form-control" name="weight" required>
            </div>
            <div class="mb-3">
                <label for="cholesterol" class="form-label">Cholesterol Level:</label>
                <input type="number" class="form-control" name="cholesterol" required>
            </div>
            <button type="submit" class="btn btn-success">Submit Data</button>
        </form>
    </div>
</body>
</html>
