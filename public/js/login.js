(function () {
  var gearModule = 64 / 3;
  document.querySelectorAll('.login-gear-artwork [data-teeth]').forEach(function (gear) {
    var teeth = Number(gear.getAttribute('data-teeth'));
    var hole = Number(gear.getAttribute('data-hole'));
    var pitchRadius = teeth * gearModule / 2;
    var rootRadius = pitchRadius - gearModule / 2;
    var tipRadius = pitchRadius + gearModule / 2;
    var toothStep = Math.PI * 2 / teeth;
    var points = [];

    for (var tooth = 0; tooth < teeth; tooth += 1) {
      var center = tooth * toothStep;
      [
        [-0.36, rootRadius],
        [-0.25, rootRadius],
        [-0.2, tipRadius],
        [0.2, tipRadius],
        [0.25, rootRadius],
        [0.36, rootRadius]
      ].forEach(function (point) {
        var angle = center + point[0] * toothStep;
        points.push(
          (Math.cos(angle) * point[1]).toFixed(2) + ' ' +
          (Math.sin(angle) * point[1]).toFixed(2)
        );
      });
    }

    gear.setAttribute(
      'd',
      'M' + points.join('L') + 'Z M0-' + hole +
      'A' + hole + ' ' + hole + ' 0 1 0 0 ' + hole +
      'A' + hole + ' ' + hole + ' 0 1 0 0-' + hole + 'Z'
    );
    gear.setAttribute('fill-rule', 'evenodd');
  });

  var password = document.getElementById('login-password');
  var toggle = document.getElementById('password-toggle');
  var reminder = document.getElementById('capslock-reminder');
  var capsLockActive = false;
  if (!password) return;

  if (toggle) {
    toggle.addEventListener('click', function () {
      var visible = password.type === 'password';
      password.type = visible ? 'text' : 'password';
      toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
      toggle.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
    });
  }

  function updateCapsLock(event) {
    if (!reminder) return;
    if (event.key === 'CapsLock') {
      if (event.type === 'keydown' && !event.repeat) {
        capsLockActive = !capsLockActive;
      }
    } else if (event.getModifierState) {
      capsLockActive = event.getModifierState('CapsLock');
    }
    reminder.hidden = !capsLockActive;
  }

  password.addEventListener('keydown', updateCapsLock);
  password.addEventListener('keyup', updateCapsLock);
  password.addEventListener('blur', function () {
    if (reminder) reminder.hidden = true;
  });
})();
