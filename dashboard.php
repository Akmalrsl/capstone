<?php

session_start();
//Login required
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

//Get session info
$username = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'];

//DB connection
include 'db_connect.php';
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthmate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .header-section {
            background-color: #e0e0e0;
            padding: 40px 0;
        }

        .feature-card {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .highlight-card {
            background-color: #0d6efd;
            color: white;
            border-radius: 10px;
            padding: 20px;
        }

        .user-info {
            text-align: right;
            font-size: 14px;
            margin-bottom: 10px;
            color: #555;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
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

                <!-- Replaced "Make an Appointment" with "Submit Health Info" -->
                <a href="usersotherinfo.php" class="btn btn-success ms-3">Submit Health Info</a>
            </div>
        </div>
    </nav>


    <!-- User Info -->
    <div class="container mt-2">
        <div class="user-info">
            Logged in as: <strong><?= htmlspecialchars($username) ?></strong> (<?= $role ?>)
        </div>
    </div>

    <!-- Header -->
    <section class="header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>WELCOME TO HEALTHMATE</h1>

                    <div class="highlight-card">
                        <h3>Why Choose Healthmate?</h3>
                        <p>A cost-effective, non-invasive system for early detection and continuous monitoring of hypertension,
                            heart failure, sleep apnea and anxiety using heartbeat, blood oxygen levels, and skin conductivity.</p>
                        <button class="btn btn-light">Learn More</button>
                    </div>
                </div>

                <div class="col-md-6">
                    <div style="width:100%;height:400px;background-color:#e9ecef;border-radius:10px;text-align:center;line-height:400px;color:#aaa;">
                        Image Placeholder
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="row mt-5">
                <div class="col-md-4">
                    <div class="feature-card">
                        <h5>Manual Testing</h5>
                        <p>Test yourself by entering your health data manually.</p>
                        <a href="manualTest.php" class="btn btn-primary mt-2">Start Testing</a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <h5>AI Nutritionist Chatbot</h5>
                        <p>Talk to an AI for dietary guidance.</p>
                        <a href="http://localhost/capstone/chatbot_front.php" target="_blank" class="btn btn-primary mt-2">Start Chat</a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <h5>Health Insights</h5>
                        <p>Coming soon: Personalized insights from your health data!</p>
                    </div>
                </div>
            </div>

            <!-- admin-only feature: visualize data -->
            <?php if ($role === 'admin'): ?>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="feature-card">
                            <h5>Visualize Data</h5>
                            <p>Explore trends in health data like blood pressure, cholesterol, and BMI.</p>
                            <a href="visualization.php" class="btn btn-primary mt-2">Visualize Data</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-light text-dark py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>About Healthmate</h5>
                    <p>Healthmate provides innovative solutions for early detection and monitoring of cardiovascular health through smart technology integration.</p>
                </div>

                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-dark text-decoration-none">Home</a></li>
                        <li><a href="#" class="text-dark text-decoration-none">About Us</a></li>
                        <li><a href="#" class="text-dark text-decoration-none">Services</a></li>
                        <li><a href="#" class="text-dark text-decoration-none">Doctors</a></li>
                        <li><a href="#" class="text-dark text-decoration-none">Contact</a></li>
                    </ul>
                </div>

                <div class="col-md-4 mb-3">
                    <h5>Contact Information</h5>
                    <p>
                        📍 123 Street, City, Country<br>
                        📧 contact@healthmate.com<br>
                        📞 +8210 0000 0000
                    </p>
                </div>
            </div>
            <div class="text-center border-top pt-3 mt-3">
                &copy; 2025 Healthmate. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>