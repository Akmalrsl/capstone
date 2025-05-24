<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Live Sensor Graphs</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }

        .chart-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        canvas {
            width: 100% !important;
            height: auto !important;
        }

        .btn-container {
            text-align: center;
            margin-top: 30px;
        }

        .btn-container button {
            margin: 10px;
        }

        .timer-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .timer {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
        }

        .average-container {
            text-align: center;
            margin-top: 10px;
            font-size: 1.1rem;
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="mb-4 text-center">Live Sensor Data</h2>

        <div class="timer-container">
            <span id="timer" class="timer">01:00</span>
        </div>


        <div class="chart-container">
            <h5>PPG Sensor (mg/dL)</h5>
            <canvas id="ppgChart" width="800" height="250"></canvas>
            <div class="average-container">
                Average: <span id="ppgAvg">0</span>
            </div>
        </div>

        <div class="chart-container">
            <h5>ECG Sensor (bpm)</h5>
            <canvas id="ecgChart" width="800" height="250"></canvas>
            <div class="average-container">
                Average: <span id="ecgAvg">0</span>
            </div>
        </div>

        <!-- Buttons at the bottom -->
        <div class="btn-container">
            <button id="startBtn" class="btn btn-success">Start Streaming</button>
            <button id="stopBtn" class="btn btn-danger">Stop Streaming</button>
            <button id="submitBtn" class="btn btn-primary" onclick="submitAverages()">Submit</button>
        </div>
    </div>

    <script>
        const sensorData = [];
        let timeIndex = 0;
        let streamStarted = false;
        let socket = null;

        // Timer variables
        let timerInterval = null;
        let timerSeconds = 60; // 1 minute

        // Running sums and counts for averages
        let ppgSum = 0,
            ppgCount = 0;
        let ecgSum = 0,
            ecgCount = 0;

        function fixCanvasHD(canvas) {
            const ctx = canvas.getContext('2d');
            const ratio = window.devicePixelRatio || 1;
            canvas.width = canvas.clientWidth * ratio;
            canvas.height = canvas.clientHeight * ratio;
            ctx.scale(ratio, ratio);
        }

        function createChart(canvasId, label, color) {
            const canvas = document.getElementById(canvasId);
            fixCanvasHD(canvas);

            return new Chart(canvas.getContext("2d"), {
                type: "line",
                data: {
                    labels: [],
                    datasets: [{
                        label: label,
                        data: [],
                        borderColor: color,
                        borderWidth: 1,
                        tension: 0.3,
                        pointRadius: 0
                    }]
                },
                options: {
                    animation: false,
                    responsive: false,
                    scales: {
                        x: {
                            type: "linear",
                            title: {
                                display: true,
                                text: "Time (ms)"
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: "Amplitude"
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        const ppgChart = createChart("ppgChart", "PPG", "blue");
        const ecgChart = createChart("ecgChart", "ECG", "green");

        function addData(chart, x, y) {
            chart.data.labels.push(x);
            chart.data.datasets[0].data.push({
                x,
                y
            });

            if (chart.data.labels.length > 2000) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
            }

            chart.update("none");
        }

        function setButtonStates() {
            document.getElementById("startBtn").disabled = streamStarted;
            document.getElementById("stopBtn").disabled = !streamStarted;
        }

        function formatTime(seconds) {
            const m = String(Math.floor(seconds / 60)).padStart(2, '0');
            const s = String(seconds % 60).padStart(2, '0');
            return `${m}:${s}`;
        }

        function updateTimerDisplay() {
            document.getElementById("timer").textContent = formatTime(timerSeconds);
        }

        function updateAverages() {
            document.getElementById("ppgAvg").textContent = ppgCount ? Math.round(ppgSum / ppgCount) : 0;
            document.getElementById("ecgAvg").textContent = ecgCount ? Math.round(ecgSum / ecgCount) : 0;
        }

        function startTimer() {
            timerSeconds = 60;
            updateTimerDisplay();
            timerInterval = setInterval(() => {
                timerSeconds--;
                updateTimerDisplay();
                if (timerSeconds <= 0) {
                    stopStream();
                }
            }, 1000);
        }

        function stopTimer() {
            clearInterval(timerInterval);
            timerInterval = null;
        }

        function startStream() {
            if (streamStarted) return;
            streamStarted = true;
            setButtonStates();
            startTimer();

            socket = new WebSocket("ws://127.0.0.1:8000");

            socket.onmessage = (event) => {
                const line = event.data.trim(); // Expected format: "512,450,100"
                const [ppg, ecg] = line.split(",").map(Number);

                addData(ppgChart, timeIndex, ppg);
                addData(ecgChart, timeIndex, ecg);

                sensorData.push({
                    time: timeIndex,
                    ppg,
                    ecg
                });
                timeIndex++;

                // Update running sums and counts
                ppgSum += ppg;
                ppgCount++;
                ecgSum += ecg;
                ecgCount++;
                updateAverages();


            };

            socket.onerror = (err) => {
                console.error("WebSocket error:", err);
            };

            socket.onclose = () => {
                console.warn("WebSocket closed.");
                streamStarted = false;
                setButtonStates();
                stopTimer();
            };
        }

        function stopStream() {
            if (socket && socket.readyState === WebSocket.OPEN) {
                socket.close();
            }
            streamStarted = false;
            setButtonStates();
            stopTimer();
        }

        /*function submitAverages() {
            const ppgAverage = ppgCount ? Math.round(ppgSum / ppgCount) : 0;
            const ecgAverage = ecgCount ? Math.round(ecgSum / ecgCount) : 0;

            // Prepare data to send
            const data = {
                ppgAverage: ppgAverage,
                ecgAverage: ecgAverage
            };

            // Example: POST to /submit-averages (adjust URL as needed)
            fetch('/submit-averages', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    if (response.ok) {
                        alert('Averages submitted successfully!');
                    } else {
                        alert('Failed to submit averages.');
                    }
                })
                .catch(error => {
                    alert('Error submitting averages: ' + error);
                });
        }*/

        function submitAverages() {
            const ppgAverage = ppgCount ? Math.round(ppgSum / ppgCount) : 0;
            const ecgAverage = ecgCount ? Math.round(ecgSum / ecgCount) : 0;

            // Prepare data to send
            const data = {
                ppgAverage: ppgAverage,
                ecgAverage: ecgAverage
            };

            fetch('http://capstonespring2025.duckdns.org:8080/capstonepanel2025/upload.php', {
                
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.text())
                .then(result => {
                    alert(result);
                })
                .catch(error => {
                    alert('Error submitting averages: ' + error);
                });
        }


        document.getElementById("startBtn").addEventListener("click", startStream);
        document.getElementById("stopBtn").addEventListener("click", stopStream);

        // Set initial button states, timer, and averages on page load
        setButtonStates();
        updateTimerDisplay();
        updateAverages();
    </script>
</body>

</html>