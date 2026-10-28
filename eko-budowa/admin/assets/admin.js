(function () {
  'use strict';
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-confirm]');
    if (b && !confirm(b.getAttribute('data-confirm'))) e.preventDefault();
  });
  var inp = document.getElementById('photos'), zone = document.getElementById('drop');
  if (inp && zone) {
    inp.addEventListener('change', function () { if (inp.files.length) { zone.classList.add('busy'); inp.form.submit(); } });
    ['dragover', 'dragenter'].forEach(function (ev) {
      zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      zone.addEventListener(ev, function () { zone.classList.remove('over'); });
    });
    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      if (e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; zone.classList.add('busy'); inp.form.submit(); }
    });
  }
})();
