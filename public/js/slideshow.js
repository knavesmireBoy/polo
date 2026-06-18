/* eslint-disable indent */
/*global Publisher: false */
/*jslint nomen: true */
/*global window: false */
/*global poloAfrica: false */
/*global document: false */
/*global _: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}

const stateFactory = function ($context) {
  const fade = {
      validate: function () {
        return $context.i <= -1;
      },
      inc: function () {
        $context.i -= 1;
      },
      reset: function (arg) {
        $context.i = $context.dur;
        $context.notify(true, "update");
      },
    },
    fadeOut = {
      validate: function () {
        return $context.i <= -0.1;
      },
      inc: function () {
        $context.i -= 1;
      },
      reset: function () {
        $context.notify(false, "update");
        //ensure fadeIn will follow
        $context.setPlayer(true);
      },
    },
    fadeIn = {
      validate: function () {
        return $context.i >= 223;
      },
      inc: function () {
        $context.i += 1;
      },
      reset: function () {
        $context.notify(null, "base");
      },
    },
    actions = [fadeIn, fadeOut];
  return function (flag) {
    return flag ? actions.reverse()[0] : fade;
  };
};
poloAfrica.playerMaker = function (duration = 300, wait = 50, initial = 1) {
  class Player extends Publisher {
    constructor() {
      super();
      this.nextplayer = stateFactory(this);
      this.player = this.nextplayer();
      this.dur = duration;
      this.wait = wait;
      this.i = initial;
      this.t = null;
      return this;
    }
    play() {
      if (this.player.validate()) {
        this.player.reset();
      } else {
        this.notify(this.i / this.wait, "opacity");
        this.resume();
      }
    }
    suspend(flag) {
      //isNaN(undefiend) true  isNaN(null) false (1)
      const o = isNaN(flag) ? 1 : 0.5,
        action = isNaN(flag) ? "delete" : "suspend";
      this.notify(o, "opacity");
      window.cancelAnimationFrame(this.t);
      //window.clearTimeout(this.t);
      this.t = flag; //either set to undefined(forward/back/exit) or null(pause)
      this.notify(null, action);
    }
    setPlayer(arg, i) {
      if (arg) {
        this.notify(null, "swap");
      }
      this.player = this.nextplayer(arg);
      this.play();
    }
    resume() {
      this.player.inc();
      this.t = window.requestAnimationFrame(this.play.bind(this));
      //this.t = window.setTimeout(this.play.bind(this), wait);
    }
    static from(...args) {
      return new Player(...args);
    }
  }
  return Player.from();
};
