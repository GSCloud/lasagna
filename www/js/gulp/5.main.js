window.addEventListener("load", function() {
  if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("/sw.js?" + Date.now()).then(function(registration) {
      console.log("Service Worker registration successful with scope: ", registration.scope);
    }, function (err) {
      console.log("Service Worker registration failed: ", err);
    });
  } else {
    console.log("Service Worker is not supported!");
  }
  (function ($) {
    $(function() {
      M.AutoInit();
      window.GSC.AutoInit();
    });
  })(jQuery, window);
});
