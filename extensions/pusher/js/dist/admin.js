(()=>{var e={n:t=>{var r=t&&t.__esModule?()=>t.default:()=>t;return e.d(r,{a:r}),r},d:(t,r)=>{for(var a in r)e.o(r,a)&&!e.o(t,a)&&Object.defineProperty(t,a,{enumerable:!0,get:r[a]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r:e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};(()=>{"use strict";e.r(t);const r=flarum.core.compat["admin/app"];var a=e.n(r);a().initializers.add("flarum-pusher",(()=>{a().extensionData.for("flarum-pusher").registerSetting({setting:"flarum-pusher.app_id",label:a().translator.trans("flarum-pusher.admin.pusher_settings.app_id_label"),type:"text"},30).registerSetting({setting:"flarum-pusher.app_key",label:a().translator.trans("flarum-pusher.admin.pusher_settings.app_key_label"),type:"text"},20).registerSetting({setting:"flarum-pusher.app_secret",label:a().translator.trans("flarum-pusher.admin.pusher_settings.app_secret_label"),type:"text"},10).registerSetting({setting:"flarum-pusher.app_cluster",label:a().translator.trans("flarum-pusher.admin.pusher_settings.app_cluster_label"),type:"text"},0)}))})(),module.exports=t})();
//# sourceMappingURL=admin.js.map