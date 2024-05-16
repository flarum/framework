"use strict";(self.webpackChunkflarum_core=self.webpackChunkflarum_core||[]).push([[460],{5049:(t,o,r)=>{r.r(o),r.d(o,{default:()=>u});var s=r(7905),i=r(6789),e=r(899),a=r(8312),n=r(6403),l=r(1552),d=r(4041),c=r(6458);class u extends e.Z{constructor(){super(...arguments),(0,s.Z)(this,"identification",void 0),(0,s.Z)(this,"password",void 0),(0,s.Z)(this,"remember",void 0)}oninit(t){super.oninit(t),this.identification=(0,c.Z)(this.attrs.identification||""),this.password=(0,c.Z)(this.attrs.password||""),this.remember=(0,c.Z)(!!this.attrs.remember)}className(){return"LogInModal Modal--small"}title(){return i.Z.translator.trans("core.forum.log_in.title")}content(){return[m("div",{className:"Modal-body"},this.body()),m("div",{className:"Modal-footer"},this.footer())]}body(){return[m(n.Z,null),m("div",{className:"Form Form--centered"},this.fields().toArray())]}fields(){const t=new d.Z,o=(0,l.Z)(i.Z.translator.trans("core.forum.log_in.username_or_email_placeholder")),r=(0,l.Z)(i.Z.translator.trans("core.forum.log_in.password_placeholder"));return t.add("identification",m("div",{className:"Form-group"},m("input",{className:"FormControl",name:"identification",type:"text",placeholder:o,"aria-label":o,bidi:this.identification,disabled:this.loading})),30),t.add("password",m("div",{className:"Form-group"},m("input",{className:"FormControl",name:"password",type:"password",autocomplete:"current-password",placeholder:r,"aria-label":r,bidi:this.password,disabled:this.loading})),20),t.add("remember",m("div",{className:"Form-group"},m("div",null,m("label",{className:"checkbox"},m("input",{type:"checkbox",bidi:this.remember,disabled:this.loading}),i.Z.translator.trans("core.forum.log_in.remember_me_label")))),10),t.add("submit",m("div",{className:"Form-group"},m(a.Z,{className:"Button Button--primary Button--block",type:"submit",loading:this.loading},i.Z.translator.trans("core.forum.log_in.submit_button"))),-10),t}footer(){return m("[",null,m("p",{className:"LogInModal-forgotPassword"},m("a",{onclick:this.forgotPassword.bind(this)},i.Z.translator.trans("core.forum.log_in.forgot_password_link"))),i.Z.forum.attribute("allowSignUp")&&m("p",{className:"LogInModal-signUp"},i.Z.translator.trans("core.forum.log_in.sign_up_text",{a:m("a",{onclick:this.signUp.bind(this)})})))}forgotPassword(){const t=this.identification(),o=t.includes("@")?{email:t}:void 0;i.Z.modal.show((()=>r.e(502).then(r.bind(r,1839))),o)}signUp(){const t=this.identification(),o={[t.includes("@")?"email":"username"]:t};i.Z.modal.show((()=>r.e(395).then(r.bind(r,8686))),o)}onready(){this.$("[name="+(this.identification()?"password":"identification")+"]").trigger("select")}onsubmit(t){t.preventDefault(),this.loading=!0,i.Z.session.login(this.loginParams(),{errorHandler:this.onerror.bind(this)}).then((()=>window.location.reload()),this.loaded.bind(this))}loginParams(){return{identification:this.identification(),password:this.password(),remember:this.remember()}}onerror(t){401===t.status&&t.alert&&(t.alert.content=i.Z.translator.trans("core.forum.log_in.invalid_login_message"),this.password("")),super.onerror(t)}}flarum.reg.add("core","forum/components/LogInModal",u)},6403:(t,o,r)=>{r.d(o,{Z:()=>e});var s=r(2190),i=r(4041);class e extends s.Z{view(){return m("div",{className:"LogInButtons"},this.items().toArray())}items(){return new i.Z}}flarum.reg.add("core","forum/components/LogInButtons",e)}}]);
//# sourceMappingURL=LogInModal.js.map