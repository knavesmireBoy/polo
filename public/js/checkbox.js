/*jslint nomen: true */
/*global poloAfrica: false */
/*global setTimeout: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}
(function (query) {
  "use strict";

  function throttle(callback, time) {
    if (throttlePause) {
      return;
    }
    throttlePause = true;
    setTimeout(() => {
      callback();
      throttlePause = false;
    }, time);
  }

  function isSmall(n) {
    return window.viewportSize.getWidth() <= n;
  }

  let throttlePause,
    meta = poloAfrica.meta,
    utils = poloAfrica.utils,
    cb = (str) => (el) => {
      return el.appendChild(meta.$Q(str));
    },
    gang = meta.toArray(meta.$Q(".radio div", true)),
    exec = function() {
        gang.map(cb('label'));
    },
    undo = function() {
        gang.map(cb('input'));
    },
    threshold = Number(query.match(new RegExp("[^\\d]+(\\d+)[^\\d]+"))[1]),
    getPredicate = meta.pApply(isSmall, threshold),
    init = meta.doAlternate()([exec, undo], getPredicate),
    handler = () => {
      if (!getPredicate()) {
        getPredicate = meta.defernegate(getPredicate);
        init();
      }

    };
  window.addEventListener("resize", meta.pApply(throttle, handler, 66));
  if (!getPredicate()) {
    getPredicate = meta.defernegate(getPredicate);
  }
  window.onload = function () {
    init();
  };
})("(max-width: 430px)");
