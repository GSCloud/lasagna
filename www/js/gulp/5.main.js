window.GSC.loaderOff();

window.addEventListener("load", function() {
  if ("serviceWorker" in navigator) {
    console.log("Service worker...");
    navigator.serviceWorker.register("/sw.js?" + Date.now()).then(function(registration) {
      console.log("ServiceWorker registration successful with scope: ", registration.scope);
    }, function (err) {
      console.log("ServiceWorker registration failed: ", err);
    });
  } else {
    console.log("Service worker is not supported!");
  }
  (function ($) {
    $(function() {
      M.AutoInit();
      window.GSC.AutoInit();
    });
  })(jQuery, window);
});
