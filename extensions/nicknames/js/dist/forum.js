(()=>{var e={n:t=>{var n=t&&t.__esModule?()=>t.default:()=>t;return e.d(n,{a:n}),n},d:(t,n)=>{for(var r in n)e.o(n,r)&&!e.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:n[r]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r:e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};(()=>{"use strict";e.r(t),e.d(t,{extend:()=>_});const n=flarum.reg.get("core","forum/app");var r=e.n(n);const a=flarum.reg.get("core","common/extend"),o=flarum.reg.get("core","common/components/Button");var i=e.n(o);const s=flarum.reg.get("core","common/utils/extractText");var c=e.n(s);const u=flarum.reg.get("core","common/utils/Stream");var l=e.n(u);const d=flarum.reg.get("core","common/components/FormModal");var f=e.n(d);function p(e){return p="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},p(e)}function h(e,t,n){return(t=function(e){var t=function(e,t){if("object"!==p(e)||null===e)return e;var n=e[Symbol.toPrimitive];if(void 0!==n){var r=n.call(e,t);if("object"!==p(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(e)}(e,"string");return"symbol"===p(t)?t:String(t)}(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}class b{constructor(){h(this,"element",void 0),h(this,"attrs",void 0),h(this,"state",void 0)}oninit(e){this.setAttrs(e.attrs)}oncreate(e){this.element=e.dom}onbeforeupdate(e){this.setAttrs(e.attrs)}onupdate(e){}onbeforeremove(e){}onremove(e){}$(e){const t=$(this.element);return e?t.find(e):t}static component(e,t){void 0===e&&(e={}),void 0===t&&(t=null);const n={...e};return m(this,n,t)}setAttrs(e){if(void 0===e&&(e={}),this.constructor.initAttrs(e),e){if("children"in e)throw new Error("[".concat(this.constructor.name,'] The "children" attribute of attrs should never be used. Either pass children in as the vnode children or rename the attribute'));if("tag"in e)throw new Error("[".concat(this.constructor.name,'] You cannot use the "tag" attribute name with Mithril 2.'))}this.attrs=e}static initAttrs(e){}}function g(e){var t,n,r="";if("string"==typeof e||"number"==typeof e)r+=e;else if("object"==typeof e)if(Array.isArray(e))for(t=0;t<e.length;t++)e[t]&&(n=g(e[t]))&&(r&&(r+=" "),r+=n);else for(t in e)e[t]&&(r&&(r+=" "),r+=t);return r}flarum.reg.add("flarum-nicknames","../../../../framework/core/js/src/common/Component",b);const k=function(){for(var e,t,n=0,r="";n<arguments.length;)(e=arguments[n++])&&(t=g(e))&&(r&&(r+=" "),r+=t);return r},v=k;flarum.reg.add("flarum-nicknames","../../../../framework/core/js/src/common/utils/classList",k);class y extends b{view(e){const{label:t,description:n,className:r,...a}=e.attrs;return m("div",Object.assign({className:v("Form",r)},a),m("div",{className:"Form-header"},t&&m("label",null,t),n&&m("p",{className:"helpText"},n)),m("div",{className:"Form-body"},e.children))}}flarum.reg.add("flarum-nicknames","../../../../framework/core/js/src/common/components/Form",y);class N extends(f()){oninit(e){super.oninit(e),this.nickname=l()(r().session.user.displayName())}className(){return"NickameModal Modal--small"}title(){return r().translator.trans("flarum-nicknames.forum.change_nickname.title")}content(){return m("div",{className:"Modal-body"},m(y,{className:"Form--centered"},m("div",{className:"Form-group"},m("input",{type:"text",autocomplete:"off",name:"nickname",className:"FormControl",bidi:this.nickname,disabled:this.loading})),m("div",{className:"Form-group Form-controls"},m(i(),{className:"Button Button--primary Button--block",type:"submit",loading:this.loading},r().translator.trans("flarum-nicknames.forum.change_nickname.submit_button")))))}onsubmit(e){e.preventDefault(),this.nickname()!==r().session.user.displayName()?(this.loading=!0,r().session.user.save({nickname:this.nickname()},{errorHandler:this.onerror.bind(this)}).then(this.hide.bind(this)).catch((()=>{this.loading=!1,m.redraw()}))):this.hide()}}flarum.reg.add("flarum-nicknames","forum/components/NicknameModal",N);const x=flarum.reg.get("core","common/extenders");var S=e.n(x);const w=flarum.reg.get("core","common/models/User");var M=e.n(w);const _=[new(S().Model)(M()).attribute("canEditNickname")];r().initializers.add("flarum/nicknames",(()=>{(0,a.extend)("flarum/forum/components/SettingsPage","accountItems",(function(e){"nickname"===r().forum.attribute("displayNameDriver")&&this.user.canEditNickname()&&e.add("changeNickname",m(i(),{className:"Button",onclick:()=>r().modal.show(N)},r().translator.trans("flarum-nicknames.forum.settings.change_nickname_button")))})),(0,a.extend)("flarum/common/components/EditUserModal","oninit",(function(){this.nickname=l()(this.attrs.user.displayName())})),(0,a.extend)("flarum/common/components/EditUserModal","fields",(function(e){"nickname"===r().forum.attribute("displayNameDriver")&&this.attrs.user.canEditNickname()&&e.add("nickname",m("div",{className:"Form-group"},m("label",null,r().translator.trans("flarum-nicknames.forum.edit_user.nicknames_heading")),m("input",{className:"FormControl",placeholder:c()(r().translator.trans("flarum-nicknames.forum.edit_user.nicknames_text")),bidi:this.nickname})),100)})),(0,a.extend)("flarum/common/components/EditUserModal","data",(function(e){"nickname"===r().forum.attribute("displayNameDriver")&&this.attrs.user.canEditNickname()&&this.nickname()!==this.attrs.user.displayName()&&(e.nickname=this.nickname())})),(0,a.extend)("flarum/forum/components/SignUpModal","oninit",(function(){"nickname"===r().forum.attribute("displayNameDriver")&&(this.nickname=l()(this.attrs.username||""))})),(0,a.extend)("flarum/forum/components/SignUpModal","onready",(function(){"nickname"===r().forum.attribute("displayNameDriver")&&r().forum.attribute("setNicknameOnRegistration")&&r().forum.attribute("randomizeUsernameOnRegistration")&&this.$("[name=nickname]").select()})),(0,a.extend)("flarum/forum/components/SignUpModal","fields",(function(e){"nickname"===r().forum.attribute("displayNameDriver")&&r().forum.attribute("setNicknameOnRegistration")&&(e.add("nickname",m("div",{className:"Form-group"},m("input",{className:"FormControl",name:"nickname",type:"text",placeholder:c()(r().translator.trans("flarum-nicknames.forum.sign_up.nickname_placeholder")),bidi:this.nickname,disabled:this.loading||this.isProvided("nickname"),required:r().forum.attribute("randomizeUsernameOnRegistration")})),25),r().forum.attribute("randomizeUsernameOnRegistration")&&e.remove("username"))})),(0,a.extend)("flarum/forum/components/SignUpModal","submitData",(function(e){if("nickname"===r().forum.attribute("displayNameDriver")&&r().forum.attribute("setNicknameOnRegistration")&&(e.nickname=this.nickname(),r().forum.attribute("randomizeUsernameOnRegistration"))){const t=new Uint32Array(2);crypto.getRandomValues(t),e.username=t.join("")}}))}))})(),module.exports=t})();
//# sourceMappingURL=forum.js.map