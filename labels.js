  function setupLabels(fields) {
    for (var i = 0, elm; elm = fields[i]; i++) {
      if (elm.type == "text" || elm.type == "password") {
	if (elm.value == "") {
	  elm._type = elm.type;
	  setDynamicLabel(elm);
	}

	addEvent(elm, "focus", focusDynamicLabel);
	addEvent(elm, "blur", blurDynamicLabel);
      }
    }
  }

  function resetLabels(fields) {
    for (var i = 0, elm; elm = fields[i]; i++) {
      if (elm.type == "text" || elm.type == "password") {
	if (elm.value == elm.getAttribute("label")) {
	  elm.value = "";
	}
      }
    }
  }

  function addEvent(objObject, strEventName, fnHandler) {
    if (objObject.addEventListener)
      objObject.addEventListener(strEventName, fnHandler, false);
    else if (objObject.attachEvent)
      objObject.attachEvent("on" + strEventName, fnHandler);
  }

  function focusDynamicLabel(event) {
    var elm = getEventSrc(event);
    if (elm.value == elm.getAttribute("label")) {
      if (elm._type == "password") {
	elm = setInputType(elm, "password", true);
      }

      elm.value = "";
    }
  }

  function blurDynamicLabel(event) {
    // wierd... sometimes, it seems like the blur gets fired after unload, so 
    // this fucntion is gone.
    if (typeof window.getEventSrc == "undefined") {
      return;
    }

    var elm = getEventSrc(event);
    setDynamicLabel(elm);
  }

  function setDynamicLabel(elm) {
    if ("" == elm.value) {
      if (elm.type == "password") {
	elm = setInputType(elm, "text", false);
      }

      elm.value = elm.getAttribute("label");
    }
  }

  function getEventSrc(e) {
    if (!e) e = window.event;

    if (e.originalTarget)
      return e.originalTarget;
    else if (e.srcElement)
      return e.srcElement;
  }

  function setInputType(el, type, focus) {
    if (navigator.appName == "Microsoft Internet Explorer") {
      var span = document.createElement("SPAN");
      span.innerHTML = 
	'<input id="' + el.id + '" type="' + type + '" class="' + 
	el.className + '" label="' + el.getAttribute("label") + '">';

      var newEl = span.firstChild;
      el.parentNode.replaceChild(newEl, el);

      newEl._type = el._type;

      if (focus) {
	window.setTimeout(function() { newEl.focus(); }, 0);
      }

      addEvent(newEl, "focus", focusDynamicLabel);
      addEvent(newEl, "blur", blurDynamicLabel);

      return newEl;
    } else {
      el.type = type;
      return el;
    }
  }



