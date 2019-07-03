window.cookieconsent.initialise({
  "palette": {
    "popup": {
      "background": window.cceudata.ui.popup_background
    },
    "button": {
      "background": window.cceudata.ui.button_background,
      "border": window.cceudata.ui.button_border,
      "text": window.cceudata.ui.button_text
    }
  },
  "content": {
    "dismiss": window.cceudata.content.dismiss,
    "link": window.cceudata.content.link,
    "message": window.cceudata.content.message
  },
  "position": window.cceudata.ui.position,
  "static": (window.cceudata.ui.static == "true")
});
