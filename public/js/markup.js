/*jslint nomen: true */
/* eslint-disable indent */
/* eslint-disable no-param-reassign */
/*global poloAfrica: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}
poloAfrica.markup = (function (
  linkreg,
  nonHeadLinkReg,
  imgReg,
  justImgReg,
  targetReg,
  floatReg
) {
  let isOL = /\n+1\.\s+([^\n]+)/g,
    isUL = /\n+-+\s([^\n]+)/g,
    mylist = [
      [isOL, "- $1\n"],
      [isUL, "1. $1\n"],
    ],
    tog = false;
  const meta = poloAfrica.meta,
    utils = poloAfrica.utils,
    log = (...args) => console.log(...args),
    comp = meta.compose,
    ptL = meta.pApply,
    $ = meta.$,
    isNum = meta.isNumber,
    verify = meta.always(true),
    whenNot = (arg) => !arg,
    doAlt = meta.doAlternate(),
    pass = meta.pass,
    defer = meta.doPartial(true),
    deferCB = meta.deferCB,
    delay = meta.doPartial(1),
    getRes = meta.getResult,
    curry2 = meta.curryRight(2),
    curry22 = meta.curryRight(2, true),
    curry3 = meta.curryRight(3),
    curry4 = meta.curryRight(4),
    curry44 = meta.curryRight(4, true),
    curryL2 = meta.curryLeft(2),
    doMatch = (m) => (v, o) => o[m](v),
    doComp = (f1, f2) => comp(f2, f1),
    doComper = (f1, f2) => {
      return comp(f2, f1);
    },
    doCompo = (f1, f2) => comp(invoke, f2, f1),
    doCompArg = (f1, f2, seed) => comp(f2(seed), f1),
    isOK = meta.doWhenFactory(0),
    bestmap = (pred, actions, arg) => {
      let predicate = ptL(pred, arg),
        options = actions.map((action) => ptL(action, arg));
      return options.reduce((a, b) => (predicate() ? a : b));
    },
    invoke = (f) => f(),
    invokeArg = (f, a) => f(a),
    invoker = (f, ...args) => f(...args),
    noOp = () => {},
    add = (a, b) => getRes(a) + getRes(b),
    ltEq = (a, b) => a <= b,
    equal = (a, b) => a === b,
    subtract = (a, b) => getRes(a) - getRes(b),
    soMatch = doMatch("match"),
    invokeMethod = (o, m, v) => o[m](v),
    deferMethod = (m) => (o, v) => o[m](v),
    deferMethodPair = (m) => (o, k, v) => o[m](k, v),
    invokePair = (o, m, k, v) => o[m](k, v),
    any = (agg, curr) => (getRes(agg) ? agg : getRes(curr)),
    all = (agg, curr) => getRes(agg) && getRes(curr),
    isAny = curry4(invokePair)(false)(any)("reduce"),
    isAnyDefer = curry44(invokePair)(false)(any)("reduce"),
    isAll = curry44(invokePair)(true)(all)("reduce"),
    extents = ["selectionStart", "selectionEnd"].map(curry2(meta.getter)),
    preverify = curry2((a, b) => [a, b])(verify),
    getClassList = curry2(meta.getter)("classList"),
    addKlas = ptL(meta.invokeMethodBridge, "add"),
    remKlas = ptL(meta.invokeMethodBridge, "remove"),
    undoActive = comp(remKlas("active"), getClassList).wrap(pass),
    doActive = comp(addKlas("active"), getClassList).wrap(pass),
    isEqual = (char) => (arg) => arg === char,
    checkIndex = meta.negator(isEqual(-1)),
    doSetJSCookie = defer(meta.invokePair, utils, "setCookie", "js", "js"),
    remove = (child) => {
      let parent = child && child.parentNode;
      if (parent && child) {
        parent.removeChild(child);
      }
    },
    Maker = (tx) => {
      if (!tx) {
        return {};
      }

      let cache = tx && tx.value;
      const isEmpty = () =>
          /^\s$/.test(subSelect(tx.selectionStart, tx.selectionEnd)),
        deferValue = curry22((o, p) => o[p])("value")(tx),
        deferMatch = comp(
          curry2(invokeArg)("match"),
          ptL(meta.invokeMethod),
          deferValue
        ),
        deferLength = comp(curry2((o, p) => o[p])("length"), deferValue),
        deferIndex = deferMethod("indexOf"),
        soReplace = deferMethodPair("replace"),
        deferReplace = curry3(soReplace),
        deferStart = curry22((o, p) => o[p])("selectionStart")(tx),
        deferEnd = curry22((o, p) => o[p])("selectionEnd")(tx),
        subtractFrom = comp(invoke, ptL(doComp, deferStart), curry2(subtract)),
        addTo = comp(invoke, ptL(doComp, deferEnd), curry2(add)),
        setTxValue = ptL((o, p, v) => (o[p] = v), tx, "value"),
        doSetTxValue = comp(setTxValue, invoke, ptL(doComp, deferValue)),
        //edge case returns string length if zero is reached; eliminates a false positive; used with a character check
        checkRange = comp(
          invoke,
          ptL(bestmap, meta.identity, [meta.identity, deferLength])
        ),
        isSpace = isEqual(" "),
        isLine = isEqual("\n"),
        isStar = ptL(soMatch, /\*/),
        isExclamationMark = ptL(soMatch, /(?<!\[)!/),
        matchLine = ptL(soMatch, /\n/),
        isNotWord = ptL(soMatch, /\W/),
        isWord = ptL(soMatch, /\w/),
        leadingSlash = ptL(soMatch, /^\//),
        isForwardSlash = ptL(soMatch, /\//),
        isClosingBracket = ptL(soMatch, /\]/),
        isOpeningBracket = ptL(soMatch, /\[/g),
        isClosingParanth = ptL(soMatch, /\)/g),
        isOpeningBrace = ptL(soMatch, /\{/g),
        isClosingBrace = ptL(soMatch, /\}/g),
        isOpeningParanth = ptL(soMatch, /\(/g),
        isColon = ptL(soMatch, /:/g),
        isHash = ptL(soMatch, /#/),
        isBackSlash = ptL(soMatch, /\\/),
        revertSelection = (s, e = 0) => {
          tx.selectionStart = s;
          tx.selectionEnd = e || s;
          return s;
        },
        setSpan = comp(curry2(add)("|"), curryL2(add)("|")),
        checkLinkStyle = comp(
          checkIndex,
          invoke,
          ptL(doCompArg, deferValue, curry2(deferIndex))
        ),
        checkInlineStyle = ptL(checkLinkStyle, "]("),
        checkRefStyle = ptL(checkLinkStyle, "]["),
        queryChar = (flag) => (predicate, getter) => {
          return (i) => {
            let f = getter(),
              k = flag ? i : i - 1,
              v = flag ? i + 1 : i,
              g = f(k);
            return comp(predicate, g)(v);
          };
        },
        getTextVal = comp(
          curry4((v, k, o, m) => o[m](k, v))("slice"),
          deferValue
        ),
        queryFromz = queryChar(false),
        queryToz = queryChar(true),
        findFrom = curry2(queryFromz)(getTextVal),
        findTo = curry2(queryToz)(getTextVal),
        setStyleTag = (tag, str) => {
          let [col, bg] = tag,
            weight = col.toUpperCase() === col ? "font-weight:bold;" : "";
          [col, bg] = tag.map((o) => o.toLowerCase());
          if (bg) {
            tag = `<span style=color:${col};background-color:${bg};${weight}>${str}</span>`;
          } else {
            tag = `<span style=color:${col};${weight}>${str}</span>`;
          }
          return tag;
        },
        getAvailableLink = () => {
          //return 1 if no current ref links
          var i = 1,
            j = 0,
            links = tx.value.match(/(?<=\[)(\w+)(?=\]:.+)/g),
            res;
          if (links) {
            res = links.map((o) => {
              let x = Number(o);
              return !isNaN(x) ? x : o;
            });
          }
          if (res) {
            while (res[j]) {
              if (res.indexOf(i) === -1) {
                break;
              }
              j++;
              i++;
            }
            return i;
          }
          return 1;
        },
        doSetJSCookie = defer(meta.invokePair, utils, "setCookie", "js", "js"),
        doUnsetJSCookie = defer(invokeMethod, utils, "deleteAllCookies", "js"),
        hasImg = comp(curry3(invokeMethod)(imgReg)("match"), deferValue),
        //fails if we have a linked image in the article
        hasLINKedimg2 = ptL(
          meta.negate,
          comp(curry3(invokeMethod)(justImgReg)("match"), deferValue)
        ),
        hasLINKedIMG = (x) => true,
        hasImageLinks = comp(curry2(isOK)(hasLINKedIMG), hasImg),
        hasLinks = comp(ptL(soMatch, nonHeadLinkReg), deferValue),
        subSelect = (from, to) => tx.value.slice(from, to),
        trimFrom = (str, from) => (/^\s+[^ ]+/.test(str) ? from + 1 : from),
        trimTo = (str, to) => (/\s+$/.test(str) ? to - 1 : to),
        notEmpty = deferCB(meta.best, isEmpty),
        toggleToolbar = doAlt([doActive, undoActive]),
        doSkip = (i, index = undefined) => {
          if (i) {
            let ints = i.toString().split("").map(Number);
            return isNaN(index) ? ints : ints[index];
          }
          return isNaN(index) ? [0, 0] : 0;
        },
        incrementor = (o, i) => {
          let ints = i.toString().split("").map(Number);
          o.from -= ints[0];
          o.to += ints[1] ?? ints[0];
          o.selection = subSelect(o.from, o.to);
          return o;
        },
        mapReplace =
          (arg = "") =>
          (...args) => {
            gang = args.map(deferReplace("$1" + arg));
            gang.map(doSetTxValue);
          },
        doClear = (reg) => {
          let f = deferReplace("");
          doSetTxValue(f(reg));
        },
        doTrim = () => (tx.value = tx.value.trim()),
        fixFrom = (cb, n, skip = 0, k = 0) => {
          k = isNum(k) ? k : 0;
          skip = isNum(skip) ? skip : 0;
          while (!cb(n) && n >= 1) {
            k++;
            n--;
          }
          if (isNum(skip) && skip > 0 && n >= 1) {
            k++;
            n--;
            skip--;
            return fixFrom(cb, n, skip, k);
          }
          return k;
        },
        /*
        fixTo needs to TRACK the length of tx.value and exit on potentially reaching the last character to avoid a repeating loop
        should the callback never return true abiding by the concept of pure functions that info is wrapped up in the callback (cb) see bestmap
        otherwise we have a dependency on the tx object (while(!cb(n) && n <= tx.value))
        fixFrom simply needs to know we have yet to reach zero nad the extra abstraction is not worth the effort
        */
        fixTo = (cb, n, skip = 0, k = 0) => {
          k = isNum(k) ? k : 0;
          skip = isNum(skip) ? skip : 0;
          while (!cb(n)) {
            k++;
            n++;
          }
          if (isNum(skip) && skip > 0) {
            k++;
            n++;
            skip--;
            return fixTo(cb, n, skip, k);
          }
          return k;
        },
        //works with equals and match
        lookAhead = (s, i = 0) => tx.value.substring(s + i, s + i + 1),
        lookBehind = (s, i) => tx.value.substring(s - i, s - i + 1),
        //may not work with equals
        lookForward = (s, i = 1) => tx.value.substring(s, s + i),
        validateStart = comp(
          curry2(lookAhead)(0),
          checkRange,
          ptL(subtract, deferStart)
        ),
        validateBack = comp(curry2(lookBehind)(2), ptL(subtract, deferStart)),
        invalidSelection = (selection) => {
          let line = selection.match(/\n/),
            wordcount = selection.split(" ");
          return line || wordcount.length > 7;
        },
        isValidSelection = (selection) => {
          let line = selection.match(/\n/),
            wordcount = selection.split(" ");
          return !line && wordcount.length < 7 ? selection : null;
        },
        inWord = (start, end) => {
          if (start === end) {
            let infront = lookForward(start),
              behind = lookBehind(end, 1);
            return infront.match(/\W/) || behind.match(/\W/);
          }
        },
        thanMax = comp(curry2(ltEq), curry2(meta.getter)("length"), deferValue),
        fixSelection =
          (start, end, skip = 0) =>
          (doFrom, doTo, i = 0) => {
            doTo = doTo || doFrom;
            let selected = start !== end,
              selection = subSelect(start, end),
              x = skip ? doSkip(skip, 0) : 0,
              y = skip ? doSkip(skip, 1) : 0,
              from = trimFrom(selection, start),
              to = trimTo(selection, end);
            from -= fixFrom(doFrom, from, x, i);
            to += fixTo(doTo, to, y, i);
            selection = subSelect(from, to);
            selected = start !== end;
            return {
              from,
              to,
              selection,
              selected,
            };
          },
        deferFix = comp(
          invoke,
          ptL(doComp, deferEnd),
          ptL(fixSelection),
          deferStart
        ),
        delayFix = comp(
          invoke,
          ptL(doComp, deferStart),
          invoke,
          ptL(doComp, deferEnd),
          curry3(fixSelection)
        ),
        /*
        the further we travel back the greater the number returned from fixFrom bigger numbers refer to EARLIER characters important to get f1 and f2 in the right order, the function expects f1 to be the winner as in find a space before a line
          */
        queryFrom = (f1, f2, start, skip = 0, k = 0) => {
          let [x, y] = doSkip(skip),
            i = fixFrom(f1, start, x, k),
            j = fixFrom(f2, start, y, k);
          return i < j;
        },
        queryTo = (f1, f2, start, skip = 0, k = 0) => {
          let [x, y] = doSkip(skip),
            i = fixTo(f1, start, x, k),
            j = fixTo(f2, start, y, k);
          return i < j;
        },
        revert = () => (tx.value = cache),
        checkMax = ptL(bestmap, thanMax()),
        //fn1;fn2 need to be UNMAPPED functions; ie isOpeningBracket NOT isOpBrkt
        eitherOr =
          (fn1, fn2, rev = false) =>
          (pos, flag) => {
            let i,
              j,
              [f1, f2] = [fn1, fn2].map(findFrom),
              [f3, f4] = [fn1, fn2]
                .map(findTo)
                .map(comp(checkMax, preverify))
                .map(curry2(doComp)(getRes));

            if (flag && meta.isBoolean(flag)) {
              i = fixTo(f3, pos);
              j = fixTo(f4, pos);
              if (rev) return i > j ? f3 : f4;
              return i < j ? f3 : f4;
            } else {
              i = fixFrom(f1, pos);
              j = fixFrom(f2, pos);

              if (rev) return i > j ? f1 : f2;
              return i < j ? f1 : f2;
            }
          },
        [
          isOpBrkt,
          isClsBrkt,
          isOpara,
          isClsPara,
          isLineFrom,
          isSpaceFrom,
          isEx,
          hashFrom,
          starFrom,
          notWordFrom,
          isColonFrom,
          isOpBrace,
          isOpenTag,
          isCloseTag,
          isBackSlashFrom,
        ] = [
          isOpeningBracket,
          isClosingBracket,
          isOpeningParanth,
          isClosingParanth,
          isLine,
          isSpace,
          isExclamationMark,
          isHash,
          isStar,
          isNotWord,
          isColon,
          isOpeningBrace,
          isEqual("<"),
          isEqual(">"),
          isBackSlash,
        ].map(findFrom),
        [
          isOpBrktTo,
          isClsBrktTo,
          isOpParaTo,
          isClsParaTo,
          isClsBraceTo,
          isOpBraceTo,
          isLineTo,
          isSpaceTo,
          isFsTo,
          hashTo,
          starTo,
          notWordTo,
          wordTo,
          isColonTo,
          isOpenTagTo,
          isCloseTagTo,
          isBackSlashTo,
        ] = [
          isOpeningBracket,
          isClosingBracket,
          isOpeningParanth,
          isClosingParanth,
          isClosingBrace,
          isOpeningBrace,
          isLine,
          isSpace,
          isForwardSlash,
          isHash,
          isStar,
          isNotWord,
          isWord,
          isColon,
          isEqual("<"),
          isEqual(">"),
          isBackSlash,
        ]
          .map(findTo)
          .map(comp(checkMax, preverify))
          .map(curry2(doComp)(getRes)),
        deferFrom = curry2(eitherOr(isSpace, isLine))(false),
        deferTo = curry2(eitherOr(isSpace, isLine))(true),
        deferSpaceLine = comp(deferFrom, deferStart),
        deferSpaceLineTo = comp(deferTo, deferEnd),
        //all await current selectionStart/End
        deferBracket = ptL(fixFrom, isOpBrkt),
        deferParanth = ptL(fixFrom, isOpara),
        isSpaceOrLine = (bool) => {
          let pos = bool ? deferEnd() : deferStart(),
            f = eitherOr(isSpace, isLine);
          return f(pos, bool);
        },
        advanceClsBrkt = comp(
          revertSelection,
          addTo,
          curry2(invokeArg)(0),
          invoke,
          ptL(doComp, deferEnd),
          //use delay to allow for the skip optional argument
          delay(fixTo, isClsBrktTo)
        ),
        advanceClsBrktFrom = comp(
          ptL(subtract, deferStart),
          invoke,
          ptL(doComp, deferStart),
          ptL(fixFrom, isClsBrkt)
        ),
        advanceOpenBrkt = comp(
          ptL(subtract, deferStart),
          invoke,
          ptL(doComp, deferStart),
          ptL(fixFrom, isOpBrkt)
        ),
        advanceOpenBrktTo = comp(
          ptL(add, deferEnd),
          invoke,
          ptL(doComp, deferEnd),
          ptL(fixTo, isOpBrktTo)
        ),
        isLinkHeading = comp(
          invoke,
          ptL(doComp, advanceOpenBrkt),
          ptL(queryFrom, hashFrom),
          deferSpaceLine
        ),
        isLinkHeadingAlt = comp(
          invoke,
          ptL(doComp, advanceOpenBrktTo),
          ptL(queryFrom, hashFrom),
          deferSpaceLine
        ),
        img1 = comp(ptL(queryFrom, isEx, isLineFrom), deferStart),
        img2 = comp(ptL(queryFrom, isOpBrkt, isEx), deferStart),
        img3 = comp(ptL(queryFrom, isEx, isClsBrkt), deferStart),
        checkClosingBracket = comp(isClosingBracket, validateBack),
        myEx = comp(ptL(fixFrom, isEx), deferStart),
        imageAhoy = comp(isOpeningBracket, validateStart, myEx),
        imageHelloa = comp(isOpeningBracket, validateBack, myEx),
        imageAhead = ptL(isOK, img1, imageAhoy),
        imageBehind = ptL(isOK, img1, imageHelloa),
        isImg = comp(
          curry2(isOK)(imageAhoy),
          curry2(isOK)(img3),
          curry2(isOK)(img2),
          ptL(isOK, hasImg, img1)
        ),
        linkedImg = comp(
          curry2(isOK)(imageBehind),
          ptL(isOK, hasImageLinks, isImg)
        ),
        inline1 = ptL(queryFrom, isOpara, isClsPara),
        inline2 = comp(
          invoke,
          ptL(doComp, deferStart),
          ptL(queryFrom, isOpara),
          deferSpaceLine
        ),
        inline3 = comp(
          invoke,
          ptL(doComp, deferEnd),
          ptL(queryTo, isClsParaTo),
          deferSpaceLineTo
        ),
        inline4 = isAnyDefer([inline2, inline3]),
        atInLineLink1 = comp(
          curry2(isOK)(comp(checkClosingBracket, deferParanth, deferStart)),
          curry2(isOK)(inline3),
          ptL(isOK, comp(inline1, deferStart), inline2)
        ),
        atInLineLink = comp(
          curry2(isOK)(comp(checkClosingBracket, deferParanth, deferStart)),
          ptL(isOK, comp(inline1, deferStart), inline4)
        ),
        myRefLink = comp(
          curry2(invokeArg)(1),
          invoke,
          ptL(doComp, advanceClsBrkt),
          delay(queryTo, isClsBrktTo),
          deferSpaceLineTo
        ),
        myInlineLink = comp(
          invoke,
          ptL(doComp, advanceClsBrkt),
          ptL(queryTo, isOpParaTo),
          deferSpaceLineTo
        ),
        inLinerRef = comp(
          isOpeningBracket,
          curry2(lookAhead)(2),
          ptL(add, deferEnd),
          ptL(fixTo, isClsParaTo),
          deferEnd
        ),
        inLinerRefBrace = comp(
          isOpeningBracket,
          curry2(lookAhead)(2),
          ptL(add, deferEnd),
          ptL(fixTo, isClsBraceTo),
          deferEnd
        ),
        refInLiner = comp(
          isOpeningParanth,
          curry2(lookAhead)(1),
          ptL(add, deferEnd),
          curry2(invokeArg)(2),
          invoke,
          delay(fixTo, isClsBrktTo),
          deferEnd
        ),
        reflinkduo = comp(
          curry2(invokeArg)(3),
          invoke,
          ptL(doComp, deferEnd),
          delay(queryTo, isClsBrktTo),
          deferSpaceLineTo
        ),
        testOBR = comp(
          isOpeningBracket,
          curry2(lookAhead)(),
          ptL(subtract, deferStart)
        ),
        /*
        deferSpaceLine returns the second arg (isSpace or isLine) to queryFrom and the partially applied queryFrom
        is then composed with deferStart (third arg) in doComp
        */
        refindex1 = comp(ptL(queryTo, isClsBrktTo, isSpaceTo), deferEnd),
        refindex2 = comp(checkClosingBracket, deferBracket, deferStart),
        atRefIndex = ptL(isOK, refindex2, refindex1),
        footer1 = comp(ptL(queryFrom, isColonFrom, isLineFrom), deferStart),
        footer2 = comp(ptL(queryTo, isFsTo, isLineTo), deferEnd),
        footer3 = comp(
          invoke,
          ptL(doComp, deferEnd),
          ptL(queryTo, hashTo),
          deferSpaceLineTo
        ),
        doTestOBR = comp(testOBR, ptL(fixFrom, isLineFrom), deferStart),
        //works with cursor BEFORE closeBracket
        isFooterLinkSpace = comp(
          comp(curry2(invokeArg)(isColonTo), invoke),
          //deferTo supplies appropriate function as SECOND argument to queryTo
          ptL(doComp, deferSpaceLineTo),
          curry3(queryTo),
          revertSelection,
          advanceClsBrktFrom
        ),
        isFooterLinkPara = comp(
          curry2(invokeArg)(isColonTo),
          curry2(invokeArg)(isOpParaTo),
          curry3(queryTo),
          advanceClsBrktFrom
        ),
        isFooterLink = ptL(isOK, isFooterLinkPara, isFooterLinkSpace),
        notFooterLink = comp(ptL(queryTo, wordTo, isColonTo), deferEnd),
        notInlineLink = comp(ptL(queryFrom, isSpaceFrom, isOpara), deferEnd),
        atRefLink = comp(
          curry2(isOK)(isAnyDefer([isFooterLink, meta.negator(notFooterLink)])),
          curry2(isOK)(notInlineLink),
          curry2(isOK)(doTestOBR),
          curry2(isOK)(isAnyDefer([footer1, footer2, footer3])),
          //ensure this runs first WITHOUT any incoming arguments!!
          comp(ptL(doComp, deferStart), ptL(queryFrom, isSpaceFrom, isLineFrom))
        ),
        link1 = comp(ptL(queryTo, isClsBrktTo, isOpBrktTo), deferEnd),
        link2 = comp(ptL(queryFrom, isOpBrkt, isClsBrkt), deferStart),
        pureLink = comp(
          curry2(isOK)(meta.negator(isImg)),
          curry2(isOK)(notFooterLink),
          curry2(isOK)(isAnyDefer([myRefLink, myInlineLink])),
          curry2(isOK)(link1),
          curry2(isOK)(link2),
          hasLinks
        ),
        excludeHeadings = comp(
          curry2(isOK)(isLinkHeading),
          ptL(isOK, link1, link2)
        ),
        spanFrom = comp(
          invoke,
          ptL(doComp, deferStart),
          ptL(queryFrom, isOpenTag),
          deferSpaceLine
        ),
        spanTo = comp(
          invoke,
          ptL(doComp, deferEnd),
          ptL(queryTo, isCloseTagTo),
          deferSpaceLineTo
        ),
        braceFrom = comp(
          invoke,
          ptL(doComp, deferStart),
          ptL(queryFrom, isOpBrace),
          deferSpaceLine
        ),
        braceTo = comp(
          invoke,
          ptL(doComp, deferEnd),
          ptL(queryTo, isClsBraceTo),
          deferSpaceLineTo
        ),
        spanFromText = comp(
          invoke,
          ptL(doComp, deferStart),
          ptL(queryFrom, isCloseTag),
          deferSpaceLine
        ),
        spanToText1 = comp(
          invoke,
          ptL(doComp, deferEnd),
          ptL(queryTo, isOpenTagTo),
          deferSpaceLineTo
        ),
        spanToText = ptL(
          isOK,
          comp(ptL(queryTo, isFsTo, isCloseTagTo), deferEnd),
          spanToText1
        ),
        atBraceTag = isAll([braceFrom, braceTo]),
        atSpanText = isAnyDefer([spanFromText, spanToText]),
        atSpanTag = ptL(
          isOK,
          isAnyDefer([spanFrom, spanTo]),
          meta.negator(atSpanText)
        ),
        defaultactions = [
          atSpanTag,
          atBraceTag,
          excludeHeadings,
          isLinkHeadingAlt,
          atRefLink,
          atInLineLink,
        ],
        reLocate = (key) => {
          let str = document.forms[0].getAttribute("action"),
            i = str.match(/\d+$/);
          key = key.indexOf("/") === 0 ? key : `/${key}`;
          i = i ? i[0] : "";
          window.location = window.location.origin + "/article/edit/" + i + key;
        },
        grabIndex = (start, end) => {
          end = end || start;
          let fixer,
            txstatus,
            i = fixTo(isOpBrktTo, end);
          end += i + 1;
          revertSelection(end);
          fixer = deferFix();
          txstatus = fixer(isOpBrkt, isClsBrktTo);
          end = txstatus.selection.match(/^\w+$/);
          return end ? txstatus.selection : null;
        },
        getLiveRegExp = (id, capture = true) => {
          if (id) {
            let path = "(\\[" + id + "\\]:\\s*\\S+)",
              title = '(?:(\\s".+")|\\s|$)',
              attr = capture
                ? "(?:(\\s*{.+})|\\s|$)?"
                : "(?:\\s*{(.+)}|\\s|$)?",
              match = `${path}${title}${attr}`;
            return new RegExp(match);
          } else {
            //!!! biz rules apply; a LINKED image MUST NOT have an attribute block and the surrounding link MUST
            return /\(([^)]+?)(?:\)|\s("[^"]+")\))(?:\s?({[^}]+?})|)(?:\](\([^)]+\))|)(?:\s?({[^}]+})|)/;
          }
        },
        getRefBlock = comp(
          invoke,
          ptL(doComp, deferValue),
          curry2(deferMethod("match")),
          curry2(getLiveRegExp)(true)
        ),
        refBlockFromIndex = comp(getRefBlock, grabIndex),
        refFactory = {
          refduo: (start, end) => {
            var i,
              imgcopy,
              attrs = refBlockFromIndex(start, end);
            if (attrs) {
              imgcopy = attrs[2]
                ? `${attrs[1]}${attrs[2]}`
                : attrs[1].replace(/\n/, "");
            }
            if (imgcopy) {
              i = fixTo(isOpBrktTo, end, 1);
              start += i;
              end = start;
              attrs = refBlockFromIndex(start, end);
              attrs = attrs[3] ? attrs[3].replace(/\n/, "") : "";
              //only float required so capture that
              attrs = "{" + attrs.replace(/[^.]+(\.\S+).+/, "$1") + "}";
              imgcopy = meta.prepareEscape(imgcopy, true);
              mapReplace(`${attrs}`)(imgcopy);
            }
            return "";
          },
          refuno: (start, end, attr_block = "") => {
            var attrs = refBlockFromIndex(start, end);
            if (attrs) {
              attrs = attrs ? attrs[0].replace(/\n/, "") : "";
              attrs = meta.prepareEscape(attrs, true);
              mapReplace(`${attr_block}`)(attrs);
            }
            return "";
          },
          unoref: (start, end) => {
            var attrs = refBlockFromIndex(start, end);
            attrs = attrs && attrs[3] ? attrs[3].replace(/\n/, "") : "";
            revertSelection(start, end);
            return attrs;
          },
          def: (start, end, attr_block) =>
            attr_block.match(/\w/) ? attr_block : "",
        },
        linkType = (start, skip = 0) => {
          let i = fixTo(isOpBrktTo, start, skip),
            j = fixTo(isOpParaTo, start, skip);
          return j < i;
        },
        postFocus = (start, end) => {
          //used with onChange event, not onSubmit
          tx.selectionStart = start || tx.selectionStart;
          tx.selectionEnd = end || tx.selectionEnd;
        },
        setTextArea = (from, to, selection = "") => {
          tx.value = subSelect(0, from) + selection + subSelect(to);
          return tx.value;
        },
        setTextAreaBridge = ({ from, to, selection }) => {
          return setTextArea(from, to, selection);
        },
        bailOut = (list, selection) => {
          return list.some((char) => selection === char);
        },
        bailBloodyOut = (list, selection) => {
          return list.some((char) => {
            return selection.indexOf(char) !== -1;
          });
        },
        setLinkIndex = (str, i) => {
          let linebreak = i === 1 ? "\n\n[" : "\n[";
          return str + linebreak + i + "]: ";
        },
        sortLinkAttributes = (attrstr, flag) => {
          let attrs = [],
            [_, link, title = "", block = ""] = attrstr.match(
              /(\S+)(?:\s([^{]+)|)(?:\s*({[^}]+})|)/
            );
          if (block) {
            if (block.match(/target/)) {
              block = flag ? block : block.replace(/target=\w+\s?/, "");
            } else {
              block = flag
                ? "{target=_blank " + block.replace(/{([^}]+)}/, "$1") + "}"
                : block;
            }
          } else {
            block = flag ? "{target=_blank}" : block;
          }
          attrs = [link, title, block].filter((str) => str.trim());
          return attrs.join(" ");
        },
        sortLinkType = (attrstr) => {
          return leadingSlash(attrstr) ? attrstr.substring(0) : attrstr;
        },
        prepInlineLink = ({ from, to, selection }, attrs, attr_block) => {
          attrs = sortLinkType(attrs);
          selection = "[" + subSelect(from, to) + "](" + attrs + ")";
          selection += attr_block || "";
          return { from, to, selection };
        },
        getReferenceDef = (n) => "[" + n + "]:",
        setReferenceDef = (str, index) => "[" + str + "][" + index + "]",
        getCopyIndex = (str = "") => {
          if (!str) {
            let copyindexes = [],
              reg = /\]\[(\w+)\]/g,
              res = [];
            while ((res = reg.exec(tx.value)) != null) {
              copyindexes.push(res[1]);
            }
            return copyindexes;
          } else {
            let index = str.match(/\]\[(\w+)\]/);
            return index && index[1];
          }
        },
        isMismatch = (a, b) => {
          let validate = curry2((o, p) => o[p])(0),
            res = a.filter((x) => !b.includes(x)),
            output = b.filter((x) => !a.includes(x));
          a = validate(res);
          b = validate(output);
          return a || b;
        },
        //fix restore float here...
        validateRefLinks = (copyindexes, flag = false) => {
          if (copyindexes[0]) {
            let res = [],
              linkattrs = [],
              refindexes = [],
              reg = /\[(\w+)\]:(.*)/g; //won't obtain ###[heading][1]
            while ((res = reg.exec(tx.value)) != null) {
              refindexes.push(res[1]);
              linkattrs.push(res[2]);
            }
            if (flag || !isMismatch(copyindexes, refindexes)) {
              return [refindexes, linkattrs];
            }
            return [[], []];
          }
          return [[1]];
        },
        orderRefLinks = (copyindexes, activeindex) => {
          let [refindexes, linkattrs] = validateRefLinks(copyindexes, true),
            dupes = meta.queryDuplicates(copyindexes);
          copyindexes = meta.queryDuplicates(copyindexes, true);
          //need an activeindex when deleting, but check if label/index is still in use
          if (refindexes[0]) {
            if (activeindex && !meta.includes(dupes, activeindex)) {
              copyindexes = copyindexes.filter((x) => x !== activeindex);
            }
            let val,
              index,
              i = 0,
              str = "";
            doClear(/\[(\w+)\]:.+/g);
            doTrim();
            tx.selectionStart = tx.value.length;
            tx.focus();
            tx.setSelectionRange(tx.value.length, tx.value.length);
            while ((val = copyindexes[i++])) {
              index = refindexes.indexOf(val);
              if (index !== -1) {
                str += "\n";
                str += getReferenceDef(val);
                str += linkattrs[index];
                tx.value += str;
                str = "";
              }
            }
            return true;
          }
          return false;
        },
        inlineAttrBlock = comp(
          curry2(invokeArg)(isOpBraceTo),
          curry2(invokeArg)(wordTo),
          curry3(queryTo),
          revertSelection,
          addTo,
          ptL(fixTo, isClsParaTo),
          deferEnd
        ),
        clearImage = (start, end) => {
          /*
          instructions to clear are to place the cursor in the alt text (between the brackets)
          ie:  my copy ![my lovely pic] my copy [![my lovely pic]... with link
          we must skip the first opening bracket and find if space/line is AFTER a [
          will be true if pure image and false if link
          */
          let txstatus,
            inline = linkType(start),
            doOrderRefLinks = comp(orderRefLinks, getCopyIndex),
            confirmer = ptL(
              meta.invokeMethod,
              window,
              "confirm",
              "Are you sure you want to remove this image?"
            );
          if (pureLink()) {
            return linker();
          }
          if (confirmer()) {
            if (!inline) {
              //end can be adjusted...
              let skipper = fixSelection(start, start, "01"),
                fixRefLink = ptL(skipper, isEx, isClsBrktTo),
                myincr = curry2(incrementor)(21),
                mySoFix = comp(myincr, fixRefLink);
              txstatus = mySoFix();
            } else {
              let fixer = fixSelection(start, end),
                func = inlineAttrBlock() ? isClsBraceTo : isClsParaTo,
                fixInlineLink = ptL(fixer, isEx, func),
                incr = curry2(incrementor)(11),
                soFix = comp(incr, fixInlineLink);
              txstatus = soFix();
            }
            setTextArea(txstatus.from, txstatus.to, "");
            doOrderRefLinks();
            return true;
          }
          return true;
        },
        isFloat = (title) => {
          const lib = new Map([
            ["<", "left"],
            [">", "right"],
            ["-", "none"],
            ["#", null],
          ]);
          for (var [k, v] of lib) {
            if (title.indexOf(k) !== -1) {
              break;
            }
          }
          return v;
        },
        machImage = (fullpath, selection, settextarea, title) => {
          if (fullpath) {
            let float = isFloat(title),
              link,
              str;
            title = isFloat(title) ? title.substring(1) : title;
            link = isFloat(title);
            title = link ? title.substring(1) : title;
            title = title ? `"${title}"` : "";
            fullpath = title ? `${fullpath} ${title}` : `${fullpath}`;
            str = `![${selection}](${fullpath})`;
            float = float ? "{." + float : "";
            if (float && link) {
              str = `[${str}](#)${float} target=_blank}`;
            } else if (float) {
              str = `${str}${float}}`;
            }
            settextarea(str);
          }
        },
        buildImage = (fullpath, txstatus) => {
          let [path, title = ""] = fullpath.split(" "),
            filename = path.replace(/^.*[/\/]/, ""),
            pathtofile = path.replace(filename, ""),
            abspath = window.location.origin + pathtofile + filename,
            ext = filename.match(/[\w-]+.(\w+)/),
            imgext = ["jpg", "jpeg", "png", "gif", "svg"],
            oncomplete = ptL(
              machImage,
              path,
              txstatus.selection,
              ptL(setTextArea, txstatus.from, txstatus.to),
              title
            ),
            oncompleteDefer = curry44(machImage)(title)(
              ptL(setTextArea, txstatus.from, txstatus.to)
            )(txstatus.selection);

          if (!ext || imgext.indexOf(ext[1]) === -1) {
            return;
          }
          let onfail = comp(
            ptL(
              utils.isJPEG(utils.checkIfImageExists, ptL(reLocate, "/inline")),
              abspath,
              oncompleteDefer
            )
          );
          utils.checkIfImageExists(abspath, oncomplete, onfail);
        },
        clearAttrBlock = (selection) => {
          let id = selection.match(/\[([^\]]+)\]/),
            myid = meta.escapeRegex(id[1]),
            rego = new RegExp("(\\[" + myid + "\\]\\([^)]+\\))(.+)");
          return selection.replace(rego, "$1").trim();
        },
        queryAttrBlock = (start, end, capture = true) => {
          var reg = getLiveRegExp(null),
            fixer = fixSelection(start, end),
            incr = curry2(incrementor)(11),
            doValidateAttrBlock = comp(
              handleHyperLink,
              validateAttrBlock([targetReg, floatReg])
            );
          if (inlineAttrBlock()) {
            var soFix = comp(incr, ptL(fixer, isEx, isClsBraceTo)),
              { from, to, selection } = soFix(),
              block = selection.match(reg);
            block = doValidateAttrBlock(block[3]);
            if (block) {
              //note name HAS to be block; see.res?.block
              return { from, to, selection, block };
            }
            return { from, to, selection };
          }
          revertSelection(start, end);
          if (myRefLink()) {
            //revertSelection(start, end);
            fixer = fixSelection(start, end, "01");
            var i = grabIndex(start, end),
              reg = getLiveRegExp(i, capture),
              regex = new RegExp(reg),
              block = tx.value.match(regex),
              title = block && block[2] ? block[2] : "",
              soFix = comp(incr, ptL(fixer, isEx, isClsBrktTo)),
              { from, to, selection } = soFix();
            if (block && block[3]) {
              mapReplace(title)(regex);
              block = block[3] || ""; //NAME it block
              return { from, to, selection, block };
            }
          }
          soFix = comp(incr, ptL(fixer, isEx, isClsParaTo));
          ({ from, to, selection } = soFix());
          return { from, to, selection };
        },
        fetchAttrBlock = (i, s, e) => {
          e += i;
          s = e;
          revertSelection(s, e);
          return queryAttrBlock(s, e);
        },
        handleHyperLink = (attr_block, replace = false) => {
          attr_block = attr_block.replace(/{([^}]+)}/, "$1");
          if (replace && !attr_block) {
            return "";
          }
          attr_block = attr_block.replace(/\s?target=\w{4,7}\s?/, "");
          if (!replace) {
            attr_block = meta.join(" ", attr_block, "target=_blank");
          }
          return `{${attr_block}}`;
        },
        handleFloat = (attr_block) => {
          attr_block = attr_block.replace(/{([^}]+)}/, "$1");
          //bizrules
          if (!attr_block.match(/\.\w{4,5}/)) {
            attr_block += " .left";
          }
          attr_block = attr_block.split(" ");
          attr_block = meta.join(" ", ...attr_block);
          return `{${attr_block}}`;
        },
        persistAttrBlock = (reglists, attrs, ret = []) => {
          let j = 0,
            matcha = curry2(deferMethod("match")),
            store = deferMethod("unshift"),
            result = comp(ptL(store, ret), utils.getZero),
            persist = curry2(meta.doWhen)(result),
            cb = curry2(invokeArg);

          while (reglists[j]) {
            let reglist = reglists[j].map(matcha),
              i = 0;
            while (attrs[i] !== undefined) {
              let [res] = reglist.map(cb(attrs[i])).filter((x) => x);
              persist(res);
              i++;
            }
            j++;
          }
          return ret;
        },
        validateAttrBlock =
          (lists) =>
          (attr_block = "") => {
            let attrs = attr_block.replace(/{([^}]+)}/, "$1"),
              ret = persistAttrBlock(lists, attrs.split(" "), []);
            return ret[0] ? `{${meta.join(" ", ...ret)}}` : "";
          },
        buildAttrs = (strx, regexx, ret, i) => {
          let j = 0;
          while (regexx[j]) {
            if (strx[i] && regexx[j] && strx[i].match(regexx[j])) {
              ret.push(strx[i]);
            }
            j++;
          }
          return strx[i] ? buildAttrs(strx, regexx, ret, (i += 1)) : ret;
        },
        inlineToRef = (flag, float = "") => {
          let [start, end] = extents.map((o) => o(tx)),
            //reg = /(?:\(([^)\s]+))(?:\s*("[^"]+")\)|\)|$)(?:\s*({[^}]+})|$)/,
            either = eitherOr(
              isClosingBrace,
              isClosingParanth,
              float || inlineAttrBlock()
            ),
            doOrderRefLinks = comp(orderRefLinks, getCopyIndex),
            endFunc = either(end, true),
            fixFunc = isOpParaTo,
            soFix = comp(
              curry2(incrementor)(1),
              getRes,
              curry44(invoker)(0)(endFunc)(isOpara),
              fixSelection
            ),
            i = getAvailableLink(),
            doValidateAttrBlock,
            confirm;
          if (flag) {
            confirm = window.confirm(
              "Convert inline link to a reference link?"
            );
          } else if (flag === false) {
            /*
            redirect from creating a LINK around an IMAGE where the cursor is expected to be within the alt text [my |lovely pic]
            as opposed to within the parantheses [my lovely pic](path/|to/file)
             */
            //boolean for INLINE link, using the confirm binding (variable), bit lazy
            confirm = queryFrom(isOpParaTo, isOpBrktTo, start);
            if (confirm) {
              //let attr_block = queryTo();
              i = fixTo(fixFunc, start);
              revertSelection((tx.selectionEnd += i + 1));
              [start, end] = extents.map((o) => o(tx));
              i = getAvailableLink();
            } else {
              //REF LINK
              soFix = comp(
                curry2(incrementor)(11),
                getRes,
                curry44(invoker)(0)(isClsBrktTo)(isEx),
                fixSelection
              );
              //txstatus object
              return soFix(start, end, "01");
            }
          }
          if (confirm) {
            let txstatus = soFix(start, end, 0),
              reg = getLiveRegExp(null),
              data = txstatus.selection.match(reg),
              // [![alt](/path/)|](link); NOT [![alt](/path/)](link|)
              atWrappedImage = queryTo(isClsBrktTo, isLineTo, end);
            data = data && data.filter(meta.identity);

            if (data) {
              var list = [targetReg, floatReg],
                regexx = [/^"/, /^\(/, /^{/],
                [_, path, title, link, brace] = data,
                method = "slice",
                nolink =
                  path.match(/#/) ||
                  atWrappedImage ||
                  (imageAhead() && !imageBehind()),
                just_img = imageAhead() && !imageBehind(),
                linked_img = imageBehind(),
                just_link = !linked_img && !just_img,
                attrs = buildAttrs([title, link, brace], regexx, [], 0);
              method = just_img ? "shift" : just_link ? "pop" : method;
              //use attrs binding for wrapping link and data binding for image/link
              if (atWrappedImage) {
                let ref = !attrs[1];
                if (ref) {
                  attrs = "";
                } else if (!ref && attrs[2]) {
                  //if title as well link and brace, title belongs to wrapped image, lose it
                  attrs.shift();
                }
                attrs = attrs.join ? `]${attrs.join(" ")}` : attrs;
              } else {
                attrs = attrs.map((str) => str || "");
                [title, brace] = attrs;
                attrs = "";
              }

              list[method](); //pop or shift
              list = atWrappedImage ? [] : list;
              //brace not required at image BUT required at link
              doValidateAttrBlock = validateAttrBlock(list);
              brace = doValidateAttrBlock(brace);
              brace = handleHyperLink(brace, nolink);
              title = title && title.match(regexx[0]) ? title : "";
              brace = brace && brace.match(regexx[2]) ? brace : "";
              data = meta.join(" ", path, title, brace);
            }
            setTextArea(txstatus.from, txstatus.to, `[${i}]${attrs}`);
            tx.value += `\n[${i}]: ${data}`;
            doOrderRefLinks();
          }
          return true;
        },
        refToInline = (flag) => {
          let confirmed,
            fixer = deferFix(),
            list = [targetReg, floatReg],
            doValidateAttrBlock,
            [start] = extents.map((o) => o(tx)),
            i = fixFrom(isOpBrkt, start),
            copyindexes = getCopyIndex(),
            res = validateRefLinks(copyindexes);
          if (flag) {
            confirmed = window.confirm(
              "Convert reference link to an inline link?"
            );
          }
          if (confirmed && hasRefLinks(res)) {
            tx.selectionStart -= i;
            tx.selectionEnd = tx.selectionStart;
            revertSelection(tx.selectionStart); //!!
            fixer = deferFix();
            let txstatus = fixer(isOpBrkt, isClsBrktTo),
              method = "slice",
              index = txstatus.selection,
              reg = new RegExp("\\[" + index + "\\]:\\s?(.+)"),
              rego = new RegExp("\\[" + index + "\\](?!:)", "g"),
              extent = tx.value.match(rego),
              output = "",
              doReg = comp(
                invoke,
                ptL(doCompArg, deferValue, curry2(deferMethod("match")))
              ),
              reflink = doReg(getLiveRegExp(index, true)),
              just_img = imageAhead() && !imageBehind(),
              linked_img = imageBehind(),
              just_link = !linked_img && !just_img,
              img = doReg(new RegExp("(\\W)\\[.+\\]\\[" + index + "\\](\\W)")),
              atWrappedImg = img && img[1] !== "!" && img[2] === "]";
            method = just_img ? "shift" : just_link ? "pop" : method;
            doValidateAttrBlock = validateAttrBlock(list);
            if (reflink) {
              let nolink,
                [link, title, attr_block] = reflink
                  .slice(1)
                  .map((item) => (item ? item.trim() : ""));

              link = link.replace(reg, "$1");
              output = meta.join(" ", link, title);
              if (link) {
                nolink =
                  link.match(/^(?:#$|\/\w+#\w+)$/) ||
                  (img[1] && img[1] === "!") ||
                  just_img ||
                  atWrappedImg;
                attr_block = doValidateAttrBlock(attr_block);
                attr_block = handleHyperLink(attr_block, nolink);
                output = `(${output})${attr_block}`;
                comp(
                  setTxValue,
                  invoke,
                  ptL(
                    doComp,
                    deferValue,
                    deferReplace(output.trim())(`[${index}]`)
                  )
                )();
                //reflink = doReg(reg);
                //two links may point to the same location
                //in which case no one has any business converting to inline; what a mess, however...
                // if (!extent[1]) {
                //doClear(reflink[0]);
                //  }
                doClear(/\s+$/);
              }
            }
            copyindexes = getCopyIndex();
            orderRefLinks(copyindexes);
            return true;
          }
        },
        wrapImage = (attrs, attr_block) => {
          let [start, end] = extents.map((o) => o(tx)),
            inline = queryTo(isOpParaTo, isOpBrktTo, start),
            skip = inline ? 0 : "01",
            fixer = fixSelection(start, end, skip),
            incr = curry2(incrementor)("01"),
            doFrom = isSpaceOrLine(false),
            setLink = curry3(prepInlineLink)(attr_block)(attrs),
            txstatus;
          if (!inline) {
            revertSelection(start, end);
            txstatus = inlineToRef(false, attr_block);
            if (meta.isBoolean(txstatus)) {
              //no further processing required delegated to inlineToRef
              return true;
            } else {
              return txstatus;
            }
          }
          return comp(
            setTextAreaBridge,
            setLink,
            incr,
            ptL(fixer, doFrom, isClsParaTo)
          )();
        },
        buildRegEx = (text) => {
          let star4 = new RegExp("\\*\\*\\*\\*+" + text + "\\*\\*\\*\\*+"),
            star3 = new RegExp("(?<!\\*)\\*\\*\\*" + text + "\\*\\*\\*(?!\\*)"),
            star2 = new RegExp("(?<!\\*)\\*\\*" + text + "\\*\\*(?!\\*)"),
            star1 = new RegExp("(?<!\\*)\\*" + text + "\\*(?!\\*)"),
            nostar = new RegExp(text);
          return [star4, star3, star2, star1, nostar];
        },
        setLinkTitle = (str) => {
          let title = str.indexOf(" "),
            i = title + 1; //inc space
          if (i > 0) {
            //only space and words allowed
            title = str
              .substring(i)
              .replace(/\s/g, "_")
              .replace(/\W/g, "")
              .replace(/_/g, " ");
            return str.substring(0, i) + '"' + title + '"';
          }
          return str;
        },
        hasRefLinks = (refindexes) => {
          return refindexes[0][0];
        },
        sortRefLinks = (skip) => {
          skip = skip ? "12" : "01";
          let incr = curry2(incrementor)(1),
            [s, e] = extents.map((o) => o(tx)),
            fixer = fixSelection(s, e, skip),
            fix = ptL(fixer, isOpBrkt, isClsBrktTo),
            reflink = ptL(queryTo, isClsBrktTo, isClsParaTo),
            reflinker = curry2(reflink)(0),
            soFix = comp(incr, fix);

          if (!reflinker(e)) {
            fixer = fixSelection(s, e);
            fix = ptL(fixer, isOpara, isClsParaTo);
            soFix = comp(incr, fix);
            reflinker = null;
          }
          return [soFix(), reflinker];
        },
        makeRefLink = ({ from, to, selection, attrs, external, open }) => {
          let i = getAvailableLink(selection),
            copyindexes = getCopyIndex(),
            res = validateRefLinks(copyindexes),
            ok = meta.doWhenFactory(3),
            doHasRefLinks = ptL(ok, res, hasRefLinks);
          if (invalidSelection(selection)) {
            return;
          }
          if (doHasRefLinks()) {
            doTrim();
            attrs = comp(sortLinkType, sortLinkAttributes)(
              attrs,
              external && external >= 0
            );
            tx.value =
              tx.value.slice(0, from) +
              setReferenceDef(selection, i) +
              setLinkIndex(tx.value.slice(to), i) +
              attrs;
            copyindexes = getCopyIndex();
            orderRefLinks(copyindexes);
          }
          return true;
        },
        listFromLine = (i = 0) => {
          let fixer = deferFix(),
            isLnSpc = deferSpaceLine(),
            o = fixer(isLnSpc, isLineTo, i),
            F = o.from,
            T = o.to,
            copy = tx.value,
            doTextArea = ptL(setTextArea, F - 1, T),
            str = copy.slice(F - 1, T),
            [[rule, rpl]] = mylist,
            pass = copy.slice(T, T + 2).match(/-|\d/);

          if (pass) {
            return listFromLine(11);
          }
          if (str.match(/(\d\.\s|-\s)/)) {
            /*EDGE CASE: if some fiend was editing multiple lists alternately things
             can get outtawhack, tog should be true at this point*/
            if (!tog) {
              mylist = mylist.reverse();
              [[rule, rpl]] = mylist;
            }
            doTextArea(str.replace(rule, "\n$1"));
            tog = false;
          } else {
            doTextArea(str.replace(/([^\n]+)(\n*)/g, rpl));
            tx.value = tx.value.replace(/([^\n]+\n)(\n)\n+/g, "$1$2");
            mylist = mylist.reverse();
            tog = !tog;
          }
        },
        hasEmphasis = (text = "") => {
          let fixer = deferFix();
          if (!text) {
            text = fixer(starFrom, starTo).selection;
            if (text.match(/^\W/) || text.match(/\W$/)) {
              //begins/end with space/line or..
              // if literal asterisks are used you only \*live twice > you only \
              return false;
            }
          }
          return text;
        },
        fixMulti = (partials) => {
          /*
          if there are MORE THAN ONE instance of supplied text, typically a single word
          then there is a chance that the wrong instance may be modified, typically the first occurence will be replaced regardless of where the location tx.selectionStart was
          so in a tiny fraction of cases we have more work to do...
          */
          let starStatus = () => {
              let fixer = deferFix(),
                start,
                end,
                fix = ptL(fixer, starFrom, starTo),
                i = 0,
                rego = /^\*/,
                soInc = curry2(incrementor),
                incrs = [
                  comp(soInc(4), fix),
                  comp(soInc(3), fix),
                  comp(soInc(2), fix),
                  comp(soInc(1), fix),
                ],
                ret,
                initial = incrs[0](); //inspect context before effectively trimming selection
              //ie ", **Polafrica***";
              start = initial.selection.match(/\*+(?=[^*])/g)[0];
              end = initial.selection.match(/(?<![^*])\*+/g)[0];
              end = initial.selection.match(/(?<!\*)\*+/g)[0];
              //edge case if by accident an uneven amount of '*' prefix and suffix text

              if (end.length !== start.length) {
                return null;
              }
              while (incrs[i]) {
                ret = incrs[i]();
                if (ret.selection.match(rego)) {
                  break;
                }
                i++;
              }
              return ret;
            },
            outcomes = [null, 3, 2, 1, 0],
            regy = /\*/g,
            mycb,
            i,
            o = starStatus();

          if (o) {
            i = o.selection.match(regy);
            i = i.length;
            i /= 2;
            //as it stands literal asterisks get included in the count
            if (Math.round(i) === i) {
              if (
                /*outcomes[i] maybe zero*/ typeof outcomes[i] !== "undefined"
              ) {
                mycb = partials[outcomes[i]];
                if (mycb) {
                  text = mycb(o.selection);
                  setTextArea(o.from, o.to, text);
                }
              }
            }
          }
          return null;
        },
        sortBoldItalics = (regexs, partials) => {
          let i = 0;
          while (regexs[i]) {
            if (tx.value.match(regexs[i])) {
              break;
            }
            i++;
          }
          if (partials[i]) {
            tx.value = partials[i](tx.value);
            return true;
          }
        },
        getRegReplacers = (text) => {
          let reg = new RegExp(text, "g"),
            result = tx.value.match(reg),
            regex = buildRegEx(text),
            //no. of stars.. in selection **selection**
            //4,3,2,1,0
            [a, b, c, d, e] = regex;
          return [
            result,
            (m, reg, rpl) => (o) => o[m](reg, rpl),
            [a, b, c, d, e],
          ];
        },
        replaceFormatted = (result, regexes, partials) => {
          if (result && result.length < 2) {
            return [regexes, partials];
          }
          return fixMulti(partials);
        },
        replaceBold = (txt = "") => {
          let text = hasEmphasis(txt);
          if (!text) {
            return false;
          }
          if (!quit) {
            let [result, cb, regexes] = getRegReplacers(text),
              [a, b, c, d, e] = regexes,
              callbacks = [
                cb("replace", a, text), //4 with 0
                cb("replace", b, `*${text}*`), //3 with 1
                cb("replace", c, text), //2 with 0
                cb("replace", d, `***${text}***`), //1 with 3
                cb("replace", e, text), //nochange
              ];
            return replaceFormatted(result, regexes, callbacks);
          }
        },
        replaceItalics = (txt = "") => {
          let text = hasEmphasis(txt);
          if (!text) {
            return false;
          }
          let [result, cb, regexes] = getRegReplacers(text),
            [a, b, c, d, e] = regexes,
            callbacks = [
              cb("replace", a, text),
              cb("replace", b, `**${text}**`),
              cb("replace", c, `***${text}***`),
              cb("replace", d, text),
              cb("replace", e, text),
            ];
          return replaceFormatted(result, regexes, callbacks);
        },
        format = (process) => {
          let invoke = (o, m, v) => o[m](v),
            getSelect = curry2((o, p) => o[p])("selection"),
            matcher = curry3(invoke)(/^\w.+[\w,!-]$/)("match"),
            fixer = deferFix(),
            fix = ptL(fixer, starFrom, starTo),
            doWhen = meta.doWhenFactory(3),
            getRes = curry2((o, p) => o[p])(0);
          return comp(
            process,
            ptL(doWhen, (o) => o, getRes),
            matcher,
            getSelect,
            fix
          );
        },
        emphasizer = (char, replacer) => {
          let actions = [
            ...defaultactions,
            atSpanText,
            pureLink,
            isImg,
            linkedImg,
          ];

          quit = actions.reduce((a, b) => (a ? a : b()), false);
          if (quit) return;
          let ran = false,
            setnew = comp(curry2(add)(char), curryL2(add)(char)),
            test = replacer(),
            process,
            func;
          if (test) {
            process = (text) => {
              if (text) {
                let [regs, cbs] = replacer(text),
                  [a, b, c, d] = regs;
                return sortBoldItalics([a, b, c, d], cbs);
              }
            };
            func = format(process);
            ran = func();
          }
          //fixMulti MAY have run instead,
          if (!ran) {
            let doTextArea,
              fixer = deferFix(),
              { from, to, selection } = fixer(notWordFrom, notWordTo);
            test = subSelect(from - 1, to + 1).match(/\*/);
            if (bailOut(["|", "_", "[", "!"], subSelect(from - 1, from))) {
              return;
            }
            //&& selection some empty text could be produced to which stars would be added
            if (!test && selection) {
              //would occur if we had uneven stars
              doTextArea = comp(ptL(setTextArea, from, to), setnew);
              doTextArea(selection);
            }
          }
        },
        fixLinkRefs = (article_title, id, content, myhash = "###") => {
          //for sanity's sake exclude period from a title, otherwise it's possible that an entire para could match
          var pukka = /(?:(#*)\[([^\]]+)\](?:\[([^\]]+)(\])|\(([^)]+)(\))))/,
            sanslink = /^(#*)([^\][()\n#]+)/,
            identity = meta.identity,
            getVal = deferMatch(),
            txval = deferValue(),
            beOK = meta.doWhenFactory(3),
            filta = ptL(
              beOK,
              identity,
              curry3(meta.invokeMethod)(identity)("filter")
            ),
            fresh = !id && !txval ? `${myhash}[${article_title}](#)` : "",
            aux = true,
            apply = "Apply article title to heading?",
            mismatch =
              "Title Mismatch! replace heading text with article title?",
            alter = "Change title field to match new heading?",
            alert =
              "Copy will be reverted: The title must be wrapped in a hyperlink",
            prompt = mismatch,
            mock = meta.fillArray(5),
            [_, hash, text] = getVal(pukka) ?? mock;
          if (fresh) {
            setTxValue(fresh);
            aux = false;
          }
          if (_) {
            [_, hash, text] = comp(filta, getVal)(pukka);
            if (article_title !== text) {
              var regex = new RegExp(`^(${hash})\\[(${text})\\]`);
              if (window.confirm(prompt)) {
                doSetTxValue(deferReplace(`$1[${article_title}]`)(regex));
                aux = false;
              } else {
                aux = window.confirm(alter) ? text : false;
              }
            }
          } else {
            [_, hash, text] = getVal(sanslink) ?? mock;
            if (!_) {
              revert();
              aux = false;
              return $("content").value ? window.alert(alert) : null;
            }
            aux = article_title !== text;
            prompt = text && aux ? mismatch : apply;
            regex = new RegExp(`^(${hash})(${text})`);
            if (window.confirm(prompt)) {
              text = article_title;
              aux = hash ? `$1[${text}](#)` : `${myhash}[${text}](#)`;
              doSetTxValue(deferReplace(aux)(regex));
              aux = false;
            } else {
              aux = window.confirm(alter) ? text : false;
            }
          }
          tx.selectionStart = 0;
          return aux;
        },
        heading = () => {
          let id = tx.form.pk?.value,
            title = tx.form.title.value,
            txval = deferValue();

          if (!txval && id) {
            return;
          }
          if (title) {
            let res = fixLinkRefs(title, id, txval),
              fixer = deferFix(),
              isHead = ptL(soMatch, /^#+/),
              o = fixer(isLineFrom, isLineTo),
              ok = meta.doWhenFactory(4),
              get = meta.getter,
              getter = ptL(ok, curry2(get)(0), curry2(get)("length")),
              selection = subSelect(o.from, o.to),
              setHead = ptL(setTextArea, o.from, o.to),
              header = ok(isHead, getter, selection);

            if (res && meta.isBoolean(res) && header) {
              if (header++ === 7) {
                //###[header](#) note neg lookahead to exlude basic INLINE hyperlink
                setHead("#" + selection.replace(/#(?!\))/g, ""));
              } else {
                setHead("#" + selection);
              }
            }
            if (meta.isString(res)) {
              tx.form.title.value = res;
            }
            tx.selectionEnd = o.from;
            postFocus(o.from, o.from);
            tx.focus();
          }
          return true;
        },
        hasSpan = () => {
          let open,
            reg = /<([^>]+)>[^<]+<\/(\w+)>/,
            match = tx.value.match(reg);
          if (match) {
            open = match[1].split(" ")[0];
            return open === match[2];
          } else {
            reg = /<!--[^-]+-->/;
            return tx.value.match(reg);
          }
        },
        spanner = () => {
          let doWhen = meta.doWhenFactory(3),
            f1 = isSpaceOrLine(false),
            f2 = isSpaceOrLine(true),
            fixoo = deferFix(),
            { from, to, selection, selected } = fixoo(f1, f2),
            doTextArea = comp(ptL(setTextArea, from, to), setSpan),
            soTextArea = comp(
              ptL(doWhen, (o) => o, doTextArea),
              isValidSelection
            );
          if (
            bailOut(
              ["*", "_", "[", "]", "!", "#", "(", ")"],
              selection.charAt(0)
            )
          ) {
            return;
          }
          let [start, end] = extents.map((o) => o(tx)),
            incr = curry2(incrementor)(10),
            fixer = deferFix(),
            fix = defer(fixer, isOpenTag, isCloseTag),
            soFix = comp(curry2(meta.getter)("selection"), incr, fix),
            fix1 = ptL(queryFrom, isCloseTag, f1, start),
            fix2 = ptL(queryTo, isOpenTagTo, f2, end),
            fix1a = ptL(queryFrom, isOpenTag, f1, start),
            fix2a = ptL(queryTo, isCloseTagTo, f2, end),
            doFix = comp(
              curry2(isOK)(soFix),
              ptL(isOK, hasSpan, isAnyDefer([fix1, fix2]))
            ),
            doFixComment = comp(
              curry2(isOK)(soFix),
              ptL(isOK, hasSpan, isAnyDefer([fix1a, fix2a]))
            ),
            spanSelection = doFix(),
            res = spanSelection?.match(/^<([^>]+)>/),
            commentSelection = doFixComment(),
            _comment = commentSelection?.match(/^<!--/),
            tag = res ? res[1] : null,
            styled = tag && tag.match(/\s/),
            comment = _comment ? _comment[0] : null,
            checkSpanTag = comment ? meta.always(false) : atSpanTag,
            actions = [
              defer(inWord, start, end),
              checkSpanTag,
              excludeHeadings,
              isLinkHeadingAlt,
              atRefLink,
              atInLineLink,
            ];
          quit = actions.reduce((a, b) => (a ? a : b()), false);
          if (!quit) {
            if (styled && tag.match(/style=/)) {
              spanSelection = spanSelection?.replace(
                /[^<]+(?=>)/,
                tag.split(" ")[0]
              );
              res = spanSelection?.match(/^<([^>]+)>/);
              tag = res ? res[1] : null;
            }
            if (tag && !comment) {
              let myselection,
                open,
                end,
                i = tag.length,
                l = spanSelection.length;
              myselection = spanSelection.substring(i + 2, l - (i + 3));
              open = new RegExp("<[^>]+>(?=" + myselection + ")");
              end = new RegExp("(?<=" + myselection + ")</[^>]+>");
              doClear(open);
              doClear(end);
            } else if (comment) {
              let i = 4,
                l = commentSelection.length - 3,
                myselection = commentSelection.substring(i, l),
                open = new RegExp("<!--(?=" + myselection + ")"),
                end = new RegExp("(?<=" + myselection + ")-->");
              doClear(open);
              doClear(end);
            } else {
              let tag = utils.getInlineTag(),
                output;
              if (tag) {
                if (meta.isArray(tag)) {
                  output = setStyleTag(tag, selection);
                  doSetTxValue(deferReplace(output)(selection));
                } else {
                  soTextArea(selection);
                  if (tag) {
                    let opentag = `<${tag}>`,
                      endtag = `</${tag}>`,
                      open = new RegExp("\\|(?=" + selection + ")"),
                      end = new RegExp("(?<=" + selection + ")\\|");

                    if (tag == "!") {
                      opentag = `<!--`;
                      endtag = `-->`;
                    }
                    doSetTxValue(deferReplace(opentag)(open));
                    doSetTxValue(deferReplace(endtag)(end));
                  }
                }
              }
            }
          }
        },
        lister = () => {
          let [from, to] = extents.map((o) => o(tx)),
            list = ["|", "_", "*", "!", "["],
            copy = tx.value,
            selected = from !== to,
            matcher = curry3(meta.invokeMethod)(/\n/)("match"),
            aft = comp(matcher, lookForward),
            fore = comp(matcher, curry2(lookBehind)(1)),
            validate = null;

          if (!selected || bailOut(list, copy.slice(from, from + 1))) {
            return;
          }
          if (matchLine(copy.slice(from, to))) {
            return listFromLine();
          } else {
            validate = ptL(
              isOK,
              isAll([ptL(fore, from), ptL(aft, to)]),
              ptL(
                setTextArea,
                from,
                to,
                copy.slice(from - 1, to).replace(/(\w+(\s|$))/g, "- $1\n")
              )
            );
            if (validate()) {
              mylist = mylist.reverse();
              tog = true;
            }
          }
        },
        clearLinkBridge = (start, end) => {
          let when = meta.doWhenFactory(0),
            skip = queryTo(isBackSlashTo, isClsBrktTo, end) ? 1 : 0,
            look = comp(
              curry2(lookAhead)(0),
              ptL(add, 1),
              ptL(add, start),
              ptL(fixTo, isClsBrktTo, start, skip)
            ),
            ref = comp(isOpeningBracket, look),
            inline = comp(isOpeningParanth, look),
            clear = ptL(clearLink, skip),
            actions = [ptL(when, ref, clear), ptL(when, inline, clear)];
          return actions.reduce((a, b) => (a ? a : b()), false);
        },
        relinker = () => {
          if (isAny([checkInlineStyle, checkRefStyle])) {
            let actions = [
              ptL(isOK, atInLineLink, ptL(inlineToRef, true)),
              ptL(isOK, atRefIndex, ptL(refToInline, true)),
              ptL(isOK, atRefLink, ptL(refToInline, true)),
            ];
            return actions.reduce((a, b) => (a ? a : b()), false);
          }
        },
        sortImageLink = (start, end) => {
          let i,
            cb,
            getImg,
            inlinelink1 = comp(ptL(queryFrom, isOpBrkt, isOpara), deferStart),
            inlinelink2 = comp(
              curry2(ptL(queryTo, isClsParaTo, isClsBrktTo))("01"),
              deferEnd
            ),
            inline = isOK(inlinelink1, inlinelink2),
            inlineref = isAny([inLinerRef, inLinerRefBrace]),
            //(pipe is cursor) advance to |]; then 1 to ]|); and test substring '('
            //or test /(?<=\[)\(/ BUT relies on support for positive lookbehind
            refinline = refInLiner(),
            inlinelinkduo = comp(
              curry2(ptL(queryTo, isOpParaTo, isOpBrktTo))(10),
              deferEnd
            ),
            fixer = deferFix(),
            endFunc = isClsParaTo,
            data = null,
            lookup = "def",
            doValidateAttrBlock = validateAttrBlock([targetReg, floatReg]);
          if (inline) {
            getImg = ptL(fixer, isEx, isClsParaTo);
            if (inlinelinkduo()) {
              fixer = delayFix(11);
              i = fixTo(isClsParaTo, end, 1);
              //grab the attr_block from INLINE link
              data = fetchAttrBlock(i, start, end);
              if (data.block) {
                endFunc = isClsBraceTo; //adjust the end point
                fixer = delayFix(10);
              }
              cb = ptL(fixer, isOpBrkt, endFunc);
              res = 1;
            } else if (inlineref) {
              fixer = delayFix(12);
              cb = ptL(fixer, isOpBrkt, isClsBrktTo);
              res = false;
              lookup = "unoref";
            }
          } else {
            fixer = delayFix("01");
            getImg = ptL(fixer, isEx, isClsBrktTo);
            // advance beyond space [m|y alt] [my alt|]
            end = advanceClsBrkt(); //just adjust end
            revertSelection(start, end);
            if (reflinkduo()) {
              fixer = delayFix(13);
              cb = ptL(fixer, isOpBrkt, isClsBrktTo);
              lookup = "refduo";
            } else if (refinline) {
              fixer = delayFix(10);
              i = fixTo(isClsParaTo, start);
              //grab the attr_block from INLINE link
              data = fetchAttrBlock(i, start, end, true);
              endFunc = data.block ? isClsBraceTo : endFunc;
              cb = ptL(fixer, isOpBrkt, endFunc);
              res = 0;
              lookup = "refuno";
            }
          }
          data = doValidateAttrBlock(data?.block || "");
          data = handleHyperLink(data, true);
          return clearLinkFromImage(getImg, cb, start, end, lookup, data);
        },
        checkIfIndex = (start, end) => {
          let reg,
            sp1 = isSpaceOrLine(),
            sp2 = isSpaceOrLine(true),
            a = ptL(queryFrom, isOpBrkt, sp1, start),
            b = ptL(queryTo, isClsBrktTo, sp2, end),
            fixer = fixSelection(start, end),
            f = ptL(
              isOK,
              isAnyDefer([a, b]),
              ptL(fixer, isOpBrkt, isClsBrktTo)
            ),
            o = f();
          if (o) {
            //https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/RegExp/escape#
            o = meta.escapeRegex(o.selection);
            reg = new RegExp("\\[" + o + "\\]:\\s?(.+)");
            return comp(curry3(meta.invokeMethod)(reg)("match"), deferValue);
          }
          return meta.always(false);
        },
        linker = () => {
          let [start, end] = extents.map((o) => o(tx)),
            notImg = meta.defernegate(isImg),
            isLinkedImg = ptL(isOK, linkedImg, ptL(sortImageLink, start, end)),
            deferClearLink = ptL(
              isOK,
              isAll([pureLink, notImg]),
              ptL(clearLinkBridge, start, end)
            ),
            quit = false,
            actions = [
              ...defaultactions,
              atSpanText,
              defer(inWord, start, end),
              checkIfIndex(start, end),
              isLinkedImg,
              deferClearLink,
            ];
          revertSelection(start, end);
          quit = actions.reduce((a, b) => (a ? a : b()), false);
          if (!quit) {
            var page = $("page").value,
              attrstr = window.prompt(
                "Enter hyperlink for/" + page,
                "https://www.bbc.co.uk"
              ),
              attrs,
              external,
              reflink = true,
              img = isImg(),
              isLnSpc = deferSpaceLine(),
              isLnSpcTo = deferSpaceLineTo(),
              //cursor needs to be here [my| alt text] NOT [my alt| text] || [my alt te|xt]
              i = fixFrom(isOpBrkt, start),
              fixer = fixSelection((start -= 0), end),
              txstatus = fixer(isLnSpc, isLnSpcTo),
              list = [targetReg, floatReg],
              data = "",
              doValidateAttrBlock = validateAttrBlock(list);

            if (attrstr) {
              external = attrstr.indexOf("http") >= 0;
              attrstr = setLinkTitle(attrstr);
              if (img) {
                i = fixFrom(isOpBrkt, start);
                fixer = fixSelection((start -= i), end);
                txstatus = fixer(isOpBrkt, isClsBrktTo);
                data = queryAttrBlock(start, end);
                //!!required otherwise myInlineLink() returns false
                revertSelection(start, end);
                if (myInlineLink()) {
                  setTextArea(
                    data.from,
                    data.to,
                    clearAttrBlock(data.selection)
                  );
                  reflink = false;
                  revertSelection(start, end);
                }
                //stand alone image implicilty floats left (courtesy of css) but a wrapped image requires a float, unless specifically overriding with .none
                data = doValidateAttrBlock(data?.block, img);
                data = handleHyperLink(data, !external);
                data = handleFloat(data);

                txstatus = wrapImage(attrstr, data);
                if (meta.isBoolean(txstatus)) {
                  //no further processing required
                  return true;
                }
              }
              if (reflink) {
                attrs = meta.join(" ", attrstr, data);

                return makeRefLink({
                  ...txstatus,
                  attrs,
                  external,
                });
              }
            }
          }
        }, //linker
        setInlineLink = () => {
          let incr = curry2(incrementor)(0),
            fixer = deferFix(),
            fix = ptL(fixer, isOpBrkt, isClsBrktTo),
            soFix = comp(incr, fix),
            either = eitherOr(
              isClosingBrace,
              isClosingParanth,
              inlineAttrBlock()
            ),
            endFunc = either(tx.selectionEnd, true),
            { from, to, selection } = soFix(); //for article copy
          incr = curry2(incrementor)(1);
          fix = ptL(fixer, isOpBrkt, endFunc);
          soFix = comp(incr, fix);
          //https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Destructuring_assignment#
          ({ from, to } = soFix()); //for extent of removal
          setTextArea(from, to, selection);
          return true;
        },
        setReferenceLink = (txstatus, copyindexes, skip) => {
          //txstatus return from sortRefLinks will be {from, to, [my link text][index]}
          let fixer = deferFix(),
            incr = skip ? curry2(incrementor)(21) : curry2(incrementor)(0),
            fix = ptL(fixer, isOpBrkt, isClsBrktTo),
            soFix = comp(incr, fix),
            { from, to, selection } = soFix(),
            index = grabIndex(tx.selectionStart, tx.selectionEnd),
            reg = new RegExp(`\\[${index}\\]:\\s(\\S+)`),
            [_, path] = tx.value.match(reg);

          if (path.match(/\.pdf$/)) {
            reLocate("unlink");
            return true;
          }
          if (index) {
            setTextArea(txstatus.from, txstatus.to, selection);
            orderRefLinks(copyindexes, index);
          }
          return true;
        },
        clearLink = (skip = 0) => {
          let copyindexes = getCopyIndex(),
            [refindexes] = validateRefLinks(copyindexes);
          if (hasRefLinks(refindexes)) {
            let [txstatus, reflink] = sortRefLinks(skip);
            return reflink
              ? setReferenceLink(txstatus, copyindexes, skip)
              : setInlineLink();
          } else if (!copyindexes[0]) {
            return setInlineLink();
          }
        },
        clearLinkFromImage = (
          getimg,
          getlnk,
          start,
          end,
          lookup,
          attr_block = ""
        ) => {
          //test getimg, getlnk are functions
          let myincr = curry2(incrementor)(1),
            img = comp(myincr, getimg)(),
            lnk = comp(myincr, getlnk)(),
            order = comp(orderRefLinks, getCopyIndex),
            setBodyTxt = ptL(setTextArea, lnk.from, lnk.to),
            doCopy = ptL(add, img.selection),
            //obtain index look up ref, grab attr_block
            aux = window.confirm("Do you want to remove the image as well?"),
            copied = "",
            refSetter = null;
          if (aux) {
            setBodyTxt(img.selection);
            /*if cursor(end) was here [![alt|][1]][2]
            then it will now be here ![alt][|1], || ![alt](|/path/to/file) as we've lost a new line and an opening brace
            see 1670ish*/
            //end -= lookForward(end, 10).match(/\[/) ? 0 : 1;
            end -= lookForward(end, 10).match(/\[/) ? 1 : 0;
            order();
            revertSelection(end);
            return image(true);
          }
          refSetter = refFactory[lookup];
          aux = refSetter(start, end, attr_block);
          copied = doCopy(aux);
          setBodyTxt(copied);
          order();
          return true;
        },
        //https://gist.github.com/rxaviers/7360908
        image = (flag) => {
          tx.focus();
          // flag from linker don't bother with useless checks
          let defaultpath = "file.jpeg",
            [start, end] = extents.map((o) => o(tx)),
            whenEv = meta.doWhen,
            fixer = fixSelection(start, end),
            txstatus = fixer(notWordFrom, notWordTo),
            txstatusDefer = ptL(fixer, isLineFrom, isLineTo),
            lineWinFrom = ptL(queryFrom, isLineFrom, isSpaceFrom),
            lineWinTo = ptL(queryTo, isLineTo, isSpaceTo),
            deferWinFrom = comp(invoke, ptL(doComp, deferStart), lineWinFrom),
            deferWinTo = comp(invoke, ptL(doComp, deferEnd), lineWinTo),
            //instructions will be to invoke an image on its own line
            promptWhen = comp(curry2(isOK)(deferWinTo), deferWinFrom),
            doTextArea = ptL(setTextArea, start, end),
            doClearImage = curry22(clearImage)(end)(start),
            undoImg = comp(curry2(isOK)(doClearImage), isImg),
            bailOut = ptL(
              bailBloodyOut,
              ["#", "!", "|", "_", "*", "[", "]"],
              txstatus.selection
            ),
            reg = /^.*[/\/]\w+\.\w{2,4}(?:\s[\w\s\-#<>]+)?$/,
            matchPathTitle = curry3(meta.invokeMethod)(reg)("match"),
            imgMsg = "Enter path to image, replace file.jpg with img filename",
            txtMsg = "Enter the alternate text for the image",
            pathtofile = "/resources/images/articles/fullsize/",
            promptImg = ptL(
              invokePair,
              window,
              "prompt",
              imgMsg,
              pathtofile + defaultpath
            ),
            promptTxt = ptL(invokePair, window, "prompt", txtMsg, "my icon"),
            zero = curry2((o, p) => o[p])(0),
            doPrompt = comp(
              curry2(whenEv)(revert),
              whenNot,
              curry2(whenEv)(invoke),
              curry2(whenEv)(ptL(doComp, txstatusDefer)),
              curry2(whenEv)(ptL(buildImage)),
              curry2(whenEv)(zero),
              curry2(whenEv)(matchPathTitle),
              curry2(whenEv)(promptImg),
              curry2(whenEv)(doTextArea),
              curry2(whenEv)(promptTxt),
              promptWhen
            ),
            callbacks = [bailOut, linkedImg, undoImg, doPrompt];
          callbacks = flag ? [undoImg] : callbacks;
          //mostly eliminate misplaced cursor..
          return callbacks.reduce((a, b) => (a ? a : b()), false);
        },
        capCheck = (old, neu) => {
          let res = [],
            k = 0,
            capMap = (str) => {
              let invk = meta.invokeMethod,
                getCharAt = curry3(invk)(0)("charAt"),
                upperDo = curry3(invk)(null)("toUpperCase"),
                fn = ptL(equal, getCharAt(str));
              return comp(fn, upperDo, getCharAt)(str);
            },
            mapped = old.split(" ").map(capMap);
          while (neu[k]) {
            if (mapped[k]) {
              res.push(neu[k].capitalize());
            } else {
              res.push(neu[k]);
            }
            k++;
          }
          return res.join(" ");
        };
      return {
        //all these are useless if an empty space was selected
        heading: comp(invoke, notEmpty([noOp, heading])),
        bold: comp(
          invoke,
          notEmpty([noOp, defer(emphasizer, "**", replaceBold)])
        ),
        ital: comp(
          invoke,
          notEmpty([noOp, defer(emphasizer, "*", replaceItalics)])
        ),
        para: function () {
          /*
          text needs to be on its own line
          para
          target text
          rest of para
          */
          let fixer = deferFix(),
            isLnSpc = isSpaceOrLine(false),
            o = fixer(isLnSpc, isLineTo),
            copy = tx.value.slice(o.from, o.to),
            matcher = curry3(meta.invokeMethod)(/\n/)("match"),
            aft = comp(matcher, lookForward),
            fore = comp(matcher, curry2(lookBehind)(1));
          ptL(
            isOK,
            isAll([ptL(fore, o.from), ptL(aft, o.to)]),
            ptL(setTextArea, o.from - 1, o.to + 1, copy)
          )();
        },
        line: function () {
          let i = 0,
            [from, to] = extents.map((o) => o(tx)),
            copy = tx.value.slice(from, to),
            incr = curry2(incrementor)(11),
            fix = deferFix(),
            soFix = comp(incr, fix),
            repl = copy ? window.prompt("Enter replacement text:") : null,
            f = eitherOr(isEqual("."), isLine),
            f1 = f(from, false),
            f2 = f(to, true),
            o = soFix(f1, f2);

          if (repl) {
            repl = repl.toLowerCase();
            const re = new RegExp(copy, "gi"),
              old = tx.value.match(re);
            while (old[i]) {
              let res = capCheck(old[i], repl.split(" ")),
                reg = new RegExp(old[i]),
                f = deferReplace(res)(reg);
              doSetTxValue(f);
              i++;
            }
            i = 0;
          } else {
            if (!o.selected) {
              /*
              advance cursor to keep period AND new line with the copy BEFORE the cursor..
              sentence1. sen|tence2.
              but ONLY if there is not already a new line BEFORE the target sentence
              sentence1.\n just add two spaces after
              senten|ce2.
              */
              i = o.selection.match(/\n/) ? 0 : 2;
              o.from += i;
              i = i ? " \n" : "  ";
              repl = true;
            }

            if (repl) {
              setTextArea(o.from, o.to, i + tx.value.slice(o.from, o.to));
            }
          }
        },
        span: comp(invoke, notEmpty([noOp, spanner])),
        link: comp(invoke, notEmpty([noOp, linker])),
        relink: comp(invoke, notEmpty([noOp, relinker])),
        img: comp(invoke, notEmpty([noOp, image])),
        list: comp(invoke, notEmpty([noOp, lister])),
        revert: revert,
        help: function () {
          let guide = $("mdguide"),
            help = $("help");
          toggleToolbar(guide);
          help.onclick = (e) => {
            toggleToolbar(guide);
          };
        },
        setCount: function (count) {
          this.count = count;
        },
      }; //ret
    }, //eof Maker,
    markup = (el) => {
      if (el) {
        let maker = Maker(el),
          guide = $("mdguide"),
          lookup = { title: "heading" },
          id = tx.form.pk?.value;
        x = $("michelf");
        remove(x);
        if (!utils.getCookie("js")) {
          doSetJSCookie();
          window.location = window.location.origin + `/article/edit/${id}`;
        }
        if (guide) {
          guide.addEventListener("click", function (e) {
            e.stopPropagation();
            //e.preventDefault();
            let exec = addKlas("hi"),
              undo = remKlas("hi"),
              active = meta.$Q(".hi"),
              getLink = curry3(utils.getTargetNode)("parentNode")(/^a$/i),
              tgt = getLink(e.target),
              txt = tgt && tgt?.getAttribute("href").substring(1),
              highlighter = comp(exec, getClassList).wrap(pass),
              lolighter = comp(undo, getClassList).wrap(pass),
              doExec = curry2(meta.doWhen)(highlighter),
              doUndo = curry2(meta.doWhen)(lolighter);
            doUndo(active);
            tgt = txt ? $(txt) : null;
            doExec(tgt?.parentNode);
          });
        }

        return (e) => {
          let id = e.target.alt || lookup[e.target.name];
          id = id.toLowerCase();
          e.preventDefault();
          if (id && maker[id]) {
            let fn = ptL(maker[id], e);
            meta.getResult(fn);
          }
        };
      }
    };
  return markup;
})(
  /(?<=\[)([^\]]+)\](?=(\(|\[))/g,
  /(?<!#)\[([^\]]+)\](?=(\(|\[))/g,
  /!\[([^\]]+)\](?=(\(|\[))/g,
  /(?<!\[)!\[([^\]]+)\](?=(\(|\[))/g,
  [/^target=(blank|_blank|_parent|_self|_top)$/],
  [/^\.(left|right|none)$/]
);
