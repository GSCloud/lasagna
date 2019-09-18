(function (w, undefined) {
  var a = w.GSC || {};
  a.init = [];
  a.names = [];

  a.AutoInit = function (debug) {
    for (var a = window.GSC.init, n = window.GSC.names, i = 0, l = a.length; i < l; i++) try {
      a[i]();
      if (debug) console.log("fn #" + (i + 1) + ": " + n[i])
    } catch (e) {}
  }

  w.GSC = a;
})(window);
