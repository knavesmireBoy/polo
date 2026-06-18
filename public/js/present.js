/*jslint nomen: true */
/* eslint-disable indent */
/* eslint-disable no-param-reassign */
/*global poloAfrica: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}
/*
The code made the assumption that headings wrap a hyperlink <H3><A>TITLE</A></H3>
and had to bear in mind what would happen if someone omitted to do that when editing
so getLink where we get the firstChild of H3 is now conditional see doGetCandidate
*/
(function (query, list, frag, qSelect, videoMobile, videoDesktop) {
  let throttlePause;
  //https://stackoverflow.com/questions/56137730/how-to-set-cookie-to-expire-when-tab-is-closed
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

  function doReload(page = "") {
    //required because of ajax where we enter in page a change to page b then resize..
    //when getPredicate fails it would load the entry page not the current page. side issue
    //window.location.reload();
    page = page === "admin" ? "user/admin" : page;
    page = page === "photos" ? "gallery/display" : page;
    window.location = window.location.origin + `/${page}`;
  }

  function anime2Desktop(tgt) {
    if (tgt) {
      let t = tgt.parentNode;
      t.parentNode.insertBefore(tgt, t);
    }
  }
  /*
  function funk(str) {
    const func = (transform) =>
      [...str].filter((char) => {
        return char === [transform(char)].join('');
  });
    const str1 = func((char) => char.toUpperCase());
    const str2 = func((char) => char.toLowerCase());
    return str1 + str2;
  }
*/
  function anime2Mobile(tgt) {
    if (tgt) {
      let t = tgt.nextSibling,
        kid = utils.getNextElement(t.firstChild);
      utils.insertAfter(tgt, kid);
    }
  }

  function fixAnchor(el) {
    if (el.nodeName === "A") {
      el.setAttribute("href", "#");
      el.removeAttribute("id");
    }
    return el;
  }

  function notBig(n) {
    return window.viewportSize.getWidth() <= n;
  }

  function doLeading(el) {
    if (el) {
      let px = utils.getComputedStyle(el, "height"),
        i = px ? parseFloat(px) : 0;
      if (i && i > 90) {
        el.style.lineHeight = 1.2;
      } else {
        el.style.lineHeight = 2;
      }
    }
  }

  function prepFlex(strings) {
    let map = strings.map((s) => s.length),
      min = Math.min(...map),
      max = Math.max(...map),
      diff = max - min;
    diff = diff / 2;
    return map.map((i) => Math.round(i / diff));
  }

  function applyFlex(els, ints) {
    /*
    piggy backing on the flex treatment so while we're here
    highight the current page in the nav bar;
    not required UNTIL we change page names from the LIST that SASS expects
    naturally assumes that the main div has the same id#home as the hyperlink /home/ text
    */
    let id = meta.$Q("body > div").id,
      cb = (el, i) => {
        let a = getLink(el),
          h = a.getAttribute("href");
        if (h.indexOf(id) > 0) {
          el.className = 'hi';
        }
        el.style.setProperty("flex-grow", ints[i]);
      };

    els.forEach(cb);
  }

  function getCandidate(options, arg) {
    return options.reduce((f, g) => f(arg) || g(arg));
  }

  function prePartial(m, p) {
    return function (o, v) {
      return o[m](p, v);
    };
  }

  function equals(a, b) {
    return a === b;
  }

  function doClassList(m) {
    return function (o, v) {
      return o["classList"][m](v);
    };
  }

  function doBestWrap(coll, pred, arg) {
    let domap = (fn, ag) => curryDefer(fn)(ag),
      func = curryDefer(pred)(arg),
      group = coll.map((item) => domap(item, arg));
    return getBest(group, func);
  }
  function remove(parent, child) {
    if (parent && child && child.parentNode) {
      parent.removeChild(child);
    }
  }

  function insert(container, node, refnode) {
    if (isMobile()) {
      /*insertAfter H3
      inserting BEFORE the element AFTER H3 would be predictable
      IF it was always the SAME element (para 95% of the time)
      BUT it isn't so you have to go with the nextSibling of H3
      WHICH would become the first inserted IMAGE on a 1:>1 (article:image) scenario
      then the order would get mixed up and your CSS would be outta whack*/
      // console.log(node, refnode);
      utils.insertAfter(node, refnode);
    } else {
      container.parentNode.insertBefore(node, container);
    }
  }

  function makeSubMenu(ancr) {
    function scroll(e) {
      let id = e.target.href.split("#");
      meta.$(id).scrollIntoView();
      return false;
    }

    return function (textnode, i) {
      let li = document.createElement("li"),
        a = document.createElement("a"),
        tgt = meta.$Q(".wrap"),
        ul;
      if (!i) {
        if (!ancr) {
          ancr = frag;
        }
        ul = comp(utils.setId("submenu").wrap(meta.pass), utils.doMake)("ul");
        ancr.appendChild(ul);
      } else {
        ul = $("submenu") ?? ancr.firstChild;
      }
      a.appendChild(textnode);
      //DOCKER leading slash
      a.setAttribute("href", tgt.id + "/#" + prepIds(textnode.nodeValue));
      li.appendChild(a);
      //a.onclick = scroll;
      ul.appendChild(li);
      ancr.appendChild(ul);
      return li;
    };
  }

  function finder(txt) {
    return function (el, i) {
      let tgt = doGetCandidate(el),
        str = curry2(meta.getter)("innerHTML")(tgt),
        reg = new RegExp(txt, "i"),
        pass = str.match(reg);
      return pass ? tgt : null;
    };
  }

  function getParaKlas(arr) {
    let lib = [
        ["zero"],
        ["uno"],
        [""],
        ["trio"],
        ["trio", "quatro"],
        ["trio", "quatro", "cinqo"],
      ],
      i = arr ? arr.length : 0;
    return lib[i];
  }

  function doDrill(coll, node) {
    return node && coll.reduce(meta.driller, node);
  }

  function doIterateFuncs(m, funcs, arg) {
    if (arg) {
      return funcs[m]((f) => f(arg));
    }
  }

  function doIterate(m, coll, cb) {
    if (cb) {
      return coll[m]((item) => cb(item));
    }
  }

  function isMultiSection(article) {
    if (article) {
      let prev = utils.getPrevElement(article.previousSibling),
        next = utils.getNextElement(article.nextSibling),
        tgt = next || prev;
      return tgt && tgt.nodeName === "ARTICLE";
    }
  }

  function fixIcon(images) {
    let el,
      match = curry3(meta.invokeMethod)(/(left|right|none)/)("match"),
      getClassName = curry2(meta.getter)("className"),
      filta = comp(match, getClassName),
      matchPara = curry3(utils.getTargetNode)("parentNode")(/^p$/i),
      lnk,
      i = 0,
      j,
      icons = images.filter(matchPara),
      links = icons
        .map(getLinkAbove)
        .filter((x) => x)
        .filter(filta);
    icons = [...icons, ...links];
    while ((el = icons[i])) {
      let width = el.width,
        j = el.width / el.height,
        x,
        y;
      lnk = getLinkAbove(el);
      if (lnk) {
        ({ x, y, width } = lnk.getBoundingClientRect());
        width = width *= j;
        lnk.style.maxWidth = `${width}px`;
      }
      i++;
    }
  }

  function preptoggler(flag) {
    function wrapper(f, e) {
      /* Current policy is to have one active section at a time
      If we wanted to provide the user control over toggling (the no-js behaviour)
      we can simply omit the triggerEvent below.
      NOTE we MUST use triggerEvent to maintain state for each section heading
       */
      e.preventDefault(); //CRUCIAL ELSE REDIRECT CITY
      e.stopPropagation();
      let active = meta.$Q(".active"),
        mysection = getSec(e.target),
        myarticle = getArt(e.target),
        multisection = isMultiSection(myarticle),
        dotrigger = false,
        notmatch = meta.negator(curry2(equals)(active));

      if (active) {
        if (!multisection && notmatch(mysection)) {
          dotrigger = true;
        } else if (multisection && notmatch(myarticle)) {
          dotrigger = true;
        }
      }
      if (dotrigger) {
        utils.triggerEvent(byTag(active, "a"), "click");
      }
      return f(e.target);
    }
    let i = 0,
      target = null,
      noOp = () => {},
      headers = meta.toArray(meta.$Q(qSelect, true)),
      getTargetParent = curry3(utils.getTargetNode)("parentNode"),
      getSec = meta.compose(getTargetParent(/SECTION/i), (el) => el),
      getArt = meta.compose(getTargetParent(/ARTICLE/i), (el) => el),
      undoActive = comp(remKlas("active"), getClassList),
      doActive = comp(addKlas("active"), getClassList),
      grabText = comp(curry2(meta.getter)("innerHTML"), doGetCandidate),
      copy = headers.map(grabText);
    while (headers[i]) {
      //console.log(utils.getComputedStyle(headers[i], 'line-height'));
      headers[i].setAttribute("id", prepIds(copy[i]));
      list.push(utils.doTextNow(copy[i]));

      let el = doGetCandidate(headers[i++]),
        multisection = meta.compose(isMultiSection, getMyArticle)(el),
        doAlt = meta.doAlternate(),
        doToggle = doAlt([doActive, undoActive]),
        soToggle = meta.compose(doToggle, multisection ? getArt : getSec),
        soToggleWrap = soToggle.wrap(wrapper),
        autoEdit = (e) => {
          /*
          click on link header to edit article if logged in, good idea??
          var db_user supplied by php; 39 refers to permissions, alter as required
          if (db_user >= 39) {
            e.preventDefault();
            let o = window.location.origin,
              p = window.location.pathname.split("/")[1],
              title = e.target.innerHTML.toLowerCase();
            window.location.href = o + "/" + p + "/article/edit/" + title;
          }
          */
        },
        //only run in mobile environment, maintain state
        callbacks = [soToggleWrap, autoEdit],
        cb = meta.pApply(doBestWrap, callbacks, isMobile);
      el = fixAnchor(el);
      el.addEventListener("click", meta.compose(meta.getResult, cb));
    }
    if (!isMobile()) {
      target = nav.find(finder(meta.$Q(".wrap").id));
    }
    list.map(makeSubMenu(target));
  }

  function loader(loading, isdesktop) {
    const sections = byTags(document, "section");
    let i = 0;
    while (sections[i]) {
      let sec = sections[i++],
        j = 0,
        matcher = curry2(methodInvoke("match")),
        getNext = utils.getNextElement,
        paNodeName = meta.pApply(doDrill, ["parentNode", "nodeName"]),
        nextSib = meta.pApply(doDrill, ["nextSibling"]),
        firstKid = meta.pApply(doDrill, ["firstChild"]),
        matchSection = comp(matcher(/section/i), paNodeName),
        matchArticle = comp(matcher(/article/i), paNodeName),
        matchForm = curry3(utils.getTargetNode)("parentNode")(/form/i),
        getArticle = curry3(utils.getTargetNode)("lastChild")(/article/i),
        article = getArticle(sec);
      let isDirectChild = meta.pApply(doIterateFuncs, "some", [
          matchArticle,
          matchSection,
        ]),
        doCardinality = doClassList("add"),
        doParas = meta.pApply(
          meta.doWhenFactory(3),
          meta.getResult,
          meta.pApply(doCardinality, article)
        ),
        myinputs = byTags(sec, "input"),
        //we don't want a live collection here..
        img = meta.toArray(byTags(sec, "img")),
        paras = meta.toArray(byTags(article, "p")),
        paraklas = paras ? getParaKlas(paras) : [],
        anchors = img.map(comp(getNext, nextSib)),
        headers = anchors.map(comp(getNext, firstKid)),
        images = img.filter(isDirectChild),
        L = myinputs.length - 1;

      while (myinputs[L]) {
        let input = myinputs[L--];
        if (input) {
          if (matchForm(input)) {
            //do nowt: exclude inputs that belong to a form
          } else {
            //but deal with direct form elements <section><input><label><article>
            remove(input.parentNode, getNext(input.nextSibling));
            remove(input.parentNode, input);
          }
        }
      }
      /*
      adds classes to articles that indicate the number of paras per article
      css then decorates as required.
      An improvement would be to apply only at 1260px and remove otherwise
      decided to ditch for the moment
      paraklas.forEach((item) => {
       // doParas(item);
      });
      */
      //awkward because ONE, bloody one, article has two pics and not ONE!
      if ((loading && !isdesktop) || !loading) {
        //no need to - and please don't - run IF loading on desktop
        while (images[j]) {
          let i = anchors[1] ? j : 0;
          //STRUCTURE
          //MOBILE NOJS AND DESKTOP: <sec <img>[<img>]<article <h3><nextel> > >
          //MOBILE JS: <sec <article <h3><img>[<img>]<nextel> > >
          //this much we know, headers will be the last in a potential series of siblings to the first img
          insert(anchors[i].parentNode, images[j], headers[headers.length - 1]);
          j++;
        }
      }
      fixIcon(img);
    }
    if (loading) {
      let x = document.getElementsByClassName("public");
      preptoggler(x);
    }
    doSetJSCookie();
  }

  const meta = poloAfrica.meta,
    utils = poloAfrica.utils,
    identity = (x) => x,
    curryDefer = (fun) => (a) => () => fun(a),
    curry2 = meta.curryRight(2),
    curry3 = meta.curryRight(3),
    curryL33 = meta.curryLeft(3, true),
    ptL = meta.doPartial(),
    defer = meta.doPartial(true),
    comp = meta.compose,
    methodInvoke = (m) => (o, v) => o && o[m] && o[m](v),
    invokeMethod = (o, m, v) => {
      return o && o[m](v);
    },
    setter = (p) => (o, v) => (o[p] = v),
    getBest = (coll, pred) => coll.reduce((a, b) => (pred(a, b) ? a : b)),
    $ = meta.$,
    navlinks = meta.toArray(meta.$Q("#nav > li > a", true)),
    navlist = meta.toArray(meta.$Q("#nav > li", true)),
    ints = navlinks.map((el) => el.innerHTML),
    getClassList = curry2(meta.getter)("classList"),
    setTxt = curry2(setter("innerHTML")),
    setVideoD = meta.always(videoDesktop),
    setVideoM = meta.always(videoMobile),
    setVideoText = meta.doAlternate()([setVideoD, setVideoM]),
    videoSpans = meta.toArray(meta.$Q(".rpl", true)),
    fixVideoText = comp(
      ptL(doIterate, "forEach", videoSpans),
      setTxt,
      setVideoText
    ),
    doSetMobileCookie = defer(
      meta.invokePair,
      utils,
      "setCookie",
      "mobile",
      "mobile"
    ),
    doSetJSCookie = defer(meta.invokePair, utils, "setCookie", "js", "js"),
    doUnsetJSCookie = defer(invokeMethod, utils, "deleteAllCookies", "js"),
    doUnsetMobileCookie = defer(
      invokeMethod,
      utils,
      "deleteAllCookies",
      "mobile"
    ),
    addKlas = ptL(meta.invokeMethodBridge, "add"),
    remKlas = ptL(meta.invokeMethodBridge, "remove"),
    getMyArticle = curry3(utils.getTargetNode)("parentNode")(/^article$/i),
    byTags = methodInvoke("getElementsByTagName"),
    byTag = comp(utils.getZero, byTags),
    getLink = curry3(utils.getTargetNode)("firstChild")(/^a$/i),
    getLinkAbove = curry3(utils.getTargetNode)("parentNode")(/^a$/i),
    doGetCandidate = meta.pApply(getCandidate, [getLink, identity]),
    threshold = Number(query.match(new RegExp("[^\\d]+(\\d+)[^\\d]+"))[1]),
    isMobile = defer(notBig, threshold),
    lower = curry3(meta.invokeMethod)(null)("toLowerCase"),
    repl = curry2(prePartial("replace", /[\s\/]/g))(""),
    prepIds = comp(lower, repl),
    nav = meta.toArray(meta.$Q("nav li", true)),
    toggleCookie = meta.doAlternate()([doUnsetMobileCookie, doSetMobileCookie]),
    anime = meta.$("ani"),
    toDesktop = defer(anime2Desktop, anime),
    toMobile = defer(anime2Mobile, anime),
    moveAnime = meta.doAlternate()([toMobile, toDesktop]),
    pp = meta.$Q(".wrap").id;
  curry2(applyFlex)(prepFlex(ints))(navlist);

  let getPredicate = isMobile,
    headers = meta.toArray(meta.$Q(qSelect, true));
  handler = function () {
    if (isMobile()) {
      headers.forEach(doLeading);
    }
    if (!getPredicate()) {
      getPredicate = meta.defernegate(getPredicate);
      loader();
      toggleCookie();
      fixVideoText();
      moveAnime();
      doReload(pp || "");
    }
    //utils.report();
  };
  window.onresize = meta.pApply(throttle, handler, 66);
  doUnsetJSCookie();
  addEventListener("load", ptL(loader, true, !isMobile()));
  //addEventListener("DOMContentLoaded", premier);
  let firstlink = meta.$Q("h3 a");
  if (!getPredicate()) {
    getPredicate = meta.defernegate(getPredicate);
    let reload = utils.getCookie("mobile");
    toggleCookie();
    fixVideoText();
    if (reload) {
      //if js disabled then resized...
      doReload(pp || "");
    }
  } else {
    utils.triggerEvent(firstlink, "click");
    if (!utils.getCookie("mobile")) {
      doSetMobileCookie();
      doReload(pp || "");
    }
    moveAnime();
    headers.forEach(doLeading);
  }
  /*
  func("aBcDeF"); // 'BDFace'
func("abcdef"); // 'abcdef'
func("ABCDEF"); // 'ABCDEF'
func("a b C D e"); // '  C D a b   e'
func("aaBBccDD"); // 'BBDDaacc'
*/
  //utils.report();
})(
  "(max-width: 750px)",
  [],
  document.createDocumentFragment(),
  ".public h3",
  "(shown above)",
  "(shown on the right)"
);

/*
MOST pages have a 1:1 SECTION:ARTICLE relationship
ENQUIRIES FIRST section has a 1:3
TVCOVERAGE is a hybrid having 1:3 BUT also has a COMMON heading 1:3
a MULTISECTION 1:n is best determined by querying the articles siblings 
*/
