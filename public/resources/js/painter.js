/*jslint nomen: true */
/*global Publisher: false */
/*global poloAfrica: false */
/* eslint-disable indent */

(function (pause, pause_long) {
  "use strict";
  const meta = poloAfrica.meta,
    utils = poloAfrica.utils,
    $ = meta.$,
    $$ = meta.$$,
    compose = meta.compose,
    getProp = (o, p) => o[p],
    curry2 = meta.curryRight(2),
    curry3 = meta.curryRight(3),
    getResult = meta.getResult,
    doWhen = meta.doWhenFactory(3),
    _getSRC = curry2(getProp)("src"),
    _getALT = curry2(getProp)("alt"),
    _getORIENT = curry2(getProp)("orient"),
    always = meta.always,
    invokeMethod = meta.invokeMethod,
    ptL = meta.doPartial(),
    deferPTL = meta.doPartial(true),
    getStyle = curry2(meta.getter)("style"),
    setProperty = meta.pApply(
      meta.mittelFactory(getStyle),
      meta.invokePair,
      "setProperty"
    ),
    getTargetNode = utils.getTargetNode,
    getMyImg = curry3(getTargetNode)("firstChild")(/img/i),
    getMyLink = curry3(getTargetNode)("parentNode")(/a/i),
    setDisplay = setProperty("display"),
    setOpacity = setProperty("opacity"),
    setFloat = setProperty("float"),
    //NOTES: 2
    setBorder = curry2(setProperty("border"))("solid 1px black"),
    hide = compose(curry2(setDisplay)("none")),
    show = compose(curry2(setDisplay)("block")),
    doFloat = meta.pApply(
      invokeMethod,
      [$$("base"), $$("slide")],
      "forEach",
      compose(curry2(setFloat)("left"))
    ),
    //note using deferredGetId : $$ as the original element may have been overwritten by an ajax call but we know it will exist in the new DOM
    removePause = ptL(
      doWhen,
      meta.identity,
      ptL((o, p, v) => getResult(o)[p](v), $$("hollywood"), "removeChild")
    ),
    getAsset =
      (path) =>
      (prefix = "") => {
        return `${prefix}/resources/images/dev/${path}`;
      },
    getPause = getAsset(pause),
    getPortraitPause = getAsset(pause_long),
    setMargin = setProperty("margin-left"),
    setInplayMargin = curry2(setMargin)("-100%"),
    resetMargin = curry2(setMargin)(0),
    postQueryHeight = (flag, base, slide) => {
      if (base) {
        //maybe hidden
        const swap = compose(resetMargin, always(slide), hide),
          unswap = compose(setInplayMargin, always(slide), show);
        meta.doBest([swap, unswap], always(flag), always(base))();
      }
    },
    queryHeight = function (base, slide, flag) {
      let b = getMyImg(base),
        s = getMyImg(slide),
        bool = b.dataset.orient !== s.dataset.orient;
      //can't calculate until initial path is set and set border here too
      if (s.src) {
        setBorder(getMyLink(slide));
        postQueryHeight(bool, base, slide);
        if (!flag) this.isPortrait(s);
        return bool;
      }
      return false;
    };

  poloAfrica.Painter = class extends Publisher {
    constructor(base, $iterator) {
      super();
      if (base) {
        //base is 'A' element
        base = getResult(base);
        var doLink = utils.doMakeDefer("a"),
          doImg = utils.doMakeDefer("img"),
          deferOpacity = curry2(setOpacity)(3),
          setLink = utils.prep2Append(
            doLink,
            utils.prepAttrs([utils.setId, utils.setHyper], ["slide", "."])
          ),
          setImg = utils.prep2Append(
            doImg,
            utils.prepAttrs([utils.setAlt], [""])
          ),
          //defer setting src NOTES: 1
          slide = compose(deferOpacity, setImg, setLink)(base.parentNode);
        this.base = getMyImg(base);
        this.slide = slide;
        this.iterator = $iterator.init();
      }
    }
    updateOpacity(o) {
      removePause($("paused"));
      if (!this.slide.onload) {
        queryHeight.call(this, base, slide, true);
        doFloat();
        show(getMyLink(this.slide));
        this.update(true);
      }
      setOpacity(this.slide, o);
    }
    updatePath(data, type) {
      let el = type === "slide" ? this.slide : this.base;
      if (!data) {
        data =
          type === "slide"
            ? this.iterator.getCurrent()
            : this.iterator.forward();
        if (type === "base") {
          show(getMyLink(this.base));
        }
      }
      let mydata = getResult(data),
        prepath = mydata.prepath,
        src = _getSRC(mydata),
        alt = _getALT(mydata),
        o = _getORIENT(mydata);
      utils.setSrc(prepath + src)(el);
      utils.setAlt(alt)(el);
      el.dataset.orient = o;
    }
    isPortrait(el) {
      let bool = el.dataset.orient === "portrait";
      this.notify(bool, "portrait");
    }
    update(flag) {
      //flag from $slideplayer (mostly see notes)
      this.base.dataset["test"] = "funky";
      this.base.onload = null;
      const that = this,
        deferForward = deferPTL(invokeMethod, this.iterator, "forward", null);
      this.slide.onload = (e) => {
        setOpacity(that.base, .01);
        setOpacity(e.target, 3);
        if (flag) {
          that.updatePath(deferForward.bind(that), "base");
        }
        that.isPortrait(e.target);
      };
      this.base.onload = (e) => {
        var res = queryHeight.call(
          that,
          getMyLink(that.base),
          getMyLink(that.slide),
          true
        );
        setOpacity(e.target, 1);
        that.notify(res, "query");
      };
    }
    suspend(arg) {
      var $paused = meta.$("paused");
      if (!arg && !$paused) {
        //make pause;

        var path = meta.$Q(".portrait") ? getPortraitPause("") : getPause(""),
          //DOCKEROO ? '' : '.'
          doLink = utils.doMakeDefer("a"),
          doImg = utils.doMakeDefer("img"),
          setLink = utils.prep2Append(
            doLink,
            utils.prepAttrs([utils.setId, utils.setHyper], ["paused", "."])
          ),
          setImg = utils.prep2Append(
            doImg,
            utils.prepAttrs(
              [utils.setAlt, utils.setSrc],
              ["pause button", path]
            )
          );
        compose(
          setImg,
          compose(curry2(setFloat)("left")),
          setInplayMargin,
          setLink
        )(getMyLink(this.base).parentNode);
      } else if (arg) {
        //this action is delayed so remove on actual event listener
        //removePause($paused);
      }
    }
    cleanup() {
      /*
      NOT REQUIRED AS WE WILL HAVE A NEW DOM ON TERMINATION OF SLIDESHOW, FRESH SLATE
      displayPause("remove");
      let p = meta.$("paused");
      if (p) {
        p.parentNode.removeChild(p);
      }
      show(getMyLink(this.base));
      hide(getMyLink(this.slide));
      resetMargin(getMyLink(this.slide));
      this.base.onload = null;
      this.slide.onload = null;
      undoStyle();
      hide(meta.$("slide"));
      */
    }
    static from(...args) {
      return new poloAfrica.Painter(...args);
    }
  };
})("pause.png", "pauseLong.png");
//NOTES
/*1
//when opacity 0
//swap base into slide
//reset opacity
//set base to next
(10-05-23)
update is triggered on completion of countdown and sets the next onload event for the slide image which is EITHER setting the next image src OR DELAYING setting src and effectively hiding the base image fading out the current image to minus opacity and fading back in with new pic of a different size (width is constrained)
AND WHY: to allow for images of different height (would get away with it if shorter was behind taller, but eventually the situation will be reversed)
The src gets set when opacity is zero. BUT the image needs an onload event adding BEFORE that occurs So we have to PRIME it by forcing update to run on the maiden voyage (if ! slide.onload) AND technically running with a flag of true wouldn't be correct if the second image was a different size.
Ideally group diff sizes together
OR
FIND ANOTHER WAY using an auxillary image to do the swapping and re-ordering a group of pics. TBC
*2
UNTIL src is applied we have to hide the border of a#slide which otherwise sits below base image as a collapsed box with a total depth of 2px
ALL OF THIS to DIRECTLY style #slide and #base rather than relying on CSS (more plug in and play)
Both of these issues would be solved by having a slide element in place in the PHP template and just hiding it initially. This would mean we don't have to set the initial src and trigger the img.onload issues in NOTES 1 above
BUT abiding by the rules of progressive enhancement the #slide element should only exit courtesy of javascript

AUX
dupe base image TWICE
BASE => SLIDE
BASE2 => BACKING
BASE3 => SWAPPER

WHEN SLIDE IS 0 OPACITY MOVE TO BOTTOM OF STACK
BACKING => SLIDE
SWAPPER => BACKING
SLIDE => SWAPPER
SET SWAPPER TO NEXT
SET SLIDE TO 100
FADE
*/
