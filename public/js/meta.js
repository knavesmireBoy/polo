/*jslint nomen: true */
/* eslint-disable indent */
/* eslint-disable no-param-reassign */
/*global poloAfrica: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}

if (typeof Function.prototype.wrap === "undefined") {
  /*
  Function.prototype.wrap = function (wrapper, ..._vs) {
    let _method = this; //the function
    return function (...vs) {
      return wrapper.apply(this, [_method.bind(this), ..._vs, ...vs]);
    };
  };
*/
  Function.prototype.wrap = function (wrapper, ...first) {
    var method = this;
    return function (...next) {
      let last = [...first, ...next];
      if (wrapper) {
        return wrapper(method.bind(this), ...last);
      }
    };
  };
}

String.prototype.capitalize = function (char) {
  var splitter = char || " ",
    res = this.split(splitter),
    mapper = function (str) {
      return str.charAt(0).toUpperCase() + str.slice(1);
    };
  return res.map(mapper).join(" ");
};

function longestRepetition(s) {
  var count = 0;
  var temp = s.charAt(0);
  var arr = [];

  for (var i = 0; i < s.length; i++) {
    if (temp === s.charAt(i)) {
      count++;
      temp = s.charAt(i); // Not necessary: was already equal
    } else {
      arr.push([temp, count]); // <--- pair, BEFORE changing temp
      temp = s.charAt(i);
      count = 1;
    }
    if (i == s.length - 1) arr.push([temp, count]); // <---
  }

  if (arr.length > 0) {
    var Max = arr[0]; // <-- Max is now a pair of char & count
    for (var i = 0; i < arr.length; i++) {
      if (Max[1] < arr[i][1])
        // Comparison changed to just less-than
        Max = arr[i];
    }
  } else Max = [null, 0]; // Must be a pair here also

  return Max; // Just return the pair
}

// JavaScript program to find the maximum consecutive
// repeating character in given string

// Function to find out the maximum repeating
// character in given string
function maxRepeating(s, limit, exceptions) {
  let n = s.length,
    maxCnt = 0,
    res = s[0],
    cnt = 1;

  for (let i = 1; i < n; i++) {
    if (cnt > limit) {
      res = null;
      break;
    }
    if (s[i] === s[i - 1] && !exceptions.includes(s[i])) {
    } else {
      cnt = 1;
    }
    if (cnt > maxCnt) {
      maxCnt = cnt;
      res = s[i - 1] || res;
    }
  }
  return res;
}

window.requestAnimationFrame =
  window.requestAnimationFrame ||
  window.mozRequestAnimationFrame ||
  window.webkitRequestAnimationFrame ||
  window.msRequestAnimationFrame ||
  function (f) {
    "use strict";
    //return window.setTimeout(f, (1000 / 60));
    return window.setTimeout(f, 16.666);
  }; // simulate calling code 60
window.cancelAnimationFrame =
  window.cancelAnimationFrame ||
  window.mozCancelAnimationFrame ||
  window.webkitCancelAnimationFrame ||
  window.msCancelAnimationFrame ||
  function (requestID) {
    "use strict";
    window.clearTimeout(requestID);
  }; //fall back

poloAfrica.meta = (function () {
  "use strict";

  const supportsES6 = (function () {
    try {
      new Function("(a = 0) => a");
      //  alert('ohgood')
      return true;
    } catch (err) {
      //  alert('ohno')
      return false;
    }
  })();

  function existy(x) {
    return x != null;
  }

  function truthy(x) {
    return x !== false && existy(x);
  }

  function gimmeTruth(x) {
    return truthy(x) ? getResult(x) : x;
  }

  function fnull(fun, ...defaults) {
    return function (...args) {
      var myargs = args.map((e, i) => (existy(e) ? e : defaults[i]));
      return fun(...myargs);
    };
  }

  function pApply(fn, ...cache) {
    return (...args) => {
      const all = [...cache, ...args];
      return all.length >= fn.length ? fn(...all) : pApply(fn, ...all);
    };
  }

  function pApplyDefer(fn, ...cache) {
    return (...args) => {
      const all = cache.concat(args),
        reached = all.length >= fn.length;
      return reached ? () => fn(...all) : pApplyDefer(fn, ...all);
    };
  }

  function escapeRegex(string = "") {
    if (RegExp.escape) {
      return RegExp.escape(string);
    }
    return string.replace(/[/\-\\^$*+?.()|[\]{}]/g, "\\$&");
  }

  function reverseArray(array) {
    var i,
      L = array.length,
      old;
    //FRWL, YOLT, OHMSS, LALD
    array = Array.from(array); //slice?
    for (i = 0; i < Math.floor(L / 2); i += 1) {
      old = array[i];
      //1:FRWL / LALD
      //2: YOLT / OHMSS
      array[i] = array[L - 1 - i];
      array[L - 1 - i] = old;
    }
    return array;
  }

  function shout(m, ...args) {
    var applier = function (f, ...args) {
      return function (...newargs) {
        return f(...args, ...newargs);
      };
    };
    return applier(window[m].bind(window), ...args);
  }

  function doBestDefer(coll, pred, arg) {
    let group = coll, //could be a group of primitives, objects, functions...
      domap = (fn, ag) => (isFunction(fn) ? curryDefer(fn)(ag) : fn),
      func = pred;
    if (typeof arg != "undefined") {
      if (isArray(arg)) {
        //map args to specific functions
        if (arg[1] && isArray(arg[1])) {
          group = coll.map((item, i) => domap(item, arg[i]));
        } else {
          //arg[1] for group
          //could be one arg for group and predicate, or separate, arg[1] conditionally exists
          group = coll.map((item) => domap(item, arg[1] || arg[0]));
        }
        //arg[0] for predicate
        func = curryDefer(pred)(arg[0]);
      } else if (isFunction(arg)) {
        //if function assumes arg is for group only
        group = coll.map((item) => domap(item, getResult(arg)));
      } else {
        //assumes arg is for predicate only
        group = coll;
        func = curryDefer(pred)(arg);
      }
    }
    return group.reduce((champ, contender) =>
      func(champ, contender) ? champ : contender
    );
  }

  function doBestInvoke(coll, pred, arg) {
    let group = coll,
      domap = (fn, ag) => (isFunction(fn) ? curry(fn)(ag) : fn),
      func = pred;
    if (typeof arg != "undefined") {
      /*problemo the idea here is to divert the arg to the pred/action or both by wrapping them in an array OR a function or nothing BUT what if the arg is already a function or an array before the wrap it would be BEST to tailor this to individual use cases
       */
      if (isArray(arg)) {
        if (arg[1] && isArray(arg[1])) {
          group = coll.map((item, i) => domap(item, arg[i]));
        } else {
          group = coll.map((item) => domap(item, arg[1] || arg[0]));
        }
        func = curry(pred)(arg[0]);
      } else if (isFunction(arg)) {
        group = coll.map((item) => domap(item, getResult(arg)));
      } else {
        group = coll;
        func = curry(pred)(arg);
      }
    }
    return group.reduce((champ, contender) =>
      func(champ, contender) ? champ : contender
    );
  }

  /*
    function composeVerbose (...fns) {
      return fns.reduce((f, g) => {
        return (...vs) => {
        //console.log(f, g, ...vs);
          return f(g(...vs));
        };
      });
    }
  */

  const isDefined = (x) => typeof x !== "undefined",
    tagTester = (name) => {
      const tag = "[object " + name + "]";
      return function (obj) {
        return toString.call(obj) === tag;
      };
    },
    isArray = tagTester("Array"),
    isBoolean = tagTester("Boolean"),
    isFunction = tagTester("Function"),
    isNumber = tagTester("Number"),
    isString = tagTester("String"),
    getResult = (o) => (isFunction(o) ? o() : o),
    byId = (str) => document.getElementById(str),
    byIdDefer = (str) => () => byId(str),
    byTag = (str, flag = false) => {
      const m = flag ? "querySelectorAll" : "querySelector";
      return document[m](str);
    },
    byTagScope =
      (context) =>
      (str, flag = false) => {
        const m = flag ? "querySelectorAll" : "querySelector";
        return context[m](str);
      },
    curryDefer = (fun) => (a) => () => fun(a),
    curry = (fun) => (a) => fun(a),
    doPartial = (flag) => {
      return function p(f, ...args) {
        //allow for overriding default arguments
        if (!isNumber(flag)) {
          if (f.length === args.length) {
            return flag ? () => f(...args) : f(...args);
          }
        } else {
          if (f.length + flag === args.length) {
            return f(...args);
          }
        }
        return (...rest) => p(f, ...args, ...rest);
      };
    },
    compose = (...fns) =>
      fns.reduce(
        (f, g) =>
          (...vs) =>
            f(g(...vs))
      ),
    doWhenFactory = (n) => {
      let both = (pred, action, v) => {
          if (truthy(pred(v))) {
            return action(v);
          }
        },
        act = (pred, action, v) => {
          if (truthy(getResult(pred))) {
            return action(v);
          }
        },
        predi = (pred, action, v) => {
          if (truthy(pred(v))) {
            return getResult(action);
          }
        },
        none = (pred, action) => {
          if (gimmeTruth(pred)) {
            return getResult(action);
          }
        },
        comp = (pred, action, seed) => {
          let res = pred(seed);
          return res ? action(res) : null;
        },
        all = [none, predi, act, both, comp];
      return all[n] || none;
    },
    doWhen = (pred, action) => {
      return gimmeTruth(pred) ? action(pred) : pred;
    },
    //for signatures resistent to straightforward partial application or currying
    //largely assumes we need to return an element for further processing
    mittelFactory = (arg) => {
      let res;
      if (arg && isBoolean(arg)) {
        return (f, o, v = undefined) =>
          //dynamic method (add/remove etc)
          (m) => {
            res = f(o, m, v);
            return res || o;
          };
      } else if (!arg && isBoolean(arg)) {
        return (f, m, v = undefined) =>
          //optional key/value; dynamic key
          (o, k) => {
            res = isDefined(v) ? f(o, m, k, v) : f(o, m, v);
            return res || o;
          };
      }
      //typical use park STATIC values
      return (f, m, k = undefined) => {
        //optional key/value;dynamic value
        return (o, v) => {
          //optional callback;typically get sub property, or getResult
          if (isFunction(arg)) {
            res = isDefined(k) ? f(arg(o), m, k, v) : f(arg(o), m, v);
            return res || o;
          }
          res = isDefined(k) ? f(o, m, k, v) : f(o, m, v);
          return res || o;
        };
      };
    },
    curryRight = (i, defer = false) => {
      const once = {
          imm: (fn) => (a) => fn(a),
          def: (fn) => (a) => () => fn(a),
        },
        twice = {
          imm: (fn) => (b) => (a) => {
            return fn(a, b);
          },
          def: (fn) => (b) => (a) => () => fn(a, b),
        },
        thrice = {
          imm: (fn) => (c) => (b) => (a) => fn(a, b, c),
          def: (fn) => (c) => (b) => (a) => () => fn(a, b, c),
        },
        quart = {
          imm: (fn) => (d) => (c) => (b) => (a) => fn(a, b, c, d),
          def: (fn) => (d) => (c) => (b) => (a) => () => fn(a, b, c, d),
        },
        options = [null, once, twice, thrice, quart],
        ret = options[i],
        noOp = () => {
          return false;
        };
      return ret && defer ? ret.def : ret ? ret.imm : noOp;
    },
    curryLeft = (i, defer = false) => {
      const once = {
          imm: (fn) => (a) => fn(a),
          def: (fn) => (a) => () => fn(a),
        },
        twice = {
          imm: (fn) => (a) => (b) => fn(a, b),
          def: (fn) => (a) => (b) => () => fn(a, b),
        },
        thrice = {
          imm: (fn) => (a) => (b) => (c) => fn(a, b, c),
          def: (fn) => (a) => (b) => (c) => () => fn(a, b, c),
        },
        quart = {
          imm: (fn) => (a) => (b) => (c) => (d) => fn(a, b, c, d),
          def: (fn) => (a) => (b) => (c) => (d) => () => fn(a, b, c, d),
        },
        options = [null, once, twice, thrice, quart],
        ret = options[i],
        noOp = () => {
          return false;
        };
      return ret && defer ? ret.def : ret ? ret.imm : noOp;
    },
    toArray = (coll, cb = () => true) => {
      let i = 0,
        arr,
        grp = [];
      if (isArray(coll)) {
        while (coll[i]) {
          arr = Array.prototype.slice.call(coll[i]).filter(cb);
          grp = grp.concat(arr);
          i++;
        }
        return grp;
      }
      if (coll) {
        return Array.prototype.slice.call(coll).filter(cb);
      }
      return [];
    },
    best = (fun, coll) => {
      return coll.reduce((champ, contender) =>
        fun(champ, contender) ? champ : contender
      );
    },
    bestLog = (fun, coll) => {
      return coll.reduce((champ, contender) => {
        let res = fun(champ, contender);
        console.log("log", res, champ);
        return res ? champ : contender;
      });
    },
    //can't assign i to another variable
    alternate = (i, n) => () => (i += 1) % n,
    doAlternate = (j = 2) => {
      const f = alternate(0, j);
      return (actions, predicate = true) => {
        let [uno, duo] = getResult(predicate) ? actions : actions.reverse();
        //a more sophisticated version would examine type of arg and apply to actions/predicate accordingly
        return (arg) => {
          if (arg) {
            return best(f, [pApply(uno, arg), pApply(duo, arg)])();
          }
          return best(f, [uno, duo])();
        };
      };
    },
    invokeMethod = (o, m, v) => {
      try {
        return getResult(o)[m](v);
      } catch (e) {
        return getResult(o)[m](getResult(v));
      }
    },
    invokePropertyMethod = (o, p, m, k, v) => {
      return getResult(o)[p][m](k, v);
    },
    invoke = (f, v) => f(getResult(v)),
    invokePair = (o, m, k, v) => {
      return getResult(o)[m](k, v);
    },
    invokePairBridge = (o, m, arr) => invokePair(o, m, ...arr),
    soInvoke = (o, m, ...rest) => o[m](...rest),
    invokeEach = (m, funs) => {
      return (o) => {
        if (funs) {
          let obj = getResult(o);
          funs[m]((f) => f(obj));
          return obj;
        }
        return o;
      };
    };
  return {
    $: byId,
    $$: byIdDefer,
    $Q: byTag,
    $$Q:
      (str, flag = false) =>
      () =>
        byTag(str, flag),
    byTagScope: byTagScope,
    compose: compose,
    getResult: getResult,
    tagTester: tagTester,
    doWhen: doWhen,
    doWhenFactory: doWhenFactory,
    doBest: doBestDefer,
    doBestInvoke: doBestInvoke,
    prepBestArgs: function (type, arg) {
      if (isArray(type)) {
        return [arg];
      } else if (isFunction(type)) {
        return always(arg);
      }
      return arg;
    },
    doPartial: doPartial,
    doOnce: (i) => (cb) => {
      if (i) {
        cb();
        i -= 1;
      }
    },
    setter: (o, k, v) => {
      // console.log(o,k,v)
      let obj = getResult(o);
      obj[k] = v;
    },
    setterBridge:
      (pre = getResult, post = getResult) =>
      (o, k, v) => {
        // console.log(o,k,v)
        let obj = pre(o);
        obj[k] = v;
        return post(obj);
      },
    pApply: pApply,
    pass: (ptl, o) => {
      ptl(getResult(o));
      return o;
    },
    always: (a) => () => a,
    alwaysLog: (a) => () => {
      console.log(a);
      return a;
    },
    defer:
      (flag) =>
      (fn, ...cache) => {
        return (...args) => {
          const all = cache.concat(args),
            pass = all.length >= fn.length;
          if (pass && !flag) {
            return fn(...all);
          } else if (pass && flag) {
            return () => fn(...all);
          }
          return pApply(fn, ...all);
        };
      },
    doParallel: (arr1, arr2) => {
      return arr2.map((item, index) => arr1[index](item));
    },
    //https://stackoverflow.com/questions/3561493/is-there-a-regexp-escape-function-in-javascript
    escapeRegex: escapeRegex,
    prepareEscape: (str, capture = false) => {
      str = escapeRegex(str);
      str = capture ? `(${str})` : str;
      return new RegExp(str);
    },
    identity: (a) => a,
    isFunction: isFunction,
    isBoolean: isBoolean,
    curryRight: curryRight,
    curryLeft: curryLeft,
    mittelFactory: mittelFactory,
    invoke: invoke,
    invoker: curryRight(2)(invoke),
    invokeMethod: invokeMethod,
    invokeMethodBind: (o, m, v) => {
      return getResult(o)[m].call(o, v);
    },
    invokeMethodV: (o, p, m, v) => {
      return getResult(o)[p][v](m);
    },
    invokePropertyMethod: invokePropertyMethod,
    invokePair: invokePair,
    invokeEach: invokeEach,
    isArray: isArray,
    join: (sep, ...args) =>
      args
        .filter((x) => x)
        .filter((item, i, self) => self.indexOf(item) === i)
        .join(sep),
    fillArray: (rpt, item = "") => {
      if (Array.prototype.fill) {
        return new Array(rpt).fill(item);
      } else {
        let arr = [];
        if (rpt > 0) {
          while (item) {
            arr.push(item);
          }
          item--;
        }
        return arr;
      }
    },
    lazyVal: (m, p, o, v) => {
      return getResult(o)[m](p, v);
    },
    includes: (o, p) => {
      if (isArray(o)) {
        if (o.includes) {
          return o.includes(p);
        } else {
          return o.indexOf(p) !== -1;
        }
      }
    },
    ///invokeMethodBridge: (m, v, o) => invokeMethod(o, m, v),
    invokeMethodBridge: (m, v, o) => {
      return isArray(v) ? invokePair(o, m, v[0], v[1]) : invokeMethod(o, m, v);
    },
    invokeMethodBridgeCB: (cb) => (m, v, o) => {
      //console.log(cb(o), m, v);
      return invokeMethod(cb(o), m, v);
    },
    invokeClass: (o, s, m, v) => getResult(o)[s][m](v),
    isString: isString,
    isNumber: isNumber,
    negate: (f, ...args) => !f(...args),
    defernegate:
      (f, ...args) =>
      () =>
        !f(...args),
    negator:
      (f, ...args) =>
      (...rest) =>
        !f(...args, ...rest),
    myIndexOf:
      (rev = false) =>
      (haystack, needle) => {
        let i = haystack.indexOf(needle);
        if (i >= 0) {
          return rev ? Number(!i) : i;
        }
      },
    queryDuplicates: (a, flag = false) => {
      return flag ? a.filter((n, i) => a.indexOf(n) === i) : a.filter((n, i) => a.indexOf(n) !== i);
    },
    zip: (m, funs, vals) => vals[m]((v, i) => funs[i](v)),
    eitherOr: (a, b, pred) => (pred ? a : b),
    compare: (pred) => (p, a, b) => {
      return typeof p === "string"
        ? //compare common Property of two objects
          pred(a[p], b[p])
        : p
        ? //compare two Properties of one object
          pred(p[a], p[b])
        : pred(a, b);
    },
    toArray: toArray,
    doAlternate: doAlternate,
    driller: (o, p) => o[p] || o,
    getter: (o, p) => {
      return getResult(o)[p];
    },
    getTgt: (str) => byIdDefer(str),
    getDiff: (a, b) => {
      let curry2 = curryRight(2),
        validate = curry2((o, p) => o[p])(0),
        res = a.filter((x) => !b.includes(x)),
        output = b.filter((x) => !a.includes(x));
      a = validate(res);
      b = validate(output);
      return a || b;
    },
    soInvoke: soInvoke,
    best: best,
    getLast: (o, i = 1) => {
      if (isArray(o)) {
        return o[o.length - i];
      }
    },
    reverse: reverseArray,
    deferCB: pApplyDefer,
    doTest: function (x, ...args) {
      console.log(x, ...args);
      return x;
    },
    supportsES6: supportsES6,
    shout: shout,
    maxRepeating: maxRepeating,
  };
})();
