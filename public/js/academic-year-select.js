(function () {
  var picker = document.querySelector('.yrsel-picker');
  if (!picker) return;

  var form = picker.closest('form');
  var select = picker.querySelector('select');
  var trigger = picker.querySelector('.yrsel-trigger');
  var current = picker.querySelector('.yrsel-current');
  var menu = picker.querySelector('.yrsel-menu');
  var options = Array.from(menu.querySelectorAll('[data-year-id]'));

  function closeMenu(returnFocus) {
    menu.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    if (returnFocus) trigger.focus();
  }

  function openMenu() {
    menu.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
    var selected = menu.querySelector('[aria-checked="true"]') || options[0];
    if (selected) selected.focus();
  }

  function selectYear(option) {
    var changed = select.value !== option.dataset.yearId;
    select.value = option.dataset.yearId;
    options.forEach(function (item) {
      item.setAttribute('aria-checked', item === option ? 'true' : 'false');
    });
    current.textContent = option.textContent.trim();
    closeMenu(false);
    if (changed) form.requestSubmit();
    else trigger.focus();
  }

  current.textContent = select.options[select.selectedIndex].text;
  picker.classList.add('is-enhanced');

  trigger.addEventListener('click', function () {
    if (menu.hidden) openMenu();
    else closeMenu(false);
  });

  options.forEach(function (option, index) {
    option.addEventListener('click', function () {
      selectYear(option);
    });
    option.addEventListener('keydown', function (event) {
      var nextIndex = index;
      if (event.key === 'ArrowDown') nextIndex = (index + 1) % options.length;
      else if (event.key === 'ArrowUp') nextIndex = (index - 1 + options.length) % options.length;
      else if (event.key === 'Home') nextIndex = 0;
      else if (event.key === 'End') nextIndex = options.length - 1;
      else if (event.key === 'Escape') {
        event.preventDefault();
        closeMenu(true);
        return;
      } else if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        selectYear(option);
        return;
      } else {
        return;
      }

      event.preventDefault();
      options[nextIndex].focus();
    });
  });

  document.addEventListener('pointerdown', function (event) {
    if (!picker.contains(event.target) && !menu.hidden) closeMenu(false);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !menu.hidden) closeMenu(true);
  });
})();
