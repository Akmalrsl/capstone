<!DOCTYPE html>
<html lang="en">



<head>
    <meta charset="UTF-8" />
    <title>Live Sensor Graphs</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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
            font-size: 1.1rem;
            color: #0d6efd;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            margin-bottom: 15px;
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
            <h5>ECG Sensor (bpm)</h5>
            <canvas id="ecgChart" width="800" height="250"></canvas>
        </div>

        <div class="chart-container">
            <h5>PPG Sensor</h5>
            <canvas id="ppgChart" width="800" height="250"></canvas>
        </div>

        <!-- Averages, Systolic & Diastolic readings -->
        <div class="average-container">
            <div>
                Systolic: <span id="systolicReading">0</span>
            </div>
            <div>
                Diastolic: <span id="diastolicReading">0</span>
            </div>
            <div>
                Average: <span id="ecgAvg">0</span>
            </div>
        </div>

        <!-- Buttons -->
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
        let timerSeconds = 60;

        let ecgSum = 0,
            ecgCount = 0;
        let systolicSum = 0,
            systolicCount = 0;
        let diastolicSum = 0,
            diastolicCount = 0;

        // Example formulas to calculate systolic and diastolic from ptt
        // Replace with your actual formulas
        function calculateSystolic(ptt) {
            // For example, systolic decreases as ptt increases
            return Math.round(210 - (ptt * 0.6));
        }

        function calculateDiastolic(ptt) {
            // For example, diastolic decreases as ptt increases but less than systolic
            return Math.round(120 - (ptt * 0.3));
        }

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
                                text: "Time (s)"
                            },
                            ticks: {
                                callback: function(value) {
                                    return (value / 100).toFixed(1) + 's';
                                }
                            },
                            min: (ctx) => {
                                const d = ctx.chart.data.datasets[0].data;
                                return d.length > 0 ? d[d.length - 1].x - 300 : 0;
                            },
                            max: (ctx) => {
                                const d = ctx.chart.data.datasets[0].data;
                                return d.length > 0 ? d[d.length - 1].x : 0;
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

        const ecgChart = createChart("ecgChart", "ECG", "green");
        const ppgChart = createChart("ppgChart", "PPG", "blue");

        function addData(chart, x, y) {
            chart.data.labels.push(x);
            chart.data.datasets[0].data.push({
                x,
                y
            });

            if (chart.data.labels.length > 1000) {
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
            const ecgAvg = ecgCount ? Math.round(ecgSum / ecgCount) : 0;
            const systolicAvg = systolicCount ? Math.round(systolicSum / systolicCount) : 0;
            const diastolicAvg = diastolicCount ? Math.round(diastolicSum / diastolicCount) : 0;

            document.getElementById("ecgAvg").textContent = ecgAvg;
            document.getElementById("systolicReading").textContent = systolicAvg;
            document.getElementById("diastolicReading").textContent = diastolicAvg;
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
                // Expected data format: "ecg,ppg,ptt"
                const parts = event.data.trim().split(",");
                const ecg = Number(parts[0]);
                const ppg = Number(parts[1]);
                const ptt = Number(parts[2]);

                addData(ecgChart, timeIndex, ecg);
                addData(ppgChart, timeIndex, ppg);

                // Only proceed if ptt is a valid number
                if (!isNaN(ptt)) {
                    const systolic = calculateSystolic(ptt);
                    const diastolic = calculateDiastolic(ptt);

                    sensorData.push({
                        time: timeIndex,
                        ecg,
                        ppg,
                        ptt,
                        systolic,
                        diastolic
                    });

                    systolicSum += systolic;
                    systolicCount++;

                    diastolicSum += diastolic;
                    diastolicCount++;
                } else {
                    // Still store ECG and PPG without BP
                    sensorData.push({
                        time: timeIndex,
                        ecg,
                        ppg,
                        ptt: null,
                        systolic: null,
                        diastolic: null
                    });
                }

                ecgSum += ecg;
                ecgCount++;

                updateAverages();
                timeIndex++;
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

        function submitAverages() {
            const ecgAverage = ecgCount ? Math.round(ecgSum / ecgCount) : 0;
            const systolicAverage = systolicCount ? Math.round(systolicSum / systolicCount) : 0;
            const diastolicAverage = diastolicCount ? Math.round(diastolicSum / diastolicCount) : 0;

            const data = {

                /*
                ecgAverage: ecgAverage,
                systolicAverage: systolicAverage,
                diastolicAverage: diastolicAverage
                */

                ecgAverage: 1,
                systolicAverage: 1,
                diastolicAverage: 1
            };

            fetch('http://capstonespring2025.duckdns.org/capstonepanel2025/upload.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.text())
                .then(result => alert(result))
                .catch(error => alert('Error submitting averages: ' + error));
        }

        document.getElementById("startBtn").addEventListener("click", startStream);
        document.getElementById("stopBtn").addEventListener("click", stopStream);

        setButtonStates();
        updateTimerDisplay();
        updateAverages();
    </script>
</body>

</html>