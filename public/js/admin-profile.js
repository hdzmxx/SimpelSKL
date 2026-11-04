(function () {
  var dialog = document.getElementById('admin-profile-dialog');
  var trigger = document.getElementById('admin-profile-open');
  if (!dialog || !trigger || typeof dialog.showModal !== 'function') return;

  trigger.addEventListener('click', function () {
    if (!dialog.open) dialog.showModal();
  });
  dialog.addEventListener('click', function (event) {
    if (event.target === dialog || event.target.closest('[data-admin-dialog-close]')) dialog.close();
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && dialog.open) dialog.close();
  });
  if (dialog.dataset.reopen === '1') dialog.showModal();
})();
