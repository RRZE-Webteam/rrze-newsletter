!function(){"use strict";var e={n:function(r){var s=r&&r.__esModule?function(){return r.default}:function(){return r};return e.d(s,{a:s}),s},d:function(r,s){for(var t in s)e.o(s,t)&&!e.o(r,t)&&Object.defineProperty(r,t,{enumerable:!0,get:s[t]})},o:function(e,r){return Object.prototype.hasOwnProperty.call(e,r)}},r=jQuery,s=e.n(r);s()(document).ready((()=>{s()(".rrze-newsletter-subscription #unsubscribe-all").prop("checked")&&(s()(".rrze-newsletter-subscription .checkbox").prop("disabled",!0),s()(".rrze-newsletter-subscription .list").toggleClass("list--disabled")),s()(document).on("change",".rrze-newsletter-subscription #unsubscribe-all",(()=>{s()(".rrze-newsletter-subscription .checkbox").prop("disabled",((e,r)=>!r)),s()(".rrze-newsletter-subscription .list").hasClass("list--disabled")?(s()(".rrze-newsletter-subscription .checkbox").prop("checked",!0),s()(".rrze-newsletter-subscription .list").removeClass("list--disabled")):(s()(".rrze-newsletter-subscription .checkbox").prop("checked",!1),s()(".rrze-newsletter-subscription .list").addClass("list--disabled"))}))}))}();
//# sourceMappingURL=subscription.js.map