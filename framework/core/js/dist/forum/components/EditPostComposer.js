"use strict";(self.webpackChunkflarum_core=self.webpackChunkflarum_core||[]).push([[948],{6296:(t,s,e)=>{e.d(s,{A:()=>h});var o=e(8805),r=e(5710),i=e(43);class n extends r.A{handler(){return this.attrs.when()||void 0}oncreate(t){super.oncreate(t),this.boundHandler=this.handler.bind(this),$(window).on("beforeunload",this.boundHandler)}onremove(t){super.onremove(t),$(window).off("beforeunload",this.boundHandler)}view(t){return m("[",null,t.children)}}flarum.reg.add("core","common/components/ConfirmDocumentUnload",n);var a=e(1611),d=e(5673),c=e(6064),l=e(4268),u=e(7891);class h extends r.A{constructor(){super(...arguments),(0,o.A)(this,"loading",!1),(0,o.A)(this,"composer",void 0),(0,o.A)(this,"jumpToPreview",void 0)}oninit(t){super.oninit(t),this.composer=this.attrs.composer,this.attrs.confirmExit&&this.composer.preventClosingWhen((()=>this.hasChanges()),this.attrs.confirmExit),this.composer.fields.content(this.attrs.originalContent||"")}view(){var t;return m(n,{when:this.hasChanges.bind(this)},m("div",{className:(0,l.A)("ComposerBody",this.attrs.className)},m(u.A,{user:this.attrs.user,className:"ComposerBody-avatar"}),m("div",{className:"ComposerBody-content"},m("ul",{className:"ComposerBody-header"},(0,d.A)(this.headerItems().toArray())),m("div",{className:"ComposerBody-editor"},m(a.A,{submitLabel:this.attrs.submitLabel,placeholder:this.attrs.placeholder,disabled:this.loading||this.attrs.disabled,composer:this.composer,preview:null==(t=this.jumpToPreview)?void 0:t.bind(this),onchange:this.composer.fields.content,onsubmit:this.onsubmit.bind(this),value:this.composer.fields.content()}))),m(i.A,{display:"unset",containerClassName:(0,l.A)("ComposerBody-loading",this.loading&&"active"),size:"large"})))}hasChanges(){const t=this.composer.fields.content();return Boolean(t)&&t!==this.attrs.originalContent}headerItems(){return new c.A}loaded(){this.loading=!1,m.redraw()}}(0,o.A)(h,"focusOnSelector",null),flarum.reg.add("core","forum/components/ComposerBody",h)},4191:(t,s,e)=>{e.r(s),e.d(s,{default:()=>c});var o=e(3554),r=e(6296),i=e(7880),n=e(7709),a=e(7479);function d(t){o.A.composer.isFullScreen()&&(o.A.composer.minimize(),t.stopPropagation())}class c extends r.A{static initAttrs(t){super.initAttrs(t),t.submitLabel=t.submitLabel||o.A.translator.trans("core.forum.composer_edit.submit_button"),t.confirmExit=t.confirmExit||o.A.translator.trans("core.forum.composer_edit.discard_confirmation"),t.originalContent=t.originalContent||t.post.content(),t.user=t.user||t.post.user(),t.post.editedContent=t.originalContent}headerItems(){const t=super.headerItems(),s=this.attrs.post;return t.add("title",m("h3",null,m(a.A,{name:"fas fa-pencil-alt"})," ",m(n.A,{href:o.A.route.discussion(s.discussion(),s.number()),onclick:d},o.A.translator.trans("core.forum.composer_edit.post_link",{number:s.number(),discussion:s.discussion().title()})))),t}jumpToPreview(t){d(t),m.route.set(o.A.route.post(this.attrs.post))}data(){return{content:this.composer.fields.content()}}onsubmit(){const t=this.attrs.post.discussion();this.loading=!0;const s=this.data();this.attrs.post.save(s).then((s=>{if(o.A.viewingDiscussion(t))o.A.current.get("stream").goToNumber(s.number());else{const t=o.A.alerts.show({type:"success",controls:[m(i.A,{className:"Button Button--link",onclick:()=>{m.route.set(o.A.route.post(s)),o.A.alerts.dismiss(t)}},o.A.translator.trans("core.forum.composer_edit.view_button"))]},o.A.translator.trans("core.forum.composer_edit.edited_message"))}this.composer.hide()}),this.loaded.bind(this))}}flarum.reg.add("core","forum/components/EditPostComposer",c)}}]);
//# sourceMappingURL=EditPostComposer.js.map