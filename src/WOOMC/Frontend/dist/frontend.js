!function(e){var o={};function n(t){if(o[t])return o[t].exports;var r=o[t]={i:t,l:!1,exports:{}};return e[t].call(r.exports,r,r.exports,n),r.l=!0,r.exports}n.m=e,n.c=o,n.d=function(e,o,t){n.o(e,o)||Object.defineProperty(e,o,{enumerable:!0,get:t})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,o){if(1&o&&(e=n(e)),8&o)return e;if(4&o&&"object"==typeof e&&e&&e.__esModule)return e;var t=Object.create(null);if(n.r(t),Object.defineProperty(t,"default",{enumerable:!0,value:e}),2&o&&"string"!=typeof e)for(var r in e)n.d(t,r,function(o){return e[o]}.bind(null,r));return t},n.n=function(e){var o=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(o,"a",o),o},n.o=function(e,o){return Object.prototype.hasOwnProperty.call(e,o)},n.p="",n(n.s=3)}([function(e,o){function n(e){return(n="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}var t=function(e){return"[".concat("WooCommerce-Multicurrency-Frontend","] ").concat(e)},r=function(e,o){var n=(new Error).stack.split("\n");return n.shift(),{level:e,timestamp:(new Date).toLocaleString(),url:window.location.href,trace:n}},c=function(e,o){if("object"===("undefined"==typeof woomc?"undefined":n(woomc))&&"Y"===woomc.console_log)switch(o){case"DEBUG":console.debug(t(e),r(o));break;case"ERROR":console.error(t(e),r(o));break;case"INFO":console.info(t(e),r(o));break;case"LOG":console.log(t(e),r(o));break;case"WARN":console.warn(t(e),r(o))}};e.exports={debug:function(e){return c(e,"DEBUG")},error:function(e){return c(e,"ERROR")},info:function(e){return c(e,"INFO")},log:function(e){return c(e,"LOG")},warn:function(e){return c(e,"WARN")}}},function(e,o,n){var t=n(0),r=n(5),c=function(){return r.get(woomc.cookieSettings.name)};e.exports={get:c,set:function(e){document.cookie="".concat(woomc.cookieSettings.name,"=").concat(e,";path=/;max-age=").concat(woomc.cookieSettings.expires,";samesite=strict")},notAsCached:function(){var e=c();return t.debug("[".concat("CurrencyCookie","] Cookies: active=").concat(e,"; woomc.currency=").concat(woomc.currency)),e!==woomc.currency&&(t.debug("activeCookie !== woomc.currency"),!0)}}},function(e,o){var n="woocommerce-multicurrency-reloaded";e.exports={set:function(){document.cookie="".concat(n,"=1;samesite=strict")},unset:function(){document.cookie="".concat(n,"=;expires=Thu, 01 Jan 1970 00:00:00 GMT;samesite=strict")},isSet:function(){return document.cookie.split(";").some((function(e){return e.trim().startsWith("".concat(n,"="))}))}}},function(e,o,n){var t=n(4),r=n(1),c=n(6),i=n(0);jQuery((function(e){t.met()&&(i.debug("[".concat("ROOT","] Geolocation=").concat(woomc.settings.woocommerce_default_customer_address)),i.debug("[".concat("ROOT","] Currency: on page=").concat(woomc.currency,", in cookie=").concat(r.get())),c.bust(),i.debug("[".concat("ROOT","] The End.")))}))},function(e,o,n){var t=n(0);e.exports={met:function(){return"undefined"==typeof woomc?(t.error("Internal Error: Undefined 'woomc'."),!1):navigator.cookieEnabled?(t.debug("Frontend prerequisites met."),!0):(t.error("Cookies disabled in the browser. Currency selector will not work."),!1)}}},function(e,o){e.exports={get:function(e){var o=document.cookie.split("; ").find((function(o){return o.startsWith("".concat(e,"="))}));return o?o.split("=")[1]:""}}},function(e,o,n){var t=n(0);e.exports={bust:function(){if(navigator.cookieEnabled)if(window.location.search.indexOf("currency=")>0)t.debug("Currency is set via URL. No additional processing is needed.");else{var e=n(2);if(e.isSet())return e.unset(),void(window.history.replaceState&&window.history.replaceState(null,null,window.location.href));if(n(1).notAsCached()){var o=n(7);t.debug("Reloading page..."),o.reload()}}else t.error("Cookies disabled in the browser. Cache buster will not work.")}}},function(e,o,n){e.exports={reload:function(){n(2).set(),jQuery("<form>",{method:"post",action:woomc.currentURL}).appendTo(document.body).submit()}}}]);
//# sourceMappingURL=frontend.js.map