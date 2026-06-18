/*jslint nomen: true */
/*global window: false */
/*global poloAfrica: false */
/*global _: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}
poloAfrica.Iterator = function (rev) {
  "use strict";

  function modulo(n, i) {
    return i % n;
  }

  function increment(i) {
    return i + 1;
  }

  function equals(a, b) {
    return a === b;
  }

  function getProp(o, p) {
    return o[p];
  }

  function extractLast(path) {
    var neu = path.split("/"),
      str = neu.pop(),
      pre = neu.join("/");
    return [str, (pre += "/")];
  }

  function doMatch(str) {
    return str.match(/\//);
  }

  function negate(f) {
    return function (...args) {
      return !f(...args);
    };
  }

  return function (coll, index = 0, prepath = "") {
    var meta = poloAfrica.meta,
      curry2 = (f) => (b) => (a) => f(a, b),
      soEqual = curry2(equals),
      getSrc = curry2(getProp)("src"),
      ptl = meta.doPartial(),
      defer = meta.doPartial(true),
      best = meta.doBest,
      doInc = function (n) {
        return meta.compose(ptl(modulo, n), increment);
      },
      notMatch = negate(doMatch),
      getFromIndex = function (i) {
        return coll[i];
      },
      notMatchBridge = meta.compose(notMatch, getSrc),
      noOp = function () {},
      switchDirection = function () {
        coll = meta.reverse(coll);
        index = coll.length - 1 - index;
        rev = !rev;
      },
      isReversed = function () {
        return rev === true;
      },
      notReversed = meta.defernegate(isReversed),
      loop = function (bool) {
        var inc = ptl(doInc, coll.length);
        if (!bool) {
          index = inc(index);
        }
        return index;
      },
      getNext = function (isRev, bool = false) {
        best([switchDirection, noOp], isRev)();
        let next = getFromIndex(loop(bool));
        next.prepath = prepath;
        return next;
      },
      getCurrent = function () {
        let next = getFromIndex(loop(true));
        next.prepath = prepath;
        return next;
      },
      findIndex = function (cb) {
        let cbBridge = meta.compose(cb, getSrc),
          i = coll.findIndex(cbBridge);
        setIndex(i);
        return i;
      },
      init = function () {
        let first = getFromIndex(0);
        if (isNaN(index)) {
          if (doMatch(index) && notMatchBridge(first)) {
            let [str, pre] = extractLast(index);
            prepath = pre;
            findIndex(soEqual(str));
          }
        }
        //only run AFTER index is found, obvs
        if (rev) {
          switchDirection();
        }
        return this;
      },
      setIndex = function (arg) {
        index = arg;
      },
      forward = defer(getNext, isReversed),
      back = defer(getNext, notReversed),
      invoke = function (bool = false) {
        return best([back, forward], isReversed)();
      },
      ret = {
        init: init,
        getNext: invoke,
        forward: forward,
        back: back,
        getCurrent: getCurrent,
        findIndex: findIndex,
        getIndex: function () {
          return index;
        },
        setIndex: setIndex,
        getLength: function () {
          return coll.length;
        },
        getCollection: function () {
          return coll;
        },
      };

    return ret;
  };
};
