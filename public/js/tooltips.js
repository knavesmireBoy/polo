/*jslint browser: true*/
/*jslint nomen: true */
/*global window: false */
/*global poloAfrica: false */
/*global Modernizr: false */
/*global document: false */
/*global _: false */
/*global poloAfrica: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}
window.poloAfrica.Tooltip = function (anchor, instr, count, remove) {
  "use strict";
  var meta = poloAfrica.meta,
    utils = poloAfrica.utils,
    $ = meta.$,
    add = utils.addKlas,
    list = utils.getClassList,
    ptL = meta.doPartial(),
    defer = meta.curryRight(1, 1),
    compose = meta.compose,
    curry3 = meta.curryRight(3),
    getMyDiv = curry3(utils.getTargetNode)("firstChild")(/div/i),
    doElement = compose(utils.prepend(anchor), utils.doMakeDefer("div")),
    timeout = function (fn, delay, el) {
      return setTimeout(defer(fn)(el), delay);
    },
    isPos = function (i) {
      return i > 0;
    },
    prep = function () {
      var gang = [],
        a = compose(add("tip"), list),
        b = compose(utils.remKlas("tip"), list),
        c = compose(add("tb1"), list),
        d = compose(add("tb2"), list),
        git = function () {
          if (instr[1] && $("tooltip")) {
            //$('tooltip') may not exist if cancel has been called
            var parent = $("tooltip");
            if (parent) {
              utils.setInnerHTML(getMyDiv(parent.firstChild), instr[1]);
            }
          }
        },
        wrap = function (f, el) {
          git();
          return f(el);
        };

      gang.push(ptL(timeout, a, 1000));
      gang.push(ptL(timeout, b, 9000));
      gang.push(ptL(timeout, c.wrap(wrap), 4000));
      gang.push(ptL(timeout, d, 6500));
      return gang;
    },
    exit = function (delay) {
      var that = this;
      window.setTimeout(function () {
        that.cancel();
      }, delay);
    },
    init = function () {
      if (isPos((count -= 1))) {
        var tip = compose(
            meta.pApply(timer.run.bind(timer), prep()),
            utils.setId("tooltip").wrap(meta.pass),
            doElement
          )(),
          makeDiv = compose(utils.prepend(tip), utils.doMakeDefer("div"));
        utils.setInnerHTML(makeDiv(), instr[0]);
        utils.setId("triangle")(makeDiv());
      }
      if (remove) {
        exit.call(this, 10000);
      }
      return this;
    },
    run = function (gang, el) {
      var invoke = function (partial) {
        return partial(el);
      };
     if(gang && gang.map){
      this.ids = gang.map(invoke, this);
     }
      return el;
    },
    dummytimer = {
      init: function () {},
      run: function () {},
      ids: [],
      cancel: function () {},
    },
    timer = {
      init: init,
      run: function (gang, el) {
        if (meta.$Q('.tip')) {
          return el;
        }
        return run.bind(this, gang, el)();
      },
      ids: [],
      cancel: function () {
        this.ids.forEach(window.clearTimeout);
        this.ids = [];
        utils.removeElement($("tooltip"));
      },
    };
  return Modernizr.cssanimations ? timer : dummytimer;
};
