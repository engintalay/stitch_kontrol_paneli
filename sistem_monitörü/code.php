<?php
// sistem_monitörü/code.php
session_start();
if (!isset($_SESSION['user_role'])) {
  header('Location: /login.php');
  exit;
}

// CPU kullanım oranını al
function getCpuUsage() {
    $load = sys_getloadavg();
    $cpuCores = 1;
    if (file_exists('/proc/cpuinfo')) {
        $cpuinfo = file_get_contents('/proc/cpuinfo');
        preg_match_all('/^processor/m', $cpuinfo, $matches);
        $cpuCores = count($matches[0]);
    }
    return round(($load[0] / $cpuCores) * 100, 2);
}

// RAM kullanım bilgilerini al
function getMemoryUsage() {
    $meminfo = [];
    if (file_exists('/proc/meminfo')) {
        $lines = file('/proc/meminfo');
        foreach ($lines as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches)) {
                $meminfo[$matches[1]] = $matches[2];
            }
        }
    }
    $total = isset($meminfo['MemTotal']) ? $meminfo['MemTotal'] : 0;
    $free = isset($meminfo['MemFree']) ? $meminfo['MemFree'] : 0;
    $available = isset($meminfo['MemAvailable']) ? $meminfo['MemAvailable'] : $free;
    $used = $total - $available;
    
    return [
        'total' => round($total / 1024 / 1024, 2), // GB
        'used' => round($used / 1024 / 1024, 2), // GB
        'free' => round($available / 1024 / 1024, 2), // GB
        'percent' => $total > 0 ? round(($used / $total) * 100, 2) : 0
    ];
}

// CPU sıcaklık ve fan hızı bilgilerini al
function getTempAndFan() {
    $log_file = '/var/log/fan_control.log';
    if (!file_exists($log_file)) {
        $log_file = '/home/engint/ssh_mounts/engin_192.168.1.250/var/log/fan_control.log';
    }
    
    if (!file_exists($log_file)) {
        return ['temp' => 0, 'fan' => 0];
    }
    
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($lines)) {
        return ['temp' => 0, 'fan' => 0];
    }
    
    $lastLine = end($lines);
    if (preg_match('/Temperature \(C\): (\d+\.\d+) \| Fan speed \(duty cycle %\): (\d+)$/', $lastLine, $matches)) {
        return [
            'temp' => floatval($matches[1]),
            'fan' => intval($matches[2])
        ];
    }
    
    return ['temp' => 0, 'fan' => 0];
}

// AJAX request için JSON response
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'cpu' => getCpuUsage(),
        'ram' => getMemoryUsage(),
        'temp_fan' => getTempAndFan()
    ]);
    exit;
}

$title = 'Sistem Monitörü';
$icon = 'monitor_heart';
$description = 'Sistem durumunu izleyin';

// Read system theme from settings.db
$systemTheme = 'auto';
try {
    $__db_tmp = new SQLite3(__DIR__ . '/../settings.db');
    $__stmt_theme = $__db_tmp->prepare('SELECT setting_value FROM settings WHERE setting_key = :key');
    $__stmt_theme->bindValue(':key', 'system_theme', SQLITE3_TEXT);
    $__res_theme = $__stmt_theme->execute();
    $__row_theme = $__res_theme->fetchArray(SQLITE3_ASSOC);
    if ($__row_theme && !empty($__row_theme['setting_value'])) {
        $systemTheme = $__row_theme['setting_value'];
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html <?= $systemTheme === 'dark' ? 'class="dark"' : '' ?> lang="tr">

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
  <?php if ($systemTheme === 'auto'): ?>
  <script>
    // Apply dark class if user's OS prefers dark mode
    (function(){
      try {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
          document.documentElement.classList.add('dark');
        }
      } catch (e) {}
    })();
  </script>
  <?php endif; ?>
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
      <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-2 text-primary flex items-center gap-2">
          <span class="material-symbols-outlined text-3xl"><?= htmlspecialchars($icon) ?></span>
          <?= htmlspecialchars($title) ?>
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mb-6"><?= htmlspecialchars($description) ?></p>

        <!-- System Stats Cards -->
        <?php
        $cpuUsage = getCpuUsage();
        $memUsage = getMemoryUsage();
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- CPU Card -->
            <div class="bg-white dark:bg-[#23272f] rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">CPU Kullanımı</h3>
                    <span class="material-symbols-outlined text-primary text-3xl">memory</span>
                </div>
                <div class="text-4xl font-bold text-primary mb-2"><?= $cpuUsage ?>%</div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                    <div class="bg-primary h-4 rounded-full transition-all" style="width: <?= min($cpuUsage, 100) ?>%"></div>
                </div>
                <div class="mt-4" style="height: 200px;">
                    <canvas id="cpuChart"></canvas>
                </div>
            </div>

            <!-- RAM Card -->
            <div class="bg-white dark:bg-[#23272f] rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">RAM Kullanımı</h3>
                    <span class="material-symbols-outlined text-primary text-3xl">storage</span>
                </div>
                <div class="text-4xl font-bold text-primary mb-2"><?= $memUsage['percent'] ?>%</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <?= $memUsage['used'] ?> GB / <?= $memUsage['total'] ?> GB
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                    <div class="bg-primary h-4 rounded-full transition-all" style="width: <?= min($memUsage['percent'], 100) ?>%"></div>
                </div>
                <div class="mt-4" style="height: 200px;">
                    <canvas id="ramChart"></canvas>
                </div>
            </div>
        </div>

        <!-- CPU Temperature & Fan Chart -->
        <?php
        $tempFan = getTempAndFan();
        ?>

        <div class="bg-white dark:bg-[#23272f] rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">CPU Sıcaklık & Fan Hızı</h3>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Sıcaklık</div>
                    <div class="text-2xl font-bold text-red-500" id="temp-value"><?= $tempFan['temp'] ?>°C</div>
                </div>
                <div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Fan Duty Cycle</div>
                    <div class="text-2xl font-bold text-blue-500" id="fan-value"><?= $tempFan['fan'] ?>%</div>
                </div>
            </div>
            <div style="height: 400px;">
                <canvas id="fanChart"></canvas>
            </div>
        </div>

        <script>
          // CPU Chart (Real-time updates)
          const cpuCtx = document.getElementById('cpuChart').getContext('2d');
          const cpuChart = new Chart(cpuCtx, {
            type: 'line',
            data: {
              labels: [],
              datasets: [{
                label: 'CPU Kullanımı (%)',
                data: [],
                borderColor: 'rgba(19, 91, 236, 1)',
                backgroundColor: 'rgba(19, 91, 236, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: {
                  beginAtZero: true,
                  max: 100,
                  ticks: {
                    callback: function(value) {
                      return value + '%';
                    }
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

          // RAM Chart (Real-time updates)
          const ramCtx = document.getElementById('ramChart').getContext('2d');
          const ramChart = new Chart(ramCtx, {
            type: 'line',
            data: {
              labels: [],
              datasets: [{
                label: 'RAM Kullanımı (%)',
                data: [],
                borderColor: 'rgba(19, 91, 236, 1)',
                backgroundColor: 'rgba(19, 91, 236, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: {
                  beginAtZero: true,
                  max: 100,
                  ticks: {
                    callback: function(value) {
                      return value + '%';
                    }
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

          // Initialize with current data
          const now = new Date().toLocaleTimeString('tr-TR');
          cpuChart.data.labels.push(now);
          cpuChart.data.datasets[0].data.push(<?= $cpuUsage ?>);
          cpuChart.update();

          ramChart.data.labels.push(now);
          ramChart.data.datasets[0].data.push(<?= $memUsage['percent'] ?>);
          ramChart.update();

          // Update CPU and RAM charts every 2 seconds
          setInterval(async () => {
            try {
              const response = await fetch('?ajax=1');
              const data = await response.json();
              
              const time = new Date().toLocaleTimeString('tr-TR');
              
              // Update CPU chart
              cpuChart.data.labels.push(time);
              cpuChart.data.datasets[0].data.push(data.cpu);
              if (cpuChart.data.labels.length > 30) {
                cpuChart.data.labels.shift();
                cpuChart.data.datasets[0].data.shift();
              }
              cpuChart.update();
              
              // Update RAM chart
              ramChart.data.labels.push(time);
              ramChart.data.datasets[0].data.push(data.ram.percent);
              if (ramChart.data.labels.length > 30) {
                ramChart.data.labels.shift();
                ramChart.data.datasets[0].data.shift();
              }
              ramChart.update();

              // Update Temperature & Fan chart
              fanChart.data.labels.push(time);
              fanChart.data.datasets[0].data.push(data.temp_fan.temp);
              fanChart.data.datasets[1].data.push(data.temp_fan.fan);
              if (fanChart.data.labels.length > 60) { // Keep last 60 data points (2 minutes)
                fanChart.data.labels.shift();
                fanChart.data.datasets[0].data.shift();
                fanChart.data.datasets[1].data.shift();
              }
              fanChart.update();

              // Update UI values
              document.querySelectorAll('.text-4xl.font-bold.text-primary')[0].textContent = data.cpu + '%';
              document.querySelectorAll('.text-4xl.font-bold.text-primary')[1].textContent = data.ram.percent + '%';
              document.querySelector('.text-sm.text-gray-600').textContent = 
                data.ram.used + ' GB / ' + data.ram.total + ' GB';
              document.querySelectorAll('.bg-primary.h-4.rounded-full')[0].style.width = 
                Math.min(data.cpu, 100) + '%';
              document.querySelectorAll('.bg-primary.h-4.rounded-full')[1].style.width = 
                Math.min(data.ram.percent, 100) + '%';
              
              // Update temperature and fan values
              document.getElementById('temp-value').textContent = data.temp_fan.temp + '°C';
              document.getElementById('fan-value').textContent = data.temp_fan.fan + '%';
            } catch (e) {
              console.error('Error updating stats:', e);
            }
          }, 2000);

          // Fan Chart
          const ctx = document.getElementById('fanChart').getContext('2d');
          const fanChart = new Chart(ctx, {
            type: 'line',
            data: {
              labels: [], // Time labels
              datasets: [{
                  label: 'Sıcaklık (°C)',
                  data: [],
                  borderColor: 'rgba(255, 99, 132, 1)',
                  backgroundColor: 'rgba(255, 99, 132, 0.1)',
                  borderWidth: 2,
                  fill: true,
                  tension: 0.4,
                  yAxisID: 'y'
                },
                {
                  label: 'Fan Duty Cycle (%)',
                  data: [],
                  borderColor: 'rgba(54, 162, 235, 1)',
                  backgroundColor: 'rgba(54, 162, 235, 0.1)',
                  borderWidth: 2,
                  fill: true,
                  tension: 0.4,
                  yAxisID: 'y1'
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                x: {
                  display: true,
                  title: {
                    display: true,
                    text: 'Zaman'
                  }
                },
                y: {
                  beginAtZero: true,
                  position: 'left',
                  title: {
                    display: true,
                    text: 'Sıcaklık (°C)'
                  },
                  ticks: {
                    callback: function(value) {
                      return value + '°C';
                    }
                  }
                },
                y1: {
                  beginAtZero: true,
                  max: 100,
                  position: 'right',
                  title: {
                    display: true,
                    text: 'Fan Duty Cycle (%)'
                  },
                  grid: {
                    drawOnChartArea: false
                  },
                  ticks: {
                    callback: function(value) {
                      return value + '%';
                    }
                  }
                }
              },
              plugins: {
                tooltip: {
                  enabled: true
                },
                legend: {
                  display: true,
                  position: 'top'
                }
              }
            }
          });

          // Initialize fan chart with current data
          const initTime = new Date().toLocaleTimeString('tr-TR');
          fanChart.data.labels.push(initTime);
          fanChart.data.datasets[0].data.push(<?= $tempFan['temp'] ?>);
          fanChart.data.datasets[1].data.push(<?= $tempFan['fan'] ?>);
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