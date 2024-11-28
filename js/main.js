let cmbl = function () {
  let actions = {
    /** Go to url */
    goTo: function (url) {
      document.location.href = url;
    },
    /** */
    init: function (config) {

    }
  };
  return {
    init: actions.init,
    goTo: actions.goTo
  }
}

var il = il || {}; // var important!
il.Plugins = il.Plugins || {};
il.Plugins.CustomMetaBarLinks = il.Plugins.CustomMetaBarLinks || {};
il.Plugins.CustomMetaBarLinks = cmbl($);