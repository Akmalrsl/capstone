<!DOCTYPE html>
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
?>
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
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4 text-center">Live Sensor Data</h2>

        <div class="chart-container">
            <h5>PCG Sensor</h5>
            <canvas id="pcgChart" width="800" height="250"></canvas>
        </div>

        <div class="chart-container">
            <h5>ECG Sensor</h5>
            <canvas id="ecgChart" width="800" height="250"></canvas>
        </div>

        <div class="chart-container">
            <h5>GSR Sensor</h5>
            <canvas id="gsrChart" width="800" height="250"></canvas>
        </div>

        <div class="btn-container">
            <button id="startBtn" class="btn btn-success">Start Streaming</button>
            <button id="downloadBtn" class="btn btn-primary" onclick="downloadCSV()">Download CSV</button>
        </div>
    </div>

    <script>
        const sensorData = [];
        let timeIndex = 0;
        let streamStarted = false;

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

        const pcgChart = createChart("pcgChart", "PCG", "blue");
        const ecgChart = createChart("ecgChart", "ECG", "green");
        const gsrChart = createChart("gsrChart", "GSR", "red");

        function addData(chart, x, y) {
            chart.data.labels.push(x);
            chart.data.datasets[0].data.push({ x, y });

            if (chart.data.labels.length > 2000) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
            }

            chart.update("none");
        }

        function startStream() {
            if (streamStarted) return;
            streamStarted = true;

            fetch("http://127.0.0.1:8000/stream-pcg")
                .then(response => response.body.getReader())
                .then(reader => {
                    const decoder = new TextDecoder("utf-8");

                    function read() {
                        reader.read().then(({ done, value }) => {
                            if (done) return console.log("Stream closed.");

                            const text = decoder.decode(value);
                            const lines = text.trim().split("\n");

                            lines.forEach(line => {
                                const [pcg, ecg, gsr] = line.split(",").map(parseFloat);
                                addData(pcgChart, timeIndex, pcg);
                                addData(ecgChart, timeIndex, ecg);
                                addData(gsrChart, timeIndex, gsr);

                                sensorData.push({ time: timeIndex, pcg, ecg, gsr });

                                timeIndex++;
                            });

                            requestAnimationFrame(read);
                        });
                    }

                    read();
                })
                .catch(err => console.error("Stream error:", err));
        }

        function downloadCSV() {
            const rows = [
                ["Time(ms)", "PCG", "ECG", "GSR"],
                ...sensorData.map(d => [d.time, d.pcg, d.ecg, d.gsr])
            ];

            const csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "sensor_data.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        document.getElementById("startBtn").addEventListener("click", startStream);
    </script>
</body>
</html>
