<?php
// sistem_monitörü/code.php
session_start();
if (!isset($_SESSION['user_role'])) {
  header('Location: /login.php');
  exit;
}

$title = 'Sistem Monitörü';
$icon = 'monitor_heart';
$description = 'Sistem durumunu izleyin';
?>
<!DOCTYPE html>
<html class="dark" lang="tr">

<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title><?= htmlspecialchars($title) ?></title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com" rel="preconnect" />
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#135bec",
            "background-light": "#f6f6f8",
            "background-dark": "#101622",
          },
          fontFamily: {
            "display": ["Space Grotesk", "sans-serif"]
          },
          borderRadius: {
            "DEFAULT": "0.25rem",
            "lg": "0.5rem",
            "xl": "0.75rem",
            "full": "9999px"
          },
        },
      },
    }
  </script>
  <style>
    c .material-symbols-outlined {
      font-variation-settings:
        'FILL' 0,
        'wght' 400,
        'GRAD' 0,
        'opsz' 24
    }
  </style>
</head>

<body class="font-display bg-background-light dark:bg-background-dark">
  <div class="flex flex-col min-h-screen">
    <header class="flex items-center justify-between p-4 bg-white/80 dark:bg-[#181c23] shadow">
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-primary text-3xl">dashboard</span>
        <span class="font-bold text-xl text-primary"><?= htmlspecialchars($title) ?></span>
      </div>
      <nav class="space-x-4">
        <a href="../home.php" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">Ana Sayfa</a>
      </nav>
    </header>
    <main class="flex-1 p-8">
      <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-2 text-primary flex items-center gap-2">
          <span class="material-symbols-outlined text-3xl"><?= htmlspecialchars($icon) ?></span>
          <?= htmlspecialchars($title) ?>
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mb-6"><?= htmlspecialchars($description) ?></p>

        <!-- CPU & Fan Table -->
        <?php
        
        $log_file = '/var/log/fan_control.log';
        if ( !file_exists($log_file) ) {
            $log_file = '/home/engint/ssh_mounts/engin_192.168.1.250/var/log/fan_control.log';
        }
        
        $lines = array_slice(file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -1800); // 1800 lines for 1 hours (2 seconds interval)
        $time_labels = [];
        $temp_data = [];
        $fan_data = [];
        $last_timestamp = null;

        foreach ($lines as $line) {
            preg_match('/^(\S+ \S+),\d+ INFO Temperature \(C\): (\d+\.\d+) \| Fan speed \(duty cycle %\): (\d+)$/', $line, $matches);
            if ($matches) {
                $timestamp = strtotime($matches[1]);
                if ($last_timestamp === null || ($timestamp - $last_timestamp) >= 60) { // 60 seconds = 1 minutes
                    $time_labels[] = $matches[1];
                    $temp_data[] = $matches[2];
                    $fan_data[] = $matches[3];
                    $last_timestamp = $timestamp;
                }
            }
        }
        ?>
        <canvas id="fanChart" width="1000" height="500"></canvas>

        <script>
          const ctx = document.getElementById('fanChart').getContext('2d');
          const fanChart = new Chart(ctx, {
            type: 'line',
            data: {
              labels: [], // Time labels
              datasets: [{
                  label: 'Temperature (°C)',
                  data: [],
                  borderColor: 'rgba(255, 99, 132, 1)',
                  borderWidth: 1,
                  fill: false,
                  yAxisID: 'y'
                },
                {
                  label: 'Fan Duty Cycle (%)',
                  data: [],
                  borderColor: 'rgba(54, 162, 235, 1)',
                  borderWidth: 1,
                  fill: false,
                  yAxisID: 'y1'
                }
              ]
            },
            options: {
              scales: {
                x: {
                  type: 'time',
                  time: {
                    unit: 'second'
                  }
                },
                y: {
                  beginAtZero: true,
                  position: 'left',
                  title: {
                    display: true,
                    text: 'Temperature (°C)'
                  }
                },
                y1: {
                  beginAtZero: true,
                  position: 'right',
                  title: {
                    display: true,
                    text: 'Fan Duty Cycle (%)'
                  },
                  grid: {
                    drawOnChartArea: false
                  }
                }
              },
              plugins: {
                tooltip: {
                  enabled: true
                },
                datalabels: {
                  display: true,
                  align: 'top',
                  formatter: (value) => value
                }
              }
            }
          });
          const timeLabels = <?php echo json_encode($time_labels); ?>;
          const tempData = <?php echo json_encode($temp_data); ?>;
          const fanData = <?php echo json_encode($fan_data); ?>;
          fanChart.data.labels = timeLabels;
          fanChart.data.datasets[0].data = tempData;
          fanChart.data.datasets[1].data = fanData;
          fanChart.update();
        </script>


      </div>
    </main>
    <div class="flex justify-end p-4">
      <a href="../logout.php" class="px-4 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition">Çıkış</a>
    </div>
  </div>
</body>

</html>