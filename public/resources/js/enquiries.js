/*jslint nomen: true */
/*global window: false */
/*global document: false */
/*global Modernizr: false */
/*global poloAfrica: false */
/*global _: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}
(function (formsection) {
  "use strict";

  function doBestWrap(coll, pred, arg) {
    let domap = (fn, ag) => curryDefer(fn)(ag),
      func = curryDefer(pred)(arg),
      group = coll.map((item) => domap(item, arg));
    return getBest(group, func);
  }

  function makeValidator(message, validator) {
    function F(...args) {
      return validator(...args);
    }
    F.message = message;
    return F;
  }

  function validateInput(el, errors, ...validators) {
    var sortInputName = (str) => {
        let i = str.indexOf("["),
          j = str.indexOf("]");
        return str.slice(i + 1, j);
      },
      name = sortInputName(el.name);
    return function (el) {
      //php can have the input name as an array: array[item], in this case details[email] etc..
      //it helps filter out non-relevant form fields, hidden, submits etc..
      //the name we want is the index of the array, the bit between the brackets
      validators.map((validator) => {
        let n = sortInputName(el.name);
        if (name === n) {
          if (!validator(n, el.value)) {
            errors.push(n, getResult(validator.message));
          } else {
          }
        }
      });
    };
  }
  const meta = poloAfrica.meta,
    utils = poloAfrica.utils,
    getResult = meta.getResult,
    ptL = meta.doPartial(),
    comp = meta.compose,
    getter = (o, k) => o && o[k],
    isEqual = (x, y) => getResult(x) === getResult(y),
    gtThan = (x, y) => getResult(x) > getResult(y),
    noOp = () => {},
    methodInvoke = (m) => (o, v) => o && o[m] && o[m](v),
    doTwice = meta.curryRight(2),
    curryDefer = (fun) => (a) => () => fun(a),
    getBest = (coll, pred) => coll.reduce((a, b) => (pred(a, b) ? a : b)),
    spaceCount = (str) =>
      meta.isString(str) ? str.trim().split(" ").length - 1 : 1,
    gtThanOne = doTwice(gtThan)(1),
    gtThanTwo = doTwice(gtThan)(2),
    klasAdd = utils.addKlas,
    klasRem = utils.remKlas,
    getMsg = doTwice(getter)(1),
    getAttrs = methodInvoke("getAttribute"),
    doWarning = comp(klasAdd("warning"), utils.getClassList),
    undoWarning = comp(klasRem("warning"), utils.getClassList),
    byTags = methodInvoke("getElementsByTagName"),
    byTag = comp(utils.getZero, byTags),
    getId = doTwice(getAttrs)("id"),
    getFor = doTwice(getAttrs)("for"),
    getNodeName = doTwice(getter)("nodeName"),
    myform = byTag(document, "form"),
    legend = byTag(myform, "legend"),
    textarea = byTag(myform, "textarea"),
    isEmail = ptL(isEqual, "email"),
    isName = ptL(isEqual, "name"),
    isComment = ptL(isEqual, "comments"),
    isLabel = ptL(isEqual, "LABEL"),
    notEmpty = (str) => str && str[0],
    preCondition = function (pre, post) {
      return function (k, v) {
        if (!pre(k)) {
          return true;
        }
        return comp(getResult, post)(v);
      };
    },
    email_address = (v) =>
      v.match(/^[\w][\w.\-]+@[\w][\w.\-]+\.[A-Za-z]{2,6}$/),
    form_name = (v) => v.match(/[a-zA-Z]{2,}\.?\s[a-zA-Z]{2,}/),
    form_name_three = (v) =>
      v.match(/[a-zA-Z\.]{2,}\.?\s[a-zA-Z]+\s[a-zA-Z]{2,}/),
    form_name_strict = (v) => v.match(/[A-Z][a-zA-Z]+\.*\s[A-Z][a-zA-Z]+/),
    form_name_strict_three = (v) =>
      v.match(/[A-Z][a-zA-Z]+\.?\s[A-Z][a-z]*\s[A-Z][a-zA-Z]{1,}/),
    comment_name = (v) => !v.match(/Please use this area \w*/i),
    is_suspect = (v) => !new RegExp("<[^>]+>").test(v),
    string_min = (v) => v.trim().length > 15,
    string_max = (v) => v.trim().length < 1000,
    clear = function (e) {
      //listener on textarea
      if (!meta.$Q(".warning")) {
        e.target.value = "";
      } else {
        undoWarning(e.target);
      }
    },
    checkSpacesStrict = ptL(
      doBestWrap,
      [form_name_strict_three, form_name_strict],
      comp(gtThanOne, spaceCount)
    ),
    checkSpaces = ptL(
      doBestWrap,
      [form_name_three, form_name],
      comp(gtThanOne, spaceCount)
    ),
    //Use this area for comments or questions
    isSuspect = makeValidator(
      "suspicious angled brackets found.",
      preCondition(meta.always(true), is_suspect)
    ),
    isNotEmptyComment = makeValidator(
      "required field indicated.",
      preCondition(isComment, notEmpty)
    ),
    isNewMessage = makeValidator(
      "Please write your own message.",
      preCondition(isComment, comment_name)
    ),
    isSmallMessage = makeValidator(
      "Message is very small, please elaborate",
      preCondition(isComment, string_min)
    ),
    isLargeMessage = makeValidator(
      "Word count of your message is too great. Reduce word count or please email/call instead.",
      preCondition(isComment, string_max)
    ),
    atLeastFourWords = makeValidator(
      "message shoud consist of at least four words.",
      preCondition(isComment, comp(gtThanTwo, spaceCount))
    ),
    isProperName = makeValidator(
      'Expect at least 2 characters for first and last names, eg "Dr No"',
      preCondition(isName, checkSpaces)
    ),
    isProperNameStrict = makeValidator(
      "please Capitalise your individual name parts.",
      preCondition(isName, checkSpacesStrict)
    ),
    isEmptyName = makeValidator(
      "required field indicated.",
      preCondition(isName, notEmpty)
    ),
    isEmptyEmail = makeValidator(
      "required field indicated.",
      preCondition(isEmail, notEmpty)
    ),
    isEmailAddress = makeValidator(
      "please supply an email address.",
      preCondition(isEmail, email_address)
    ),
    applyAlert = (el, labels, msgs) => {
      el.innerHTML = msgs[1];
      doWarning(formsection);
      var label = labels.find(function (node) {
          return getFor(node) === msgs[0] || getId(node) === msgs[0];
        }),
        routes = [meta.pApply(doWarning, label), noOp];
      comp(getResult, getBest)(routes, meta.always(label));
      comp(utils.addKlas(msgs[0]), utils.getClassList).wrap(meta.pass)(
        formsection
      );
    },
    restore = (el, orig, labels) => {
      el.innerHTML = orig;
      comp(getResult, getBest)(
        [meta.pApply(undoWarning, meta.$Q(".warning")), noOp],
        meta.$$Q(".warning")
      );
      labels.forEach(undoWarning);
    },
    //legend will NOT be found when form submission completes
    doAlert = (function (el) {
      if (el) {
        var exec,
          orig = el.innerHTML,
          submitters = [
            meta.$Q("input[type=submit]"),
            meta.$Q("input[type=image]")
          ].filter((o) => o),
          nodes = meta.toArray(el.parentNode.childNodes),
          labels = nodes.filter(comp(isLabel, getNodeName)),
          inter = meta.deferCB(meta.invokeMethod, submitters, "forEach"),
          funcs = [inter(utils.setDisabled), inter(utils.unsetDisabled)],
          determine = (coll, pred, arg) => {
            let cb = pred(arg);
            return coll.reduce((a, b) => (cb(a, b) ? a() : b()));
          },
          doAble = meta.pApply(determine, funcs, utils.getZeroCB);
        labels.push(meta.$("comments"));

        exec = ptL(doBestWrap, [ptL(applyAlert, el, labels), noOp], getMsg);
        return function (msgs) {
          restore(el, orig, labels);
          doAble(msgs);
          comp(getResult, exec)(msgs);
        };
      }
    })(legend),
    onchange_listener = function (e) {
      var errors = [],
        checker = validateInput(
          e.target,
          errors,
          isSuspect,
          isEmptyName,
          isProperName,
          isProperNameStrict,
          isEmptyEmail,
          isEmailAddress,
          isNotEmptyComment,
          isNewMessage,
          isSmallMessage,
          isLargeMessage,
          atLeastFourWords
        ),
        gang = meta.toArray(e.target.form.elements).filter((el) => el.id);
      gang.forEach(checker);
      doAlert(errors);
    };
  if (textarea) {
    textarea.addEventListener("focus", clear);
    document.forms[0].addEventListener("change", onchange_listener);
  }
})(document.getElementById("post"));
