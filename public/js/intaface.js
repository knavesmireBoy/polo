/*jslint nomen: true */
/*global window: false */
/*global poloAfrica: false */
/*global _: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}
(function (core) {
  "use strict";

  function noOp() {
    return function () {};
  }

  var tagTester = (name) => {
      var tag = "[object " + name + "]";
      return function (obj) {
        return toString.call(obj) === tag;
      };
    },
    beFunction = tagTester("Function"),
    beString = tagTester("String"),
    beArray = tagTester("Array");

  core.Intaface = function (name, methods) {
    if (this.Intaface) {
      //called without new: this == core.Intaface
      return new this.Intaface(name, methods);
    }
    var i = 0,
      L = methods && methods.length,
      args = arguments.length,
      plural = "",
      warnings = this.constructor.warnings,
      args2 = warnings.args2(),
      isEmpty = warnings.isEmpty(),
      isString = warnings.isString(),
      isArray = warnings.isArray();
    if (args !== 2) {
      plural = args ? "" : "s";
      throw new Error(args2(args, plural));
    }
    if (!beArray(methods)) {
      throw new Error(isArray());
    }
    this.name = name;
    this.methods = [];
    if (!L) {
      throw new Error(isEmpty());
    }
    for (i = 0; i < L; i += 1) {
      if (!beString(methods[i])) {
        throw new Error(isString());
      }
      this.methods.push(methods[i]);
    }
  };
  core.Intaface.Lib = {
    Composite: [
      "add",
      "getChild",
      "getID",
      "setID",
      "sortSrc",
      "getSrc",
      "display",
      "getElement"
    ],
    Visitor: ["accept"],
    Element: ["getElement"],
    Iterator: ["forward", "back", "getNext"],
  };
  core.Intaface.warnings = {
    isString: function () {
      return function () {
        return "Intaface constructor expects method names to be passed in as a string.";
      };
    },
    args2: function () {
      var a = "Intaface constructor called with ",
        b = " argument",
        c = ", but expected exactly 2.";
      return function (i, str) {
        return a + i + b + str + c;
      };
    },
    ensure2: function () {
      var a = "Intaface.ensure called with ",
        b = " argument",
        c = ", but expected at least 2.";
      return function (i, str) {
        return a + i + b + str + c;
      };
    },
    isInstance: function () {
      return function () {
        return "Intaface.ensure expects arguments two and above to be instances of Intaface.";
      };
    },
    isMethod: function () {
      var a = "Intaface.ensure: The supplied object does not implement the ",
        b = " interface. Method ",
        c = " was not found.";
      return function (name, method) {
        return a + name.toUpperCase() + b + method.toUpperCase() + c;
      };
    },
    isArray: function () {
      return function () {
        return "This argument must be defined and must be an Array";
      };
    },
    isEmpty: function () {
      return function () {
        return "An Array is supplied but it is empty. Cannot complete initialisation";
      };
    },
  };
  //core.Intaface.ensureImplements = function (object,  interface1, interface2...) {
  core.Intaface.ensureImplements = function (object) {
    var i,
      j,
      intaface,
      mLen,
      method,
      methods,
	  plural,
	  len = arguments.length,
	  mywarnings = core.Intaface.warnings,
      isMethod = mywarnings.isMethod(),
      isInstance = mywarnings.isInstance(),
      ensure2 = mywarnings.ensure2();
    if (len < 2) {
      plural = len ? "" : "s";
      throw new Error(ensure2(len, plural));
    }
    for (i = 1; i < len; i += 1) {
      intaface = arguments[i]; //intaface is an instance of Intaface
      methods = intaface.methods;
      if (intaface.constructor !== core.Intaface) {
        throw new Error(isInstance());
      }
      mLen = methods.length;
      for (j = 0; j < mLen; j += 1) {
        method = methods[j];
        if (!beFunction(object[method])) {
          throw new Error(isMethod(intaface.name, method));
        }
      }
    }
  };
  core.Intaface.ensures = function () {
    try {
      core.Intaface.ensureImplements.apply(core.Intaface, arguments);
    } catch (e) {
      noOp();
      window.console.log(e.message);
      //throw(e.message);
    }
  };
})(poloAfrica);
