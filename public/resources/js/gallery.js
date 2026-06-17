/*jslint nomen: true */
/*global window: false */
/*global poloAfrica: false */
/*global _: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}
(function (reverse, base_click_point, pagination) {
  function maxWidth(n) {
    return window.viewportSize.getWidth() <= n;
  }

  function paginate(paths, route, outer, k) {
    let L = paths.length,
      tmp = [];
    for (let i = 0; i < L; i++) {
      if (!i || (i + 1) % k) {
        tmp.push(paths[i]);
      } else {
        outer.push(tmp);
        tmp.push(paths[i]);
        tmp = [];
        k = route.shift();
      }
    }
    return outer;
  }

  function getExitPage(el) {
    if (el.id === "exit") {
      let q = $("slide") ? "#slide img" : "#base img",
        path = getExitPath(meta.$Q(q)),
        myform = $("play"),
        index,
        mypaths,
        mypath = path.split("/");
      mypath = mypath[mypath.length - 1];
      mypaths = myform.paths.value
        .split(";")
        .map((attrs) => attrs.split(",")[0]);
      index = mypaths.findIndex((pth) => pth === mypath);
      index = index >= 0 ? index + 1 : null;
      if (index) {
        return pagination.findIndex((i) => i >= index);
      }
    }
  }

  function getLocation(e) {
    var box = e.target.getBoundingClientRect(),
      threshold = (box.right - box.left) / 2;
    return e.clientX ? e.clientX - box.left > threshold : true;
  }

  function shuffle(outer, pred, paths) {
    if (!paths[0]) {
      return outer.flat();
    }
    let current = paths.shift(),
      lscp = current.filter((o) => o.orient === "landscape"),
      ptrt = current.filter((o) => o.orient === "portrait");
    if (!ptrt[0]) {
      outer.push(lscp);
      pred = true;
      return shuffle(outer, pred, paths);
    }
    if (pred) {
      outer.push(lscp.concat(ptrt));
      pred = !pred;
    } else {
      outer.push(ptrt.concat(lscp));
      pred = !pred;
    }
    return shuffle(outer, pred, paths);
  }

  function aggregate(paths) {
    let lscp = paths.filter((o) => o.orient === "landscape"),
      ptrt = paths.filter((o) => o.orient === "portrait");
    return lscp.concat(ptrt).flat();
  }

  function isPortrait(el) {
    var natural = utils.getNaturalDims(el),
      test = meta.compare((a, b) => a > b);
    return test(natural, "height", "width");
  }
  //return function to setTimeout
  function doPortrait(el) {
    return function () {
      var img = getMyImg(el);
      if (img) {
        if (!meta.$Q(".portrait")) {
          if (isPortrait(img)) {
            el.classList.add("portrait");
          } else if (meta.$Q(".portrait")) {
            el.classList.remove("portrait");
          }
        }
      }
    };
  }

  function getExitPath(el) {
    //potential train wreck
    try {
      return el
        .getAttribute("src")
        .split(/\w+(?=\/)/)
        .pop();
      /*
      append to existing url (route) to provide instructions
      to php to prevent current view of image reverting to the picture
      first displayed when the slideshow was initiated, set a cookie?
      */
    } catch (e) {}
  }

  function playMaker($player) {
    const func = meta.doAlternate(),
      displayPlaying = meta.pApply(
        meta.invokeMethodV,
        meta.$Q("main"),
        "classList",
        "playing"
      ),
      exec = compose(
        undostatic,
        displayPlaying,
        meta.always("add"),
        $player.play.bind($player, true)
      ),
      undo = compose(
        dostatic,
        displayPlaying,
        meta.always("remove"),
        $player.suspend.bind($player, null)
      );
    return func([exec, undo]);
  }

  function setupJS(paths, fullpath, el, $player, reverse) {
    //Painter is responsible for all DOM modifications and requires an iterator
    var factory = poloAfrica.Iterator(reverse),
      $painter = poloAfrica.Painter.from(el, factory(paths, fullpath)),
      $swapper = utils.applyClass("swap", document.body),
      $inplay = utils.applyClass("inplay", meta.$Q(".wrap")),
      $playing = utils.applyClass("playing", meta.$Q("main"), true),
      $hollywood = utils.applyClass("portrait", $("hollywood"));
    $painter.attach($player.setPlayer.bind($player), "query");
    $painter.attach($hollywood.query, "portrait");
    $player.attach($painter.updatePath.bind($painter, null, "slide"), "update");
    $player.attach($painter.updatePath.bind($painter, null, "base"), "base");
    $player.attach($painter.update.bind($painter), "update");
    $player.attach($painter.suspend.bind($painter, true), "update");
    $player.attach($painter.suspend.bind($painter, true), "delete");
    $player.attach($painter.suspend.bind($painter), "suspend");
    $player.attach($painter.updateOpacity.bind($painter), "opacity");
    $player.attach($inplay.apply, "update");
    $player.attach($inplay.undo, "delete");
    $player.attach($playing.undo, "delete");
    $player.attach($swapper.exec, "swap");
    $player.attach($swapper.undo, "delete");
    $player.attach($swapper.undo, "base");
  }

  const meta = poloAfrica.meta,
    utils = poloAfrica.utils,
    compose = meta.compose,
    $ = meta.$,
    ptL = meta.doPartial(),
    defer = meta.doPartial(true),
    curry2 = meta.curryRight(2),
    curry3 = meta.curryRight(3),
    curry4 = meta.curryRight(4),
    getMyImg = curry3(utils.getTargetNode)("firstChild")(/img/i),
    helpers = utils.getAjaxHelpers([
      /\w+\/$/,
      ///gallery\/display/,
      /user\/admin/,
      /#/,
    ]),
    checkDims = curry2(setTimeout)(66),
    myTouch =
      /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
        window.navigator.userAgent
      )
        ? true
        : false,
    allow = !myTouch ? 2 : 1,
    undostatic = compose(
      utils.remKlas("static"),
      utils.getClassList,
      meta.$$("controls")
    ),
    dostatic = compose(
      utils.addKlas("static"),
      utils.getClassList,
      meta.$$("controls")
    );

  //Bulletproof Ajax by Jeremy Keith
  const Hijax = () => {
    let canvas,
      callback,
      container,
      errorhandler,
      loading,
      url,
      data,
      request,
      timer,
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
      start = function () {
        request = utils.getHTTPObject();
        if (!request || !url) {
          return false;
        } else {
          initiateRequest();
          return true;
        }
      },
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
        let getSlide = compose(getMyImg, meta.$$("slide")),
          getOpacity = defer(utils.getComputedStyle, getSlide, "opacity"),
          calcNext = compose(curry2((a, b) => a < b)(0.5), getOpacity),
          $playb = $("playbutton"),
          $controls = $("controls"),
          exit = false,
          getMyLink = curry3(utils.getTargetNode)("parentNode")(/^a$/i),
          $player = poloAfrica.playerMaker(300, 99, 50),
          myPlayer = playMaker($player);
        if ($controls && !$controls.onclick) {
          $controls.onclick = (e) => {
            e.stopPropagation();
            e.preventDefault();
            let triggered = e.target === $controls;
            //no trigger when slideshow is INPLAY
            if (e.target.nodeName === "BUTTON" || triggered) {
              let el = e.target,
                path = el.parentNode.getAttribute("action"),
                undef,
                getNextImg = compose(
                  getMyImg,
                  $,
                  ptL(
                    meta.doBestInvoke,
                    [() => "base", () => "slide"],
                    calcNext,
                    undef
                  )
                );
              reverse = el.id === "backbutton" ? true : false;
              if (triggered) {
                el = utils.getTargetNode(
                  $controls,
                  /form/i,
                  base_click_point ? "lastChild" : "firstChild"
                );
                reverse = !base_click_point;
                path = el.getAttribute("action");
              }
              url += path;
              //scenario when slideshow is paused
              if ($("slide")) {
                //get the img.src of current or next depending on advance of the opacity, cute
                let el = getNextImg(), //run before $player.suspend
                  path = getExitPath(el);
                $player.suspend();
                //myPlayer = null;
                //above line not required as a fresh instance of xhr is created by start()
                //and xhr.captureData creates a new myPlayer
                url += path;
                utils.setCookie("loadpic", url);
              }
              return !start();
            }
          }; //back/forward
          //more specific stopPropagation to parent

          $playb.onclick = (e) => {
            e.stopPropagation();
            e.preventDefault();
            let form = e.target.parentNode,
              img = meta.$Q("#hollywood img"),
              imgpath = img ? img.getAttribute("src") : "",
              doTip = curry4(poloAfrica.Tooltip)(true)(allow)([
                "move mouse in and out of footer...",
                "...to toggle the display of control buttons",
              ]),
              datamap = (arr) => {
                return {
                  src: arr[0],
                  orient: arr[1],
                  alt: arr[2],
                };
              };
            //src only
            if (imgpath && !$("slide")) {
              let paths = form.paths.value.split(";"),
                doShift = defer((o, m) => o[m](), pagination, "shift"),
                mypaths = paths.map((str) => str.split(",")).map(datamap),
                rationalise = compose(
                  ptL(shuffle, [], true),
                  ptL(paginate, mypaths, pagination, []),
                  doShift
                );

              if (maxWidth(668)) {
                mypaths = aggregate(mypaths);
              } else {
                mypaths = rationalise(mypaths);
              }
              setupJS(mypaths, imgpath, img.parentNode, $player, reverse);
              doTip(meta.$("content")).init().run();
            }
            meta.getResult(myPlayer);
          };
        }
        if (container) {
          container.onclick = function (e) {
            let $controls = $("controls");
            //utils.removeElement($("error"));
            //check for controls we may be in gallery view
            if ($controls && e.target.nodeName === "IMG") {
              exit = true;
              //ie don't follow link to image in its own page in static mode
              if (meta.$Q(".inplay")) {
                utils.triggerEvent($("playbutton"), "click");
              } else if ($controls && $controls.onclick) {
                base_click_point = getLocation(e);
                utils.triggerEvent($controls, "click");
              }
              e.preventDefault();
            }

            if (!exit) {
              //maybe exit button or pagenav or pic
              e.stopPropagation();
              let pp,
                a = getMyLink(e.target);
              if (a && a.href) {
                url += a.getAttribute("href");
                pp = getExitPage(e.target);
                if (!isNaN(pp)) {
                  url += `/${pp}`;
                }
                return !start();
              }
            }
          };
        }
      }, //captureData,
    }; //proto
    return new Constr();
  };
  //initial listener is on <main id="content"> in order to intercept the first click
  //after that ajaxCB sets containers for
  let init = function (element) {
    var xhr = Hijax(),
      loader = defer(utils.displayLoading, element),
      fader = defer(utils.fadeUp, element, 255, 255, 222),
      error = defer(utils.displayError, element),
      reload = compose(checkDims, doPortrait, meta.$$("hollywood")),
      $controls = $("controls"),
      ajaxCB = function () {
        var links = meta.$Q("#content a", true),
          i = 0;

        for (i = 0; i < links.length; i++) {
          if (helpers.includeLinks(links[i])) {
            init(links[i]);
          }
        }
        init($("hollywood"));
      };
    if ($controls) {
      meta.$Q("footer").addEventListener("mouseover", undostatic);
      $controls.addEventListener("mouseover", dostatic);
      utils.removeElement(document.getElementById("enable-js"));
    }
    xhr.setContainer(element);
    xhr.setUrl("");
    xhr.setCanvas(meta.$Q("body"));
    xhr.setLoading();
    xhr.setCallback(ajaxCB);
    xhr.captureData();
  };

  addEventListener("DOMContentLoaded", defer(init, meta.$("content")));
})(false, false, [14, 28, 42, 54, 66, 78, 92]);

/*
  1
  non play buttons stop slideshow
  2
  string of all image paths 'tom.jpg, harry.jpg...', stored in php form.paths element, parent of button to include alt attribute: 'tom.jpg, "it be Tom"; harry.jpg, "it\s only our Harold";
  split on semi-colon then comma for pairs
  3
  iterator expects a collection and an index which could either be an integer
  OR an initial path
  AND NOTE the paths collection are JUST the UNIQUE file names "fred.jpg"
  whereas the path could be img/path/to/image/fred.jpg
  It should be the responsibility of iterator to sort this and FIND the initial index
  4
  Some browsers exhibit the strange behavior of firing the readystatechange
  event when abort is invoked. To counteract this, assign an empty function to
  onreadystatechange before aborting the Ajax request:
  */
