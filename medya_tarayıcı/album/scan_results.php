<?php
// scan_results.php: Kişi seçimi ile ilgili resimleri gösteren sayfa
header('Content-Type: text/html; charset=utf-8');
$scanFile = __DIR__ . '/scan_results.json';
$results = file_exists($scanFile) ? json_decode(file_get_contents($scanFile), true) : [];
// Tüm isimleri topla ve uniq yap
$names = [];
$emptyCount = 0;
foreach ($results as $img => $arr) {
    if (empty($arr)) {
        $emptyCount++;
        $names[] = 'Boş';
    } else {
        foreach ($arr as $name) {
            $names[] = $name;
        }
    }
}
$names = array_unique($names);
$nameCounts = [];
foreach ($names as $name) {
    $nameCounts[$name] = 0;
}
foreach ($results as $img => $arr) {
    if (empty($arr)) {
        $nameCounts['']++;
    } else {
        foreach ($arr as $name) {
            $nameCounts[$name]++;
        }
    }
}
$namesList = array_values($names);
usort($namesList, function($a, $b) {
    // Boşlar en sona
    if ($a === '' && $b !== '') return 1;
    if ($b === '' && $a !== '') return -1;
    return strcoll($a, $b);
});
$showNames = array_slice($namesList, 0, 20);
$selected = isset($_GET['name']) ? $_GET['name'] : '';


// Seçilen isim için resimleri bul
$images = [];
if ($selected && $selected !== "Boş") {
    foreach ($results as $img => $arr) {
        if (in_array($selected, $arr)) {
            $images[] = $img;
        }
    }
} else {
    // $selected boşsa, array'i boş olanlardan 50 tane getir
    $count = 0;
    foreach ($results as $img => $arr) {
        if (empty($arr)) {
            $images[] = $img;
            $count++;
            if ($count >= 350) break;
        }
    }
}
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Scan Results Search</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; padding:0; }
        .container { max-width: 900px; margin: auto; padding: 24px; }
        select { font-size: 1.2em; margin-bottom: 16px; }
        .thumbs { display: flex; flex-wrap: wrap; gap: 16px; }
        .thumb { width: 180px; height: 180px; object-fit: cover; border: 1px solid #ccc; }
        .thumb-label { text-align: center; font-size: 0.95em; margin-top: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Scan Results Search</h2>
    <form method="get">
        <label for="name">Kişi seç:</label>
        <select name="name" id="name" onchange="this.form.submit()">
                <option value="">-- Seçiniz --</option>
                <?php foreach ($showNames as $name): ?>
                    <option value="<?= htmlspecialchars($name) ?>" <?= $selected==$name?'selected':'' ?>>
                        <?= $name===''?'(Boş)':htmlspecialchars($name) ?> (<?= $nameCounts[$name] ?>)
                    </option>
                <?php endforeach; ?>
                <?php if (count($namesList) > 20): ?>
                    <option disabled>...</option>
                <?php endif; ?>
                <?php if (!in_array($selected, $showNames) && $selected!==''): ?>
                    <option value="<?= htmlspecialchars($selected) ?>" selected>
                        <?= $selected===''?'(Boş)':htmlspecialchars($selected) ?> (<?= isset($nameCounts[$selected]) ? $nameCounts[$selected] : 0 ?>) (Diğer)
                    </option>
                <?php endif; ?>
        </select>
    </form>
    <?php if ($selected): ?>
        <h3><?= htmlspecialchars($selected) ?> için bulunan resimler:</h3>
        <div class="thumbs">
                <?php foreach ($images as $img): ?>
                    <div>
                        <img src="thumb.php?path=<?= urlencode(str_replace('/media/', '', $img)) ?>&size=180" class="thumb" onclick="showModal('<?= htmlspecialchars($img) ?>')">
                        <div class="thumb-label"><?= basename($img) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($images)): ?>
                    <div>Hiç resim bulunamadı.</div>
                <?php endif; ?>
        </div>
        <div id="modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.8);justify-content:center;align-items:center;z-index:1000;">
            <div style="position:relative;background:#fff;padding:16px;border-radius:8px;max-width:90vw;max-height:90vh;">
                <button onclick="closeModal()" style="position:absolute;top:8px;right:8px;font-size:2rem;background:#222;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;">&times;</button>
                <img id="modalImg" src="" style="max-width:80vw;max-height:80vh;display:block;margin:auto;">
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
<script>
var images = <?php echo json_encode(array_values($images)); ?>;
var currentIndex = -1;
function showModal(imgPath) {
    var modal = document.getElementById('modal');
    var modalImg = document.getElementById('modalImg');
    var relPath = imgPath.replace('/media/', '');
    modalImg.src = 'media_serve.php?path=' + encodeURIComponent(relPath);
    modal.style.display = 'flex';
    currentIndex = images.indexOf(imgPath);
}
function closeModal() {
    document.getElementById('modal').style.display = 'none';
    currentIndex = -1;
}
function showPrev() {
    if (currentIndex > 0) {
        showModal(images[currentIndex - 1]);
    }
}
function showNext() {
    if (currentIndex < images.length - 1) {
        showModal(images[currentIndex + 1]);
    }
}
document.addEventListener('keydown', function(e) {
    var modal = document.getElementById('modal');
    if (modal.style.display === 'flex') {
        if (e.key === 'Escape') {
            closeModal();
        } else if (e.key === 'ArrowLeft') {
            showPrev();
        } else if (e.key === 'ArrowRight') {
            showNext();
        }
    }
});
var modalElem = document.getElementById('modal');
if (modalElem) {
    modalElem.addEventListener('mousedown', function(e) {
        if (e.target === this) closeModal();
    });
}
</script>
</html>
