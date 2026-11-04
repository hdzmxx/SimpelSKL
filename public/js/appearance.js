(function () {
  var modes = ['system', 'light', 'dark'];
  var accents = ['blue', 'green', 'purple', 'teal'];
  var mode = localStorage.getItem('nk-theme-mode') || 'system';
  var accent = localStorage.getItem('nk-theme-accent') || 'blue';
  var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

  function applyTheme() {
    var dark = mode === 'dark' || (mode === 'system' && media && media.matches);
    document.documentElement.dataset.theme = dark ? 'dark' : 'light';
    document.documentElement.dataset.accent = accent;
  }

  function updateControls() {
    var modeInput = document.querySelector('input[name="theme-mode"][value="' + mode + '"]');
    var accentInput = document.querySelector('input[name="theme-accent"][value="' + accent + '"]');
    if (modeInput) modeInput.checked = true;
    if (accentInput) accentInput.checked = true;
  }

  if (!modes.includes(mode)) mode = 'system';
  if (!accents.includes(accent)) accent = 'blue';
  applyTheme();

  if (media) media.addEventListener('change', function () {
    if (mode === 'system') applyTheme();
  });

  document.addEventListener('DOMContentLoaded', function () {
    updateControls();
    var status = document.getElementById('appearance-status');
    document.querySelectorAll('input[name="theme-mode"]').forEach(function (input) {
      input.addEventListener('change', function () {
        if (!input.checked) return;
        mode = input.value;
        localStorage.setItem('nk-theme-mode', mode);
        applyTheme();
        if (status) status.textContent = 'Tema tampilan berhasil diterapkan.';
      });
    });
    document.querySelectorAll('input[name="theme-accent"]').forEach(function (input) {
      input.addEventListener('change', function () {
        if (!input.checked) return;
        accent = input.value;
        localStorage.setItem('nk-theme-accent', accent);
        applyTheme();
        if (status) status.textContent = 'Kombinasi warna berhasil diterapkan.';
      });
    });
    var reset = document.getElementById('appearance-reset');
    if (reset) reset.addEventListener('click', function () {
      mode = 'system';
      accent = 'blue';
      localStorage.removeItem('nk-theme-mode');
      localStorage.removeItem('nk-theme-accent');
      applyTheme();
      updateControls();
      if (status) status.textContent = 'Tampilan dikembalikan ke pengaturan bawaan.';
    });
  });
})();
