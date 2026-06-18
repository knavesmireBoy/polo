/*jslint nomen: true */
/*global window: false */
/*global document: false */
/*global Modernizr: false */
/*global poloAfrica: false */
/*global _: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}

(function (x) {
  "use strict";
  var meta = poloAfrica.meta,
    utils = poloAfrica.utils,
    ptL = meta.doPartial(),
    defer = meta.doPartial(true),
    curry3 = meta.curryRight(3),
    curryL3 = meta.curryLeft(3),
    invoke = (o, m, v) => o[m](v),
    getMyLink = curry3(utils.getTargetNode)("parentNode")(/^a$/i),
    helpers = utils.getAjaxHelpers([
      /user\/admin/,
      /gallery\/display/,
      /\.pdf$/,
      /#$/,
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
      completeRequest = function (e) {
        if (request.readyState == 4) {
          if (request.status == 200 || request.status == 304) {
            clearTimeout(timer);
            if (canvas) {
              canvas.innerHTML = "";
              helpers.processResponse(canvas, request, url, '.wrap', true);
            }
            meta.getResult(callback);
          } else {
            meta.getResult(errorhandler);
          }
        }
      },
      initiateRequest = function () {
        meta.getResult(loading);
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
      start = function (uri = "") {
        request = utils.getHTTPObject();
        url = uri || url;
        if (request) {
          initiateRequest();
          url = "";
          return true;
        } else {
          return false;
        }
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
        let func = meta.doAlternate(),
          foo = (p, v) => (m) => (o) => o[p][m](v),
          bar = foo("classList", "show"),
          toggle = func([bar("add"), bar("remove")]);
        if (container) {
          if (container.elements) {
            container.onsubmit = function (e) {
              e.stopPropagation();
              e.preventDefault();
              [data] = helpers.fromPost(e.target);
              return !start(this.getAttribute("action"));
            };
          } else {
            container.onclick = function (e) {
              let a = getMyLink(e.target),
                internal = a.href.split("#");
                internal = internal[1] ? meta.$(internal[1]) : null;

              if (internal) {
                internal.scrollIntoView();
                return false;
              }
              url += a.getAttribute("href");
              return !start();
            }; //onclick
          }
        }
      },
    };
    return new Constr();
  }
  var init = function (element) {
    var path = "",
      xhr = Hijax(), //new instance for every element
      i = 0,
      links = meta.$Q(".wrap a", true),
      forms = document.forms;
    xhr.setContainer(element);
    xhr.setCanvas(meta.$Q("body"));
    //defer(init, element)
    //none required as NEW page is delivered every time
    xhr.setCallback(() => {});
    xhr.setUrl(path);
    xhr.captureData();
    if (!element) {
      for (i = 0; i < forms.length; i++) {
        init(forms[i]);
      }
      for (i = 0; i < links.length; i++) {
        if (links[i] && helpers.includeLinks(links[i])) {
          init(links[i]);
        }
      }
    }
    ;
  };
  addEventListener("DOMContentLoaded", ptL(init, null));
})(0);
