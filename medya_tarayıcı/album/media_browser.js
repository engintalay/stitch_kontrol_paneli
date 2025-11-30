let currentPath = '';
let items = [];
let currentIndex = 0;

function getPathFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('path') || '';
}

// Kök medya dizinini buradan ayarlayın:
const MEDIA_ROOT = window.MEDIA_ROOT || '../media';
function fetchItems(path = '', push = true) {
    fetch('album/media_browser.php?path=' + encodeURIComponent(path))
        .then(res => res.json())
        .then(data => {
            console.log('media_browser.php yanıtı:', data);
            if (data.error) {
                alert(data.error);
                return;
            }
            items = data.items;
            currentPath = data.current;
            renderBrowser();
            try {
                const url = '?path=' + encodeURIComponent(currentPath);
                if (push) {
                    history.pushState({ path: currentPath }, '', url);
                } else {
                    history.replaceState({ path: currentPath }, '', url);
                }
            } catch (e) {
                // ignore history errors in older browsers
            }
        });
}

function renderBrowser() {
    // Render clickable breadcrumb path
    const pathContainer = document.getElementById('path');
    const base = '/media';
    const parts = currentPath ? currentPath.split('/') : [];
    pathContainer.innerHTML = '';
    const rootLink = document.createElement('a');
    rootLink.href = 'javascript:;';
    rootLink.innerText = base;
    rootLink.onclick = () => fetchItems('', true);
    pathContainer.appendChild(rootLink);
    let acc = '';
    parts.forEach((p, i) => {
        acc = acc ? acc + '/' + p : p;
        const sep = document.createTextNode(' / ');
        pathContainer.appendChild(sep);
        const link = document.createElement('a');
        link.href = 'javascript:;';
        link.innerText = p;
        link.onclick = () => fetchItems(acc, true);
        pathContainer.appendChild(link);
    });
    const browser = document.getElementById('browser');
    browser.innerHTML = '';
    items.forEach((item, idx) => {
        const div = document.createElement('div');
        div.className = 'item';
        div.onclick = () => {
            if (item.type === 'dir') {
                fetchItems(item.path);
            } else if (item.type === 'image' || item.type === 'video') {
                openModal(idx);
            } else if (item.type === 'back') {
                fetchItems(item.path);
            }
        };
        let thumb;
        if (item.type === 'image') {
            thumb = document.createElement('img');
            // Sadece media klasörü altındaki göreli yol gönderilmeli
            let thumbPath = item.path;
            thumb.src = 'thumb.php?path=' + encodeURIComponent(thumbPath) + '&size=250&format=webp';
            console.log('thumb src:', thumb.src);
            thumb.className = 'thumb';
            thumb.loading = 'lazy';
        } else if (item.type === 'video') {
            // Use an actual thumbnail image (WebP if supported) and overlay a play icon
            thumb = document.createElement('img');
            thumb.className = 'thumb';
            thumb.loading = 'lazy';
            let thumbPath = item.path;
            thumb.src = 'thumb.php?path=' + encodeURIComponent(thumbPath) + '&size=250&format=webp';
            // overlay will be appended to the parent .item div so it sits over the img
            const overlay = document.createElement('div');
            overlay.className = 'play-overlay';
            overlay.innerText = '▶';
        } else if (item.type === 'dir') {
            thumb = document.createElement('div');
            thumb.className = 'thumb';
            thumb.style.display = 'flex';
            thumb.style.alignItems = 'center';
            thumb.style.justifyContent = 'center';
            thumb.style.background = '#eee';
            thumb.innerText = '📁';
        } else if (item.type === 'back') {
            thumb = document.createElement('div');
            thumb.className = 'thumb';
            thumb.style.display = 'flex';
            thumb.style.alignItems = 'center';
            thumb.style.justifyContent = 'center';
            thumb.style.background = '#ffe';
            thumb.innerText = '⬆️';
        } else {
            thumb = document.createElement('div');
            thumb.className = 'thumb';
            thumb.style.display = 'flex';
            thumb.style.alignItems = 'center';
            thumb.style.justifyContent = 'center';
            thumb.style.background = '#eee';
            thumb.innerText = '📄';
        }
        div.appendChild(thumb);
        // If overlay created (video), append overlay over thumb
        if (typeof overlay !== 'undefined') {
            div.appendChild(overlay);
        }
        const label = document.createElement('div');
        label.innerText = item.name;
        div.appendChild(label);
        // Dosya boyutu gösterimi
        if (item.size && typeof item.size === 'number') {
            const sizeDiv = document.createElement('div');
            sizeDiv.style.fontSize = '0.9em';
            sizeDiv.style.color = '#666';
            sizeDiv.innerText = formatSize(item.size);
            div.appendChild(sizeDiv);
        }
        browser.appendChild(div);
// Dosya boyutunu okunabilir formata çeviren fonksiyon
function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
}

function goUp() {
    if (!currentPath) return;
    const parts = currentPath.split('/');
    parts.pop();
    const parent = parts.join('/');
    fetchItems(parent, true);
}
    });
}

function openModal(idx) {
    currentIndex = idx;
    showModalMedia();
    document.getElementById('modal').style.display = 'flex';
}
function closeModal() {
    // Stop and unload any playing videos inside the modal to free resources
    const modalMedia = document.getElementById('modal-media');
    if (modalMedia) {
        const vids = modalMedia.getElementsByTagName('video');
        for (let i = 0; i < vids.length; i++) {
            try {
                vids[i].pause();
                vids[i].removeAttribute('src');
                vids[i].load();
            } catch (e) {}
        }
        modalMedia.innerHTML = '';
    }
    document.getElementById('modal').style.display = 'none';
}
function showPrev() {
    let idx = currentIndex;
    do {
        idx = (idx - 1 + items.length) % items.length;
    } while (items[idx].type !== 'image' && items[idx].type !== 'video' && idx !== currentIndex);
    currentIndex = idx;
    showModalMedia();
}
function showNext() {
    let idx = currentIndex;
    do {
        idx = (idx + 1) % items.length;
    } while (items[idx].type !== 'image' && items[idx].type !== 'video' && idx !== currentIndex);
    currentIndex = idx;
    showModalMedia();
}
function showModalMedia() {
    const item = items[currentIndex];
    const modalMedia = document.getElementById('modal-media');
    modalMedia.innerHTML = '';
    if (item.type === 'image') {
        const img = document.createElement('img');
        img.src = 'media_serve.php?path=' + encodeURIComponent(item.path);
        modalMedia.appendChild(img);
    } else if (item.type === 'video') {
        const video = document.createElement('video');
        video.src = 'media_serve.php?path=' + encodeURIComponent(item.path);
        video.controls = true;
        video.autoplay = true;
        video.preload = 'metadata';
        // set poster from thumbnail (larger size)
        video.poster = 'thumb.php?path=' + encodeURIComponent(item.path) + '&size=800&format=webp';
        modalMedia.appendChild(video);
    }
}

// Sayfayı yenilemek için buton ekle (yenileme history eklemez)

const refreshBtn = document.createElement('button');
refreshBtn.innerText = 'Yenile';
refreshBtn.style.marginBottom = '16px';
refreshBtn.onclick = () => fetchItems(currentPath, false);
const browserElem = document.getElementById('browser');
if (browserElem && browserElem.parentNode) {
    browserElem.parentNode.insertBefore(refreshBtn, browserElem);
} else {
    document.body.appendChild(refreshBtn);
}

window.onload = () => {
    const initial = getPathFromURL();
    fetchItems(initial, false);
    // ESC ile modal kapatma ve ok tuşları ile ileri/geri
    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('modal');
        if (modal && modal.style.display === 'flex') {
            if (e.key === 'Escape') {
                closeModal();
            } else if (e.key === 'ArrowLeft') {
                showPrev();
            } else if (e.key === 'ArrowRight') {
                showNext();
            }
        }
    });
};

// Backspace ile üst dizine çıkma (modal açık değilse)
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('modal');
    if (e.key === 'Backspace' && (!modal || modal.style.display !== 'flex')) {
        e.preventDefault();
        goUp();
    }
});

// Browser back/forward handling: navigate according to stored path
window.addEventListener('popstate', function(e) {
    const state = e.state;
    let path = '';
    if (state && typeof state.path === 'string') {
        path = state.path;
    } else {
        path = getPathFromURL();
    }
    // Load items for this path without pushing another history entry
    fetchItems(path, false);
});
