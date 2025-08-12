<!DOCTYPE html>
<html>

<head>
    <title>Container Health Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
            padding: 20px;
        }

        .chart-container {
            width: 45%;
            display: inline-block;
            vertical-align: top;
            margin: 20px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
        }
    </style>
</head>

<body>

    <h2>📊 Container Health Monitor</h2>

    <div class="chart-container">
        <canvas id="cpuChart"></canvas>
    </div>
    <div class="chart-container">
        <canvas id="ramChart"></canvas>
    </div>
    <div class="chart-container">
        <canvas id="diskChart"></canvas>
    </div>
    <div class="chart-container">
        <canvas id="netChart"></canvas>
    </div>

    <script>
        let cpuData = [],
            ramData = [],
            diskData = [],
            rxData = [],
            txData = [];
        let labels = [];

        function createChart(ctx, label, data, color) {
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        borderColor: color,
                        backgroundColor: color + '33',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        let cpuChart = createChart(document.getElementById('cpuChart'), 'CPU Usage (%)', cpuData, 'red');
        let ramChart = createChart(document.getElementById('ramChart'), 'RAM Usage (%)', ramData, 'blue');
        let diskChart = createChart(document.getElementById('diskChart'), 'Disk Usage (%)', diskData, 'green');
        let netChart = new Chart(document.getElementById('netChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Download (KB/s)',
                        data: rxData,
                        borderColor: 'purple',
                        backgroundColor: 'purple33',
                        fill: true
                    },
                    {
                        label: 'Upload (KB/s)',
                        data: txData,
                        borderColor: 'orange',
                        backgroundColor: 'orange33',
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true
            }
        });

        function updateData() {
            fetch('checkheal.php')
                .then(res => res.json())
                .then(data => {
                    let time = new Date().toLocaleTimeString();
                    labels.push(time);
                    if (labels.length > 10) labels.shift();

                    cpuData.push(data.cpu);
                    if (cpuData.length > 10) cpuData.shift();
                    ramData.push(data.ram);
                    if (ramData.length > 10) ramData.shift();
                    diskData.push(data.disk);
                    if (diskData.length > 10) diskData.shift();
                    rxData.push(data.network_rx);
                    if (rxData.length > 10) rxData.shift();
                    txData.push(data.network_tx);
                    if (txData.length > 10) txData.shift();

                    cpuChart.update();
                    ramChart.update();
                    diskChart.update();
                    netChart.update();

                    document.title = `CPU: ${data.cpu}% | RAM: ${data.ram}% | MySQL: ${data.mysql_status ? "OK" : "DOWN"}`;
                });
        }

        setInterval(updateData, 2000);
    </script>

</body>

</html>