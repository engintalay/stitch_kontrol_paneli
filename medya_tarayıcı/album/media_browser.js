let currentPath = '';
let items = [];
let currentIndex = 0;
let thumbTotal = 0;
let thumbLoaded = 0;
let thumbFailed = 0;
// Preserve original document title so we can prepend progress
let originalTitle = (typeof document !== 'undefined' && document.title) ? document.title : '';

function getPathFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('path') || '';
}

// Kök medya dizinini buradan ayarlayın:
const MEDIA_ROOT = window.MEDIA_ROOT || '../../media';

function fetchItems(path = '', push = true) {
    fetch('album/media_browser.php?path=' + encodeURIComponent(path))
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            items = data.items;
            currentPath = data.current;
            // expose currentPath to global so page controls can read it
            window.currentPath = currentPath;
            renderBrowser();
            try {
                const url = '?path=' + encodeURIComponent(currentPath);
                if (push) {
                    history.pushState({ path: currentPath }, '', url);
                } else {
                    history.replaceState({ path: currentPath }, '', url);
                }
            } catch (e) { }
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
    // reset thumbnail counters
    thumbTotal = 0;
    thumbLoaded = 0;
    thumbFailed = 0;
    updateThumbStatus();
    // ensure global copy is kept in sync
    window.currentPath = currentPath;

    // IntersectionObserver for strict lazy loading
    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    obs.unobserve(img);
                }
            }
        });
    }, {
        root: null,
        rootMargin: '200px', // Load images 200px before they enter viewport
        threshold: 0.01
    });

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
        let overlay;
        if (item.type === 'image') {
            thumb = document.createElement('img');
            let thumbPath = item.path;
            thumb.className = 'thumb';
            // Removed native loading="lazy" in favor of observer
            // count and attach listeners before setting src
            thumbTotal++;
            thumb.addEventListener('load', () => { thumbLoaded++; updateThumbStatus(); });
            thumb.addEventListener('error', () => { thumbFailed++; updateThumbStatus(); });
            // Use data-src for lazy loading
            thumb.dataset.src = 'album/thumb.php?path=' + encodeURIComponent(thumbPath) + '&size=250&format=webp';
            // Set a placeholder or keep transparent until loaded
            thumb.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
            observer.observe(thumb);
        } else if (item.type === 'video') {
            thumb = document.createElement('img');
            thumb.className = 'thumb';
            let thumbPath = item.path;
            // count and attach listeners before src
            thumbTotal++;
            thumb.addEventListener('load', () => { thumbLoaded++; updateThumbStatus(); });
            thumb.addEventListener('error', () => { thumbFailed++; updateThumbStatus(); });
            thumb.dataset.src = 'album/thumb.php?path=' + encodeURIComponent(thumbPath) + '&size=250&format=webp';
            thumb.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
            observer.observe(thumb);

            overlay = document.createElement('div');
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
        if (typeof overlay !== 'undefined') {
            div.appendChild(overlay);
        }
        const label = document.createElement('div');
        label.innerText = item.name;
        div.appendChild(label);
        if (item.size && typeof item.size === 'number') {
            const sizeDiv = document.createElement('div');
            sizeDiv.style.fontSize = '0.9em';
            sizeDiv.style.color = '#666';
            sizeDiv.innerText = formatSize(item.size);
            div.appendChild(sizeDiv);
        }
        browser.appendChild(div);
    });
    // After rendering all thumbs, ensure status is updated (in case loads happened synchronously)
    updateThumbStatus();
}
// Dosya boyutunu okunabilir formata çeviren fonksiyon
function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
}

function updateThumbStatus() {
    const totalEl = document.getElementById('thumb-total');
    const loadedEl = document.getElementById('thumb-loaded');
    const failedEl = document.getElementById('thumb-failed');
    if (totalEl) totalEl.innerText = thumbTotal;
    if (loadedEl) {
        let pct = 0;
        if (thumbTotal > 0) pct = Math.round((thumbLoaded / thumbTotal) * 100);
        if (pct < 0) pct = 0;
        if (pct > 100) pct = 100;
        // Show just the loaded count; show percent text only when it's 100% as "yükleme tamam"
        if (thumbTotal > 0 && pct === 100) {
            loadedEl.innerText = thumbLoaded + ' (yükleme tamam)';
        } else {
            loadedEl.innerText = thumbLoaded;
        }
    }
    if (failedEl) failedEl.innerText = thumbFailed;

    // Update browser tab title with progress (show 'yükleme tamam' at 100%)
    try {
        if (!originalTitle) originalTitle = document.title || '';
        if (thumbTotal > 0) {
            let pct = Math.round((thumbLoaded / thumbTotal) * 100);
            if (pct === 100) {
                document.title = originalTitle + ' — ' + thumbLoaded + '/' + thumbTotal + ' (yükleme tamam)';
            } else {
                document.title = originalTitle + ' — ' + thumbLoaded + '/' + thumbTotal;
            }
        } else {
            document.title = originalTitle;
        }
    } catch (e) { }
}

function goUp() {
    if (!currentPath) return;
    const parts = currentPath.split('/');
    parts.pop();
    const parent = parts.join('/');
    fetchItems(parent, true);
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
            } catch (e) { }
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
    if (!modalMedia) {
        alert('Hata: #modal-media elementi bulunamadı!\nSayfa tam yüklenmemiş olabilir.');
        return;
    }
    modalMedia.innerHTML = '';
    if (item.type === 'image') {
        const img = document.createElement('img');
        img.src = 'album/media_serve.php?path=' + encodeURIComponent(item.path);
        modalMedia.appendChild(img);
    } else if (item.type === 'video') {
        const video = document.createElement('video');
        video.src = 'album/media_serve.php?path=' + encodeURIComponent(item.path);
        video.controls = true;
        video.autoplay = true;
        video.preload = 'metadata';
        // set poster from thumbnail (larger size)
        video.poster = 'album/thumb.php?path=' + encodeURIComponent(item.path) + '&size=800&format=webp';
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
    document.addEventListener('keydown', function (e) {
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
document.addEventListener('keydown', function (e) {
    const modal = document.getElementById('modal');
    if (e.key === 'Backspace' && (!modal || modal.style.display !== 'flex')) {
        e.preventDefault();
        goUp();
    }
});

// Browser back/forward handling: navigate according to stored path
window.addEventListener('popstate', function (e) {
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
