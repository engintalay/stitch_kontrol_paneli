<?php
// admin_panel.php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: home.php');
    exit;
}
function h($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html class="dark" lang="tr">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Admin Paneli</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
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
    .material-symbols-outlined {
      font-variation-settings:
        'FILL' 0,
        'wght' 400,
        'GRAD' 0,
        'opsz' 24
    }
  </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="flex flex-col w-64 bg-white dark:bg-[#19202e] border-r border-gray-200 dark:border-gray-700/50">
      <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-200 dark:border-gray-700/50">
        <div class="size-8 text-white bg-primary rounded-lg flex items-center justify-center">
          <span class="material-symbols-outlined">admin_panel_settings</span>
        </div>
        <h1 class="text-lg font-bold text-gray-800 dark:text-white">Admin Paneli</h1>
      </div>
      <nav class="flex-1 p-4 space-y-2">
        <a class="menu-item flex items-center gap-3 px-3 py-2 rounded-lg bg-primary/20 text-primary dark:bg-primary/30" href="#" data-target="panel">
          <span class="material-symbols-outlined">dashboard</span>
          <p class="text-sm font-medium">Panel</p>
        </a>
        <a class="menu-item flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#" data-target="users">
          <span class="material-symbols-outlined">group</span>
          <p class="text-sm font-medium">Kullanıcı Yönetimi</p>
        </a>
        <a class="menu-item flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#" data-target="modules">
          <span class="material-symbols-outlined">apps</span>
          <p class="text-sm font-medium">Uygulama Yönetimi</p>
        </a>
      </nav>
      <div class="p-4 border-t border-gray-200 dark:border-gray-700/50">
        <div class="flex gap-3 items-center">
          <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Admin User Profile Picture" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAhqz9JqIJs2JaRgJ7EquDXzK6o2-YrZovVCbZ7W6YwYNVcJ5hrFxytNkXkZ1LTyfUQV9P8SsrHLNineB6miq-pmJzswSa7CImUGtEHSoZ7gZoKbOLvUDK1BUl0E4oBB7DdFZUm7QPgaDdKp5nDnfdjzQySEhfu-cl2JJ8saXZrVZo-VWre7MJ6w7wH6kiM9aEDX_aScxnyFMkCC_NNBK9fn_HawC2iTfMPeSVHKfk6kapF7H6-mtcGlQO9v2k4J5XMA99FVihim8Q");'></div>
          <div class="flex flex-col">
            <h1 class="text-gray-800 dark:text-white text-sm font-medium leading-normal">
              <?php echo isset($_SESSION['user_email']) ? h($_SESSION['user_email']) : 'Admin Kullanıcı'; ?>
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-xs font-normal leading-normal">
              <?php echo isset($_SESSION['user_role']) ? 'Rol: ' . h($_SESSION['user_role']) : 'admin@example.com'; ?>
            </p>
          </div>
        </div>
      </div>
    </aside>
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <header class="flex items-center justify-between whitespace-nowrap border-b border-gray-200 dark:border-gray-700/50 px-8 py-4 bg-white dark:bg-[#19202e]">
        <div class="flex items-center gap-8 flex-1">
          <span class="font-bold text-xl text-primary">Admin Paneli</span>
        </div>
        <div class="flex items-center gap-3">
          <button id="toggleSessionDebug" class="mr-3 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-sm rounded">Oturum Bilgisi</button>
          <a href="home.php" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">Ana Sayfa</a>
        </div>
      </header>
      <main class="flex-1 overflow-y-auto p-8">
        <div class="max-w-2xl mx-auto">
          <h1 class="text-3xl font-bold mb-2 text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-3xl">admin_panel_settings</span>
            Admin Paneli
          </h1>
          <p class="text-gray-500 dark:text-gray-400 mb-6">Yönetici işlemleri ve sistem yönetimi burada yapılır.</p>
          <div class="bg-white dark:bg-[#23272f] rounded-lg shadow p-0 min-h-[120px]">
            <div id="content-panel" class="content-section p-6">
              Yönetici işlemleri ve sistem yönetimi burada yapılır.
            </div>
            <div id="content-users" class="content-section hidden p-6">
              <div class="flex justify-between mb-4">
                <button id="btnAddUser" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                  <span class="material-symbols-outlined">person_add</span>
                  Yeni Kullanıcı Ekle
                </button>
                <form id="userSearchForm" class="flex items-center gap-2">
                  <input type="text" id="userSearchInput" placeholder="Kullanıcı ara..." class="form-input rounded-lg px-3 py-2 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-[#23272f] text-sm"/>
                  <button type="submit" class="px-3 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                    <span class="material-symbols-outlined text-base">search</span>
                  </button>
                </form>
              </div>
              <div id="userTableWrap">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                      <th class="py-2 px-3 text-left">ID</th>
                      <th class="py-2 px-3 text-left">E-posta</th>
                      <th class="py-2 px-3 text-left">Rol</th>
                      <th class="py-2 px-3 text-left">Yetkiler</th>
                      <th class="py-2 px-3 text-left">İşlemler</th>
                    </tr>
                  </thead>
                  <tbody id="userTableBody">
                    <tr><td colspan="5" class="text-center py-4 text-gray-400">Yükleniyor...</td></tr>
                  </tbody>
                </table>
              </div>
              <!-- Kullanıcı Ekle/Düzenle Modalı -->
              <div id="userModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden">
                <div class="bg-white dark:bg-[#23272f] rounded-lg shadow-lg p-6 w-full max-w-md relative">
                  <button id="closeUserModal" class="absolute top-2 right-2 text-gray-400 hover:text-red-500"><span class="material-symbols-outlined">close</span></button>
                  <h2 id="userModalTitle" class="text-lg font-bold mb-4">Yeni Kullanıcı</h2>
                  <form id="userForm" class="space-y-4">
                    <input type="hidden" name="id" id="userId" />
                    <div>
                      <label class="block mb-1 text-sm font-medium">E-posta</label>
                      <input type="email" name="email" id="userEmail" class="form-input w-full" required />
                    </div>
                    <div id="userPasswordWrap">
                      <label class="block mb-1 text-sm font-medium">Şifre</label>
                      <input type="password" name="password" id="userPassword" class="form-input w-full" required />
                    </div>
                    <div>
                      <label class="block mb-1 text-sm font-medium">Rol</label>
                      <select name="role" id="userRole" class="form-select w-full">
                        <option value="admin">Admin</option>
                        <option value="user">Kullanıcı</option>
                      </select>
                    </div>
                    <div>
                      <label class="block mb-1 text-sm font-medium">Yetkiler</label>
                      <div class="flex flex-wrap gap-2">
                        <label><input type="checkbox" name="permissions[]" value="panel" class="mr-1">Panel</label>
                        <label><input type="checkbox" name="permissions[]" value="kullanici" class="mr-1">Kullanıcı Yönetimi</label>
                        <label><input type="checkbox" name="permissions[]" value="uygulama" class="mr-1">Uygulama Yönetimi</label>
                        <label><input type="checkbox" name="permissions[]" value="sistem" class="mr-1">Sistem Ayarları</label>
                        <label><input type="checkbox" name="permissions[]" value="rapor" class="mr-1">Raporlar</label>
                        <label><input type="checkbox" name="permissions[]" value="kayit" class="mr-1">Kayıtlar</label>
                      </div>
                    </div>
                    <div class="flex justify-end gap-2">
                      <button type="button" id="cancelUserModal" class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">İptal</button>
                      <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white font-semibold">Kaydet</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <div id="content-modules" class="content-section hidden p-6">
              <div class="flex justify-between mb-4">
                <button id="btnAddModule" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                  <span class="material-symbols-outlined">add_box</span>
                  Yeni Modül Ekle
                </button>
              </div>
              <div id="moduleTableWrap">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                      <th class="py-2 px-3 text-left">ID</th>
                      <th class="py-2 px-3 text-left">Modül Adı</th>
                      <th class="py-2 px-3 text-left">Açıklama</th>
                      <th class="py-2 px-3 text-left">Durum</th>
                      <th class="py-2 px-3 text-left">İşlemler</th>
                    </tr>
                  </thead>
                  <tbody id="moduleTableBody">
                    <tr><td colspan="5" class="text-center py-4 text-gray-400">Yükleniyor...</td></tr>
                  </tbody>
                </table>
              </div>
              <!-- Modül Ekle/Düzenle Modalı -->
              <div id="moduleModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden">
                <div class="bg-white dark:bg-[#23272f] rounded-lg shadow-lg p-6 w-full max-w-md relative">
                  <button id="closeModuleModal" class="absolute top-2 right-2 text-gray-400 hover:text-red-500"><span class="material-symbols-outlined">close</span></button>
                  <h2 id="moduleModalTitle" class="text-lg font-bold mb-4">Yeni Modül</h2>
                  <form id="moduleForm" class="space-y-4">
                    <input type="hidden" name="id" id="moduleId" />
                    <div>
                      <label class="block mb-1 text-sm font-medium">Modül Adı</label>
                      <input type="text" name="name" id="moduleName" class="form-input w-full" required />
                    </div>
                    <div>
                      <label class="block mb-1 text-sm font-medium">Açıklama</label>
                      <input type="text" name="desc" id="moduleDesc" class="form-input w-full" />
                    </div>
                    <div>
                      <label class="block mb-1 text-sm font-medium">Durum</label>
                      <select name="status" id="moduleStatus" class="form-select w-full">
                        <option value="aktif">Aktif</option>
                        <option value="pasif">Pasif</option>
                      </select>
                    </div>
                    <div class="flex justify-end gap-2">
                      <button type="button" id="cancelModuleModal" class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">İptal</button>
                      <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white font-semibold">Kaydet</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <div class="mt-8 text-center text-sm text-gray-600 dark:text-gray-300">
            </main>
            <?php if (isset($_SESSION['user_email']) || isset($_SESSION['user_id'])): ?>
              <span class="font-semibold">Giriş yapan kullanıcı:</span>
              <?php
                $user = [];
                if (isset($_SESSION['user_email'])) $user[] = htmlspecialchars($_SESSION['user_email']);
                if (isset($_SESSION['user_id'])) $user[] = 'ID: ' . htmlspecialchars($_SESSION['user_id']);
                if (isset($_SESSION['user_role'])) $user[] = 'Rol: ' . htmlspecialchars($_SESSION['user_role']);
                echo implode(' | ', $user);
              ?>
            <?php else: ?>
              <span class="text-red-500">Kullanıcı oturumu bulunamadı.</span>
            <?php endif; ?>
          </div>
        </div>
      </main>
      <div id="sessionDebug" class="hidden fixed bottom-4 right-4 w-80 bg-white dark:bg-[#23272f] border border-gray-200 dark:border-gray-700 rounded p-4 shadow-lg text-xs overflow-auto max-h-60">
        <div class="flex justify-between items-center mb-2">
          <strong>SESSION</strong>
          <button id="closeSessionDebug" class="text-sm text-gray-500">Kapat</button>
        </div>
        <pre style="white-space:pre-wrap;word-break:break-word;"><?php echo htmlspecialchars(print_r($_SESSION, true)); ?></pre>
      </div>
    </div>
  </div>
  <script>
    document.getElementById('toggleSessionDebug').addEventListener('click', function(){
      document.getElementById('sessionDebug').classList.toggle('hidden');
    });
    document.getElementById('closeSessionDebug').addEventListener('click', function(){
      document.getElementById('sessionDebug').classList.add('hidden');
    });
    // Menü ve içerik yönetimi
    const menuItems = document.querySelectorAll('.menu-item');
    const contentSections = document.querySelectorAll('.content-section');

    menuItems.forEach(item => {
      item.addEventListener('click', (e) => {
        e.preventDefault();
        const target = item.getAttribute('data-target');

        // Seçili menü öğesini vurgula
        menuItems.forEach(i => i.classList.remove('bg-primary/20', 'text-primary', 'dark:bg-primary/30'));
        item.classList.add('bg-primary/20', 'text-primary', 'dark:bg-primary/30');

        // İlgili içerik bölümünü göster
        contentSections.forEach(section => section.classList.add('hidden'));
        document.getElementById(`content-${target}`).classList.remove('hidden');
      });
    });

    // Modül yönetimi AJAX ve modal
    const moduleTableBody = document.getElementById('moduleTableBody');
    const moduleModal = document.getElementById('moduleModal');
    const moduleForm = document.getElementById('moduleForm');
    const btnAddModule = document.getElementById('btnAddModule');
    const closeModuleModal = document.getElementById('closeModuleModal');
    const cancelModuleModal = document.getElementById('cancelModuleModal');
    const moduleModalTitle = document.getElementById('moduleModalTitle');
    const moduleId = document.getElementById('moduleId');
    const moduleName = document.getElementById('moduleName');
    const moduleDesc = document.getElementById('moduleDesc');
    const moduleStatus = document.getElementById('moduleStatus');

    function fetchModules() {
      moduleTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-400">Yükleniyor...</td></tr>';
      fetch('module_api.php?action=list', { headers: { 'Accept': 'application/json' } })
        .then(async r => {
          if (!r.ok) {
            const txt = await r.text();
            const msg = txt || 'Sunucu hatası';
            moduleTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">' + msg + '</td></tr>';
            showToast(msg, 'error');
            throw new Error('Module API error: ' + r.status);
          }
          try {
            return await r.json();
          } catch (e) {
            const msg = 'Geçersiz JSON yanıt';
            moduleTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">' + msg + '</td></tr>';
            showToast(msg, 'error');
            throw e;
          }
        })
        .then(data => {
          if (!data || !data.ok) return moduleTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">' + ((data && data.error) || 'Hata!') + '</td></tr>';
          if (!Array.isArray(data.modules) || !data.modules.length) return moduleTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-400">Modül bulunamadı.</td></tr>';
          moduleTableBody.innerHTML = data.modules.map(m => `
            <tr class="border-b border-gray-100 dark:border-gray-800">
              <td class="py-2 px-3">${m.id}</td>
              <td class="py-2 px-3">${m.name || ''}</td>
              <td class="py-2 px-3">${m.desc || ''}</td>
              <td class="py-2 px-3">${m.status || ''}</td>
              <td class="py-2 px-3">
                <button class="text-blue-600 hover:underline mr-2" onclick="editModule(${m.id}, '${(m.name||'').replace(/'/g, "&#39;")}', '${(m.desc||'').replace(/'/g, "&#39;")}', '${m.status||''}')">Düzenle</button>
                <button class="text-red-600 hover:underline" onclick="deleteModule(${m.id})">Sil</button>
              </td>
            </tr>
          `).join('');
        })
        .catch(err => {
          console.error('fetchModules error', err);
          showToast(err.message || 'Bir hata oluştu', 'error');
        });
    }

    function openModuleModal(edit = false, id = '', name = '', desc = '', status = 'aktif') {
      moduleModalTitle.textContent = edit ? 'Modülü Düzenle' : 'Yeni Modül';
      moduleId.value = id;
      moduleName.value = name;
      moduleDesc.value = desc;
      moduleStatus.value = status;
      moduleModal.classList.remove('hidden');
    }

    btnAddModule.onclick = () => openModuleModal(false);
    closeModuleModal.onclick = cancelModuleModal.onclick = () => moduleModal.classList.add('hidden');

    window.editModule = function(id, name, desc, status) {
      openModuleModal(true, id, name, desc, status);
    }

    window.deleteModule = function(id) {
      if (!confirm('Modül silinsin mi?')) return;
      fetch('module_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete&id=' + encodeURIComponent(id)
      })
        .then(r => r.json())
        .then(data => {
          if (!data.ok) return alert(data.error || 'Silinemedi!');
          fetchModules();
        });
    }

    moduleForm.onsubmit = function(e) {
      e.preventDefault();
      const form = new FormData(moduleForm);
      const isEdit = !!form.get('id');
      const payload = new URLSearchParams();
      payload.append('action', isEdit ? 'update' : 'add');
      if (isEdit) payload.append('id', form.get('id'));
      payload.append('name', form.get('name'));
      payload.append('desc', form.get('desc'));
      payload.append('status', form.get('status'));
      fetch('module_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: payload.toString()
      })
        .then(r => r.json())
        .then(data => {
          if (!data.ok) return alert(data.error || 'Hata!');
          moduleModal.classList.add('hidden');
          fetchModules();
        });
    }

    fetchModules();

            // Kullanıcı yönetimi AJAX ve modal
            const userTableBody = document.getElementById('userTableBody');
            const userModal = document.getElementById('userModal');
            const userForm = document.getElementById('userForm');
            const btnAddUser = document.getElementById('btnAddUser');
            const closeUserModal = document.getElementById('closeUserModal');
            const cancelUserModal = document.getElementById('cancelUserModal');
            const userModalTitle = document.getElementById('userModalTitle');
            const userId = document.getElementById('userId');
            const userEmail = document.getElementById('userEmail');
            const userPassword = document.getElementById('userPassword');
            const userPasswordWrap = document.getElementById('userPasswordWrap');
            const userRole = document.getElementById('userRole');
            const userSearchForm = document.getElementById('userSearchForm');
            const userSearchInput = document.getElementById('userSearchInput');

            function fetchUsers(q = '') {
              userTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-400">Yükleniyor...</td></tr>';
              fetch('user_api.php?action=list&q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(async r => {
                  if (!r.ok) {
                    const txt = await r.text();
                    const msg = txt || 'Sunucu hatası';
                    userTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">' + msg + '</td></tr>';
                    showToast(msg, 'error');
                    throw new Error('User API error: ' + r.status);
                  }
                  try {
                    return await r.json();
                  } catch (e) {
                    const msg = 'Geçersiz JSON yanıt';
                    userTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">' + msg + '</td></tr>';
                    showToast(msg, 'error');
                    throw e;
                  }
                })
                .then(data => {
                  if (!data || !data.ok) return userTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">' + ((data && data.error) || 'Hata!') + '</td></tr>';
                  if (!Array.isArray(data.users) || !data.users.length) return userTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-400">Kullanıcı bulunamadı.</td></tr>';
                  userTableBody.innerHTML = data.users.map(u => `
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                      <td class="py-2 px-3">${u.id}</td>
                      <td class="py-2 px-3">${u.email || ''}</td>
                      <td class="py-2 px-3">${u.role || ''}</td>
                      <td class="py-2 px-3">${(u.permissions||[]).map(p => `<span class='inline-block bg-primary/10 text-primary rounded px-2 py-0.5 text-xs mr-1 mb-1'>${p}</span>`).join('')}</td>
                      <td class="py-2 px-3">
                        <button class="text-blue-600 hover:underline mr-2" onclick="editUser(${u.id}, '${(u.email||'').replace(/'/g, "&#39;")}', '${u.role || ''}', ${JSON.stringify(u.permissions||[]).replace(/"/g, '&quot;')})">Düzenle</button>
                        <button class="text-red-600 hover:underline" onclick="deleteUser(${u.id})">Sil</button>
                      </td>
                    </tr>
                  `).join('');
                })
                .catch(err => {
                  console.error('fetchUsers error', err);
                  showToast(err.message || 'Bir hata oluştu', 'error');
                });
            }

            function openUserModal(edit = false, id = '', email = '', role = 'user', permissions = []) {
              userModalTitle.textContent = edit ? 'Kullanıcıyı Düzenle' : 'Yeni Kullanıcı';
              userId.value = id;
              userEmail.value = email;
              userRole.value = role;
              userPassword.value = '';
              userPasswordWrap.style.display = edit ? 'none' : '';
              // Yetkiler
              document.querySelectorAll('#userForm input[type=checkbox][name="permissions[]"]').forEach(cb => {
                cb.checked = permissions.includes(cb.value);
              });
              userModal.classList.remove('hidden');
            }

            btnAddUser.onclick = () => openUserModal(false);
            closeUserModal.onclick = cancelUserModal.onclick = () => userModal.classList.add('hidden');

            window.editUser = function(id, email, role, permissions) {
              openUserModal(true, id, email, role, permissions);
            }

            window.deleteUser = function(id) {
              if (!confirm('Kullanıcı silinsin mi?')) return;
              fetch('user_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=delete&id=' + encodeURIComponent(id)
              })
                .then(r => r.json())
                .then(data => {
                  if (!data.ok) return alert(data.error || 'Silinemedi!');
                  fetchUsers(userSearchInput.value);
                });
            }

            userForm.onsubmit = function(e) {
              e.preventDefault();
              const form = new FormData(userForm);
              const isEdit = !!form.get('id');
              const permissions = [];
              userForm.querySelectorAll('input[type=checkbox][name="permissions[]"]:checked').forEach(cb => permissions.push(cb.value));
              const payload = new URLSearchParams();
              payload.append('action', isEdit ? 'update' : 'add');
              if (isEdit) payload.append('id', form.get('id'));
              payload.append('email', form.get('email'));
              if (!isEdit) payload.append('password', form.get('password'));
              payload.append('role', form.get('role'));
              permissions.forEach(p => payload.append('permissions[]', p));
              fetch('user_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: payload.toString()
              })
                .then(r => r.json())
                .then(data => {
                  if (!data.ok) return alert(data.error || 'Hata!');
                  userModal.classList.add('hidden');
                  fetchUsers(userSearchInput.value);
                });
            }

            userSearchForm.onsubmit = function(e) {
              e.preventDefault();
              fetchUsers(userSearchInput.value);
            }

            fetchUsers();
            </script>
            <script>
              // Toast container and helper
              (function(){
                const container = document.createElement('div');
                container.id = 'toastContainer';
                container.className = 'fixed top-4 right-4 z-50 space-y-2';
                document.body.appendChild(container);

                window.showToast = function(message, type = 'info', timeout = 5000) {
                  const toast = document.createElement('div');
                  const bg = type === 'error' ? 'bg-red-600 text-white' : 'bg-gray-900 text-white';
                  toast.className = `px-4 py-2 rounded shadow ${bg} max-w-xs break-words`;
                  toast.style.opacity = '0';
                  toast.style.transition = 'opacity 150ms ease-in-out, transform 150ms ease-in-out';
                  toast.innerText = message;
                  container.appendChild(toast);
                  // animate in
                  requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });
                  const hide = () => {
                    toast.style.opacity = '0';
                    setTimeout(() => { toast.remove(); }, 180);
                  };
                  const t = setTimeout(hide, timeout);
                  toast.addEventListener('click', () => { clearTimeout(t); hide(); });
                };
              })();
            </script>
            </div>
          </div>
          <div class="mt-8 text-center text-sm text-gray-600 dark:text-gray-300">
            </main>
            <?php if (isset($_SESSION['user_email']) || isset($_SESSION['user_id'])): ?>
              <span class="font-semibold">Giriş yapan kullanıcı:</span>
              <?php
                $user = [];
                if (isset($_SESSION['user_email'])) $user[] = htmlspecialchars($_SESSION['user_email']);
                if (isset($_SESSION['user_id'])) $user[] = 'ID: ' . htmlspecialchars($_SESSION['user_id']);
                if (isset($_SESSION['user_role'])) $user[] = 'Rol: ' . htmlspecialchars($_SESSION['user_role']);
                echo implode(' | ', $user);
              ?>
            <?php else: ?>
              <span class="text-red-500">Kullanıcı oturumu bulunamadı.</span>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </div>
</body>
</html>
