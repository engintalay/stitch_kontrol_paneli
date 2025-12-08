<?php
// yapay_zeka_sohbet_botu/code.php
session_start();
if (!isset($_SESSION['user_role'])) {
    header('Location: /login.php');
    exit;
}

$title = 'Yapay Zeka Sohbet Botu';
$icon = 'smart_toy';
$description = 'Yapay zeka ile sohbet edin';

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
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title><?= htmlspecialchars($title) ?></title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
    .material-symbols-outlined {
      font-variation-settings:
        'FILL' 0,
        'wght' 400,
        'GRAD' 0,
        'opsz' 24
    }
  </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
  <div class="flex flex-col h-screen w-full">
    <!-- Top Header (Consistent with other modules) -->
    <header class="flex items-center justify-between p-4 bg-white/80 dark:bg-[#181c23] shadow z-10">
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-primary text-3xl"><?= $icon ?></span>
        <span class="font-bold text-xl text-primary"><?= htmlspecialchars($title) ?></span>
      </div>
      <div class="flex items-center gap-4">
        <!-- API Key Button in Header for visibility -->
        <button onclick="openSettingsModal()" class="flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition lg:hidden">
            <span class="material-symbols-outlined text-gray-600 dark:text-gray-300">key</span>
        </button>  
        <a href="../home.php" class="hidden md:inline-flex px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">Ana Sayfa</a>
        <a href="../logout.php" class="hidden md:inline-flex px-4 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition">Çıkış</a>
        <!-- Mobile/Compact Menu -->
        <a href="../home.php" class="md:hidden text-primary"><span class="material-symbols-outlined">home</span></a>
        <a href="../logout.php" class="md:hidden text-red-600"><span class="material-symbols-outlined">logout</span></a>
      </div>
    </header>

    <!-- Main Content Area (Sidebar + Chat) -->
    <div class="flex flex-1 overflow-hidden relative">
      <!-- SideNavBar -->
      <aside class="flex w-64 flex-col border-r border-gray-200 dark:border-gray-800 bg-background-light dark:bg-[#111318] p-4 hidden md:flex">
        <div class="flex flex-col gap-2 mb-4">
             <button onclick="createNewChat()" class="flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary/90 transition shadow">
               <span class="material-symbols-outlined mr-2 text-lg">add</span>
               <span class="truncate">Yeni Sohbet</span>
             </button>
             <button onclick="openSettingsModal()" class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800/50 w-full text-left border border-gray-200 dark:border-gray-800">
               <span class="material-symbols-outlined text-gray-500 dark:text-white text-base">key</span>
               <p class="text-sm font-medium text-gray-700 dark:text-white">API Ayarları</p>
             </button>
        </div>
        
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Geçmiş Sohbetler</div>
        <nav id="chatParamsList" class="flex flex-col gap-2 overflow-y-auto flex-1 pr-1">
          <!-- Chat list loaded via JS -->
          <div class="text-center text-sm text-gray-500 mt-4">Yükleniyor...</div>
        </nav>
        
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800 flex flex-col gap-2">
             <a class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800/50" href="../admin_panel.php">
                <span class="material-symbols-outlined text-gray-500 dark:text-white text-base">settings</span>
                <p class="text-sm font-medium text-gray-700 dark:text-white">Admin Paneli</p>
              </a>
        </div>
      </aside>

      <!-- Main Chat Area -->
      <main class="flex flex-1 flex-col relative bg-white dark:bg-[#181c23]">
        <header class="flex h-16 items-center border-b border-gray-200 dark:border-gray-800 px-6 shrink-0 z-0">
          <!-- Mobile Toggle -->
          <button class="md:hidden mr-4" onclick="document.querySelector('aside').classList.toggle('hidden'); document.querySelector('aside').classList.toggle('absolute'); document.querySelector('aside').classList.toggle('z-50'); document.querySelector('aside').classList.toggle('h-full');">
             <span class="material-symbols-outlined">menu</span>
          </button>
          
          <h2 id="currentChatTitle" class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Sohbet</h2>
          <div class="ml-auto flex items-center gap-4">
             <button onclick="deleteCurrentChat()" title="Sohbeti Sil" class="text-gray-400 hover:text-red-500 transition">
               <span class="material-symbols-outlined">delete</span>
             </button>
          </div>
        </header>
        
        <!-- Chat Messages -->
        <div id="messagesContainer" class="flex flex-1 flex-col overflow-y-auto p-6 space-y-6 scroll-smooth">
           <div class="flex flex-col items-center justify-center h-full text-gray-500">
             <span class="material-symbols-outlined text-4xl mb-2">chat_bubble_outline</span>
             <p>Bir sohbet seçin veya yeni bir sohbet başlatın.</p>
           </div>
        </div>
        
        <!-- Composer -->
        <div class="border-t border-gray-200 dark:border-gray-800 p-4 shrink-0">
          <div class="relative max-w-4xl mx-auto">
            <form id="chatForm" onsubmit="event.preventDefault(); sendMessage();">
              <textarea id="messageInput" class="form-input w-full resize-none rounded-xl border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-[#282e39] text-gray-800 dark:text-white placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:border-primary focus:ring-primary py-3 pl-4 pr-16 shadow-sm" placeholder="Bir mesaj gönder..." rows="1" onkeydown="if(event.key === 'Enter' && !event.shiftKey){ event.preventDefault(); sendMessage(); }"></textarea>
              <div class="absolute bottom-2 right-2 flex items-center gap-2">
                <button type="submit" class="flex cursor-pointer items-center justify-center rounded-lg bg-primary h-9 w-9 text-white hover:bg-primary/90 transition disabled:opacity-50 disabled:cursor-not-allowed" id="sendBtn">
                  <span class="material-symbols-outlined text-base">send</span>
                </button>
              </div>
            </form>
            <div class="text-center text-xs text-gray-400 mt-2">Yapay zeka hatalı bilgi verebilir. Önemli konularda kontrol ediniz.</div>
          </div>
        </div>
      </main>
    </div>
  </div>
  
  <!-- API Key Modal -->
  <div id="settingsModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white dark:bg-[#111318] p-6 rounded-xl shadow-xl max-w-md w-full mx-4">
      <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Yapay Zeka Ayarları</h2>
          <div class="flex items-center gap-2" title="Sunucu Bağlantı Durumu">
              <span class="text-xs text-gray-500" id="statusText">Bağlantı Yok</span>
              <div id="serverStatusIndicator" class="w-3 h-3 rounded-full bg-gray-400 transition-colors"></div>
          </div>
      </div>
      <p class="text-sm text-gray-500 mb-4">OpenAI veya uyumlu bir sunucu (LM Studio vb.) ayarlarını giriniz.</p>
      
      <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Sunucu Adresi (Base URL)</label>
            <div class="flex gap-2">
                <input type="text" id="baseUrlInput" onblur="checkConnection()" class="form-input flex-1 rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-[#282e39] text-sm" placeholder="https://api.openai.com/v1" />
                <button onclick="checkConnection()" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600" title="Bağlantıyı Kontrol Et">
                    <span class="material-symbols-outlined text-sm">sync</span>
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">LM Studio için genelde: http://localhost:1234/v1</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Model Seçimi</label>
            <div class="flex gap-2">
                <div class="relative flex-1">
                     <select id="modelSelect" class="form-select w-full rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-[#282e39] text-sm appearance-none">
                         <option value="gpt-4o">gpt-4o</option>
                         <option value="gpt-3.5-turbo">gpt-3.5-turbo</option>
                     </select>
                     <!-- Fallback for custom model name if not in list -->
                     <input type="text" id="modelInputCustom" class="hidden form-input w-full rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-[#282e39] text-sm mt-1" placeholder="Manuel model adı girin..." />
                </div>
                <button onclick="fetchModels()" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600" title="Modelleri Yenile">
                    <span class="material-symbols-outlined text-sm">refresh</span>
                </button>
            </div>
            <div class="flex items-center gap-2 mt-1">
                <input type="checkbox" id="manualModelCheckbox" class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4" onchange="toggleManualModel()">
                <label for="manualModelCheckbox" class="text-xs text-gray-500 cursor-pointer">Manuel model adı gir</label>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">API Anahtarı</label>
            <input type="password" id="apiKeyInput" class="form-input w-full rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-[#282e39] text-sm" placeholder="sk-..." />
            <p class="text-xs text-gray-400 mt-1">Yerel sunucular için rastgele bir değer girilebilir.</p>
          </div>
      </div>

      <div class="flex justify-end gap-2 mt-6">
        <button onclick="document.getElementById('settingsModal').classList.add('hidden')" class="px-4 py-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">İptal</button>
        <button onclick="saveSettings()" class="px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90">Kaydet</button>
      </div>
    </div>
  </div>

  <script>
    let activeChatId = null;

    function showToast(msg, type='info') {
        const div = document.createElement('div');
        div.className = `fixed top-4 right-4 px-4 py-2 rounded shadow-lg z-50 text-white ${type === 'error' ? 'bg-red-600' : 'bg-gray-800'}`;
        div.innerText = msg;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 3000);
    }
    
    function toggleManualModel() {
        const isManual = document.getElementById('manualModelCheckbox').checked;
        const sel = document.getElementById('modelSelect');
        const inp = document.getElementById('modelInputCustom');
        
        if (isManual) {
            sel.classList.add('hidden');
            inp.classList.remove('hidden');
            inp.value = sel.value;
        } else {
            sel.classList.remove('hidden');
            inp.classList.add('hidden');
            // Add custom value to select if needed?
        }
    }

    async function checkConnection() {
        const statusInd = document.getElementById('serverStatusIndicator');
        const statusTxt = document.getElementById('statusText');
        
        statusInd.className = "w-3 h-3 rounded-full bg-yellow-400 animate-pulse";
        statusTxt.textContent = "Kontrol ediliyor...";
        
        // We need to temporarily save settings or pass them in query? 
        // The list_models action reads from DB. So we must save API Key/BaseURL first?
        // Actually, let's just use what's in input effectively.
        // Wait, list_models reads from DB (user_settings). 
        // So connection check effectively requires saving first? That's annoying for "check before save".
        // Let's assume we save silently or we just check if current DB settings allow connection.
        // Since we changed UI to Inputs, maybe we should allow passing params to `list_models` via POST for testing?
        // Or just save them temporarily. Let's try to fetch models. If it works, connection is OK.
        
        // Ideally, checkConnection should check the *Input* values, but our backend list_models reads from DB.
        // Let's first save the current inputs to DB (maybe as a "test" save or just real save).
        // Let's do a quiet save then check.
        
        await saveSettings(true); // Quiet save
        
        try {
            const res = await fetch('chat_api.php?action=list_models');
            const data = await res.json();
            
            if (data.ok) {
                statusInd.className = "w-3 h-3 rounded-full bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.5)]";
                statusTxt.textContent = "Bağlı";
                statusTxt.className = "text-xs text-green-600 font-bold";
                populateModelSelect(data.models);
                return true;
            } else {
                throw new Error(data.error);
            }
        } catch(e) {
            statusInd.className = "w-3 h-3 rounded-full bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.5)]";
            statusTxt.textContent = "Hata";
            statusTxt.className = "text-xs text-red-500 font-bold";
            console.error(e);
            return false;
        }
    }
    
    function populateModelSelect(models) {
        const sel = document.getElementById('modelSelect');
        const currentVal = sel.value;
        sel.innerHTML = '';
        
        if (!models || models.length === 0) {
            const opt = document.createElement('option');
            opt.text = "Model bulunamadı";
            sel.add(opt);
            return;
        }
        
        models.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m;
            opt.text = m;
            sel.add(opt);
        });
        
        // Restore value if exists, else first
        if (models.includes(currentVal)) {
            sel.value = currentVal;
        }
    }

    async function fetchModels() {
        const ok = await checkConnection();
        if (ok) showToast('Model listesi güncellendi.');
        else showToast('Modeller alınamadı, bağlantıyı kontrol edin.', 'error');
    }

    async function checkSettings() {
        try {
            const res = await fetch('chat_api.php?action=get_settings');
            const data = await res.json();
            if (data.ok) {
                document.getElementById('baseUrlInput').value = data.base_url || 'https://api.openai.com/v1';
                document.getElementById('apiKeyInput').value = data.api_key || '';
                
                // Set model
                const savedModel = data.model || 'gpt-4o';
                
                // If we haven't fetched list yet, put this model as option
                const sel = document.getElementById('modelSelect');
                let found = false;
                for(let i=0; i<sel.options.length; i++) {
                    if(sel.options[i].value === savedModel) found = true;
                }
                if (!found) {
                     const opt = document.createElement('option');
                     opt.value = savedModel;
                     opt.text = savedModel;
                     sel.add(opt);
                }
                sel.value = savedModel;
                
                if (!data.has_key) { 
                    // Optional open
                } else {
                    // Try to check connection on load if key exists
                    // checkConnection(); // Maybe too aggressive on load? Let's just check silently?
                    // User asked for a light. Let's do it.
                    checkConnection();
                }
            }
        } catch(e) {}
    }

    function openSettingsModal() {
        document.getElementById('settingsModal').classList.remove('hidden');
    }

    async function saveSettings(quiet = false) {
        const k = document.getElementById('apiKeyInput').value.trim();
        const b = document.getElementById('baseUrlInput').value.trim();
        
        let m = "";
        if (document.getElementById('manualModelCheckbox').checked) {
            m = document.getElementById('modelInputCustom').value.trim();
        } else {
            m = document.getElementById('modelSelect').value;
        }
        
        if (!b) return quiet ? false : showToast('Sunucu adresi gereklidir.', 'error');
        
        const fd = new FormData();
        fd.append('action', 'save_settings');
        fd.append('api_key', k);
        fd.append('base_url', b);
        fd.append('model', m);
        
        try {
            const res = await fetch('chat_api.php', { method: 'POST', body: fd });
            const d = await res.json();
            if (d.ok) {
                if (!quiet) {
                    showToast('Ayarlar kaydedildi.');
                    document.getElementById('settingsModal').classList.add('hidden');
                }
                return true;
            } else {
                if (!quiet) showToast(d.error || 'Hata', 'error');
                return false;
            }
        } catch(e) { 
            if (!quiet) showToast('Bağlantı hatası', 'error'); 
            return false;
        }
    }

    // 2. Load Chat History
    async function loadChatList() {
        const listContainer = document.getElementById('chatParamsList');
        // listContainer.innerHTML = '<div class="text-center mt-4">...</div>';
        try {
            const res = await fetch('chat_api.php?action=list_chats');
            if (!res.ok) throw new Error('Sunucu hatası: ' + res.status);
            
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch(e) {
                console.error('JSON Parse Error:', text);
                throw new Error('Geçersiz sunucu yanıtı');
            }

            if (data.ok) {
                renderChatList(data.chats);
                // If no active chat but chats exist, select the first one
                if (!activeChatId && data.chats.length > 0) {
                    loadChat(data.chats[0].id);
                } else if (data.chats.length === 0) {
                    createNewChat();
                }
            } else {
                showToast(data.error || 'Liste yüklenemedi', 'error');
            }
        } catch(e) { 
            console.error(e); 
            document.getElementById('chatParamsList').innerHTML = '<div class="text-center text-red-500 text-sm mt-4">Hata: '+e.message+'</div>';
        }
    }

    function renderChatList(chats) {
        const list = document.getElementById('chatParamsList');
        list.innerHTML = '';
        chats.forEach(c => {
            const btn = document.createElement('button');
            const isActive = (c.id == activeChatId);
            btn.className = `flex items-center gap-3 rounded-lg px-3 py-2 w-full text-left transition ${isActive ? 'bg-primary/10 dark:bg-[#282e39] text-primary dark:text-white font-medium' : 'hover:bg-gray-100 dark:hover:bg-gray-800/50 text-gray-700 dark:text-gray-300'}`;
            btn.onclick = () => loadChat(c.id);
            btn.innerHTML = `
                <span class="material-symbols-outlined text-base">chat_bubble_outline</span>
                <p class="text-sm truncate flex-1">${c.title || 'Yeni Sohbet'}</p>
            `;
            list.appendChild(btn);
        });
    }

    async function createNewChat() {
        try {
            const res = await fetch('chat_api.php?action=new_chat');
            const data = await res.json();
            if (data.ok) {
                await loadChatList();
                loadChat(data.id);
            }
        } catch(e) { showToast('Hata', 'error'); }
    }
    
    async function deleteCurrentChat() {
        if (!activeChatId || !confirm('Bu sohbeti silmek istediğinize emin misiniz?')) return;
        try {
            const fd = new FormData();
            fd.append('action', 'delete_chat');
            fd.append('id', activeChatId);
            const res = await fetch('chat_api.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
                activeChatId = null;
                document.getElementById('messagesContainer').innerHTML = '';
                loadChatList();
            }
        } catch(e) {}
    }

    async function loadChat(id) {
        activeChatId = id;
        loadChatList(); 
        
        const container = document.getElementById('messagesContainer');
        container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500"><span class="animate-spin material-symbols-outlined">progress_activity</span></div>';
        
        try {
            const res = await fetch('chat_api.php?action=get_chat&id=' + id);
            const data = await res.json();
            if (data.ok) {
                document.getElementById('currentChatTitle').innerText = data.chat.title || 'Sohbet';
                renderMessages(data.messages);
            }
        } catch(e) {}
    }

    function renderMessages(msgs) {
        const box = document.getElementById('messagesContainer');
        box.innerHTML = '';
        if (msgs.length === 0) {
            box.innerHTML = '<div class="flex flex-col items-center justify-center h-full text-gray-500 opacity-50"><p>Henüz mesaj yok.</p></div>';
            return;
        }
        msgs.forEach(m => appendMessage(m.role, m.content));
        scrollToBottom();
    }

    function appendMessage(role, content) {
        const box = document.getElementById('messagesContainer');
        if (box.innerText.includes('Henüz mesaj yok')) box.innerHTML = '';
        
        const isUser = (role === 'user');
        const div = document.createElement('div');
        div.className = `flex items-start gap-4 ${isUser ? 'justify-end' : ''}`;
        
        const avatarUrl = isUser 
            ? 'https://lh3.googleusercontent.com/aida-public/AB6AXuAE3n2lFPFJl1o4E0DoxazyKuzW2PUen7MuLYASsGUMZrN0lvsIQlx7WSiMsWgXiLlIXfDreyNL01VIAJLS05M2HgbYL6D-thLZG24UZuzIqxZnQNk--Wx2ps8GUT3IKWnZYPlOegUcS8oFZ0Fnr28oR4Efw-dtnPtQBzBPryiuwMrvEUJs7Ec1_Vo_WsOqdiP4vTh0T-DDHF9lT2aun5jatpTDm6Y4oVj04lUUiHa60Iszjvj9xgUv2aOHrbVcwdABsYVFGa9Tmt0'
            : 'https://lh3.googleusercontent.com/aida-public/AB6AXuCom8dSzWKTxBKwGDyDRaVsy8xUe_kWOmFlqOixh3k9GFWda03ICh42LeGyniNblp6I11RQCCpiMCSzNeDNwHPFP95vwQYLuMBNv-SvKtzp-S1z_z6FdxeYL7LQuKSrfNJuhNQ8x3eu965A9e5fytJxKPMuVAKfwPvK7L3CQCV0sHU2hCAd24NU5t48NJAseUMP25cKhZbU616qR5OALiC7_Z6FmaNDFYmQ1Z5ajuCnOWIZXDsa61NgElk_uTZM1P1QdkV3-ERIVCs';
            
        const bubbleClass = isUser 
            ? 'rounded-xl rounded-br-none bg-primary text-white p-4 max-w-xl shadow-md'
            : 'rounded-xl rounded-bl-none bg-white dark:bg-[#23272f] border border-gray-100 dark:border-gray-700 p-4 max-w-xl shadow-sm';
            
        const name = isUser ? 'Siz' : 'Yapay Zeka';
        const nameClass = isUser ? 'text-blue-100' : 'text-gray-900 dark:text-white';
        const textClass = isUser ? 'text-white' : 'text-gray-800 dark:text-gray-200';
        
        let html = `
            ${isUser ? '' : `<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8 shrink-0 shadow-sm" style='background-image: url("${avatarUrl}");'></div>`}
            <div class="flex flex-col gap-1 ${bubbleClass}">
                <div class="text-xs font-bold ${nameClass} opacity-80">${name}</div>
                <div class="text-base font-normal leading-relaxed ${textClass} whitespace-pre-wrap">${content ? escapeHtml(content) : '<span class="animate-pulse">...</span>'}</div>
            </div>
            ${isUser ? `<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8 shrink-0 shadow-sm" style='background-image: url("${avatarUrl}");'></div>` : ''}
        `;
        div.innerHTML = html;
        box.appendChild(div);
        scrollToBottom();
        return div;
    }

    async function sendMessage() {
        if (!activeChatId) return showToast('Önce bir sohbet seçin veya oluşturun.', 'info');
        const input = document.getElementById('messageInput');
        const txt = input.value.trim();
        if (!txt) return;
        
        input.value = '';
        input.style.height = 'auto'; 
        
        appendMessage('user', txt);
        
        const waitingDiv = appendMessage('assistant', '');
        const contentDiv = waitingDiv.querySelector('.whitespace-pre-wrap');
        
        const btn = document.getElementById('sendBtn');
        btn.disabled = true;

        try {
            const fd = new FormData();
            fd.append('action', 'send_message');
            fd.append('chat_id', activeChatId);
            fd.append('message', txt);
            
            const res = await fetch('chat_api.php', { method: 'POST', body: fd });
            const data = await res.json();
            
            if (data.ok) {
                contentDiv.innerHTML = escapeHtml(data.response);
                contentDiv.classList.remove('animate-pulse');
                loadChatList(); 
            } else {
                contentDiv.innerText = "Hata: " + (data.error || 'Bilinmeyen hata');
                contentDiv.className += " text-red-500";
            }
        } catch(e) {
            contentDiv.innerText = "Bağlantı hatası.";
            contentDiv.className += " text-red-500";
        }
        btn.disabled = false;
        scrollToBottom();
        // Focus input
        document.getElementById('messageInput').focus();
    }

    function scrollToBottom() {
        const box = document.getElementById('messagesContainer');
        box.scrollTop = box.scrollHeight;
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    const tx = document.getElementById('messageInput');
    tx.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        if(this.value === '') this.style.height = 'auto';
    });
    
    // Auto focus
    tx.focus();

    window.onload = () => {
        checkSettings();
        loadChatList();
    };
  </script>
</body>
</html>
