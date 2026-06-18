/*jslint nomen: true */
/*global window: false */
/*global document: false */
/*global Modernizr: false */
/*global poloAfrica: false */
/*global _: false */

/* NOTE LINKS ENDING WITH A TRAILING SLASH WILL BREAK OUT OF AJAXLAND */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}

(function () {
  "use strict";

  function checkAll() {
    var grp = [].slice.call(this.form.elements),
      cb = (el) => (el.checked = el.checked ? "" : checked),
      cb2 = (el) => {
        console.log(el, el.checked, checked);
        return (el.checked = el.checked ? "" : checked);
      },
      checked,
      boxes = grp
        .filter((el) => el.type.match(/checkbox/i))
        .filter((el) => el.id !== "all")
        .filter((el) => el.id !== "backup");
    checked = this.checked;
    boxes.forEach(cb);
  }

  function traffic(defVal) {
    let tgt = meta.$Q(".edit_asset"),
      rpl = meta.$Q(".edit_asset.replace"),
      rplcol = "rgba(55, 0, 180, 0.5)",
      rplcol2 = "rgba(55, 0, 180, 0.7)",
      col = "rgba(30, 142, 241, 0.5)",
      def = "rgba(255,255,255,.5)";
    return function () {
      if (this.value && this.value !== defVal) {
        tgt.style.backgroundColor = rpl ? rplcol2 : col;
      } else {
        tgt.style.backgroundColor = rpl ? rplcol : def;
      }
    };
  }

  function checkUploadFile(e) {
    let val,
      addKlas = ptL(meta.invokeMethodBridge, "add"),
      getClassList = curry2(meta.getter)("classList"),
      doPdf = meta.compose(addKlas("pdf"), getClassList),
      uploadfile = document.getElementById("uploadfile");
    if (uploadfile) {
      val = uploadfile.value ? uploadfile.value : "";
    }
    if (val) {
      val = val.split(".").pop();
    }
    if (val === "pdf") {
      let node = utils.getTargetNode(e.target, /div/i, "parentNode");
      if (node) {
        doPdf(node);
      }
    }
  }

  function checkArticlePosition(data) {
    //forces reload to public page if order changes,
    //months later how??
    let res = data.match(/position=(\d)/);
    return res ? Number(res[1]) : res;
  }

  function checkCrazyCharacters(data) {
    let curry3 = meta.curryRight(3),
      filtered,
      ret,
      res;

    if (data) {
      res = decodeURI(data);

      if (res) {
        res = res.split("&");
      }
      if (res) {
        res = res.filter((s) => s.match(/^content/));
      }
      if (res[0]) {
        res = res[0].substring(8);
      } else {
        return false;
      }
      res = res.replace(/\n+/g, "\n");
      res = res.replace(/\s+/g, " "); //fails if we end up with an empty string
      res = res.split(" ");
      res = res.filter((item) => item);
      ret = res.map(curry3(meta.maxRepeating)(["#"])(3));
      filtered = ret.filter((item) => item);
      return filtered.length !== ret.length;
    }
    return false;
  }

  function on_submit(sz = 666) {
    if (document.getElementById("upload").files[0].size > sz) {
      alert("File is too big.");
      return false;
    }
    return true;
  }

  function displayLoading() {
    var image = document.createElement("img"),
      element = document.querySelector(".pic");
    image.setAttribute("attr_id", "loading...");
    image.setAttribute("src", "/resources/images/dev/progressbar.gif");
    image.className = "loading";
    if (element) {
      element.appendChild(image);
    }
  }
  //https://codeshack.io/file-upload-progress-bar-js-php/
  function uploadProgress(e) {
    let calc = () => Math.round((e.loaded / e.total) * 100),
      el = document.querySelector(".uploadpreview form"),
      button = el.querySelector("input[type=submit]");
    if (el) {
      el.style.background =
        "linear-gradient(to right, #54008b, #54008b " +
        calc() +
        "%, #176b3d " +
        calc() +
        "%)";
    }
    button.value = "Uploading... " + "(" + calc().toFixed(2) + "%)";
  }

  function removeGuide(e) {
    let res = confirm(
      "Clicking OK will hide the guide, you can manually restore it or restart the browser."
    );
    if (res) {
      utils.setCookie("upload_guide", "upload_guide", false);
    }
    return res;
  }

  function restoreGuide(e) {
    utils.deleteAllCookies("upload_guide");
  }

  var meta = poloAfrica.meta,
    utils = poloAfrica.utils,
    log = console.log,
    defer = meta.doPartial(true),
    ptL = meta.doPartial(),
    invoke = (f, a) => f(a),
    thunk = (f) => f(),
    curry2 = meta.curryRight(2),
    curry3 = meta.curryRight(3),
    getMyLink = curry3(utils.getTargetNode)("parentNode")(/^a$/i),
    helpers = utils.getAjaxHelpers([
      /gallery\/display/,
      /resources\/assets/,
      /mailto/,
      /\w+\/$/,
      /^#/,
    ]);

  function Hijax() {
    var canvas,
      callback,
      container,
      errorhandler,
      loading,
      url,
      data,
      request,
      timer,
      submitValidators = [checkArticlePosition],
      ajaxClickCB = function (e) {
        let a = getMyLink(e.target);
        if (!a || a.nodeName !== "A") {
          return false;
        }
        url += a.getAttribute("href");
        return !start();
      },
      ajaxWrapper = function (orig, e) {
        if (e.target.id === "exit_guide") {
          let res = removeGuide();
          if (!res) {
            return false;
          }
        }
        if (e.target.id === "restore_guide") {
          restoreGuide();
        }
        return orig(e);
      },
      ajaxCBWrap = ajaxClickCB.wrap(ajaxWrapper),
      completeRequest = function (e) {
        if (request.readyState == 4) {
          if (request.status == 200 || request.status == 304) {
            clearTimeout(timer);
            if (canvas) {
              canvas.innerHTML = "";
              helpers.processResponse(canvas, request, url);
            }
            meta.getResult(callback);
          } else {
            meta.getResult(errorhandler);
          }
        }
      },
      initiateRequest = function () {
        // meta.getResult(loading);
        //4
        timer = setTimeout(function () {
          request.onreadystatechange = function () {};
          request.abort();
          meta.getResult(errorhandler);
        }, 60000);

        request.onreadystatechange = completeRequest;
        if (data) {
          request.open("POST", url, true);
          request.setRequestHeader(
            "Content-Type",
            "application/x-www-form-urlencoded"
          );
          request.send(data);
        } else {
          request.open("GET", url, true);
          request.send(null);
        }
      },
      start = function () {
        request = utils.getHTTPObject();
        if (request && url.match(/\/\w+/i)) {
          initiateRequest();
          return true;
        } else {
          return false;
        }
      },
      postFormData = function (form) {
        if (typeof FormData === "undefined") {
          throw new Error("FormData is not implemented");
        }
        request = utils.getHTTPObject();
        request.upload.addEventListener("progress", uploadProgress);
        request.open("POST", url);
        request.onreadystatechange = completeRequest;
        var formdata = new FormData(form);
        request.send(formdata);
      },
      isAutoSub = function (el) {
        return el.classList.contains("autosub");
      },
      //took a while to work out
      getAutoSub = function (container) {
        let t = container.querySelector("#title"),
          tx = container.querySelector("#tx");
        if (isAutoSub(container)) {
          let select = container.querySelector("select"),
            number = container.querySelector("input[type=number]"),
            els = [select, number].filter((item) => item);
          els.forEach((el) => (el.onchange = (e) => e.target.form.onsubmit(e)));
        }
        if (t) {
          t.onchange = poloAfrica.markup(tx);
        }
      },
      mysubmit = function (e) {
        e.stopPropagation();
        let tgt = e.target.elements ? e.target : e.target.form,
          res;
        [data] = helpers.fromPost(tgt);
        res = submitValidators.every(curry2(invoke)(data));
        if (checkCrazyCharacters(data)) {
          return false;
        }
        return res || !start();
      };

    function Constr() {}

    Constr.prototype = {
      constructor: Constr,
      setCallback: (value) => (callback = value),
      setCanvas: (value) => (canvas = value),
      setContainer: (value) => (container = value),
      setErrorHandler: (value) => (errorhandler = value),
      setLoading: (value) => (loading = value),
      setUrl: (value) => (url = value),
      captureData: () => {
        if (container) {
          if (container.elements) {
            if (container.enctype && container.enctype.match("multipart")) {
              container.onsubmit = function (e) {
                e.stopPropagation();
                e.preventDefault();
                postFormData(e.target);
              };
            } else {
              container.onsubmit = mysubmit;
              getAutoSub(container);
            }
          } else {
            container.onclick = ajaxCBWrap;
          }
        }
      },
    };
    return new Constr();
  }

  var init = function (element) {
    var path = "",
      xhr = Hijax(),
      path =
        element && element.elements
          ? path + element.getAttribute("action")
          : path,
      record = document.getElementById("records"),
      setrecord = document.getElementById("setrecords"),
      ajaxCB = () => {
        var forms = document.forms,
          links = meta.$Q("#content a", true),
          controls = document.getElementById("controls"),
          attr_id = document.getElementById("attr_id"),
          tx = document.getElementById("tx"),
          i = 0;

        for (i = 0; i < forms.length; i++) {
          if (helpers.includeForms(forms[i])) {
            init(forms[i]);
          }
        }
        for (i = 0; i < links.length; i++) {
          if (helpers.includeLinks(links[i])) {
            init(links[i]);
          }
        }
        if (controls && !controls.onclick && tx) {
          controls.onclick = poloAfrica.markup(tx);
        }
        if (attr_id && !attr_id.onclick) {
          attr_id.onclick = checkUploadFile;
        }
      },
      checkbox = meta.$("all"),
      assetedit = meta.$Q(".edit_asset #path");
      /*
    if (checkbox) {
      checkbox.onchange = checkbox.onchange || checkAll;
    }
      */
    if (assetedit) {
      assetedit.onchange = assetedit.onchange || traffic(assetedit.value);
    }
    setrecord && setrecord.appendChild(record);
    xhr.setContainer(element);
    xhr.setCanvas(document.body);
    xhr.setCallback(ajaxCB);
    /*
    xhr.setLoading(function () {
     displayLoading();
    });
    */
    xhr.setUrl(path);
    xhr.captureData();
    if (!element) {
      ajaxCB();
    }
  };
  addEventListener("DOMContentLoaded", defer(init, null));
})();
