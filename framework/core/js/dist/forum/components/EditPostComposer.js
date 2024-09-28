"use strict";(self.webpackChunkflarum_core=self.webpackChunkflarum_core||[]).push([[293],{3958:(t,s,e)=>{e.d(s,{Z:()=>h});var o=e(7905),r=e(2190),i=e(5226);class n extends r.Z{handler(){return this.attrs.when()||void 0}oncreate(t){super.oncreate(t),this.boundHandler=this.handler.bind(this),$(window).on("beforeunload",this.boundHandler)}onremove(t){super.onremove(t),$(window).off("beforeunload",this.boundHandler)}view(t){return m("[",null,t.children)}}flarum.reg.add("core","common/components/ConfirmDocumentUnload",n);var a=e(4944),d=e(1268),c=e(4041),l=e(3344),u=e(7323);class h extends r.Z{constructor(){super(...arguments),(0,o.Z)(this,"loading",!1),(0,o.Z)(this,"composer",void 0),(0,o.Z)(this,"jumpToPreview",void 0)}oninit(t){super.oninit(t),this.composer=this.attrs.composer,this.attrs.confirmExit&&this.composer.preventClosingWhen((()=>this.hasChanges()),this.attrs.confirmExit),this.composer.fields.content(this.attrs.originalContent||"")}view(){var t;return m(n,{when:this.hasChanges.bind(this)},m("div",{className:(0,l.Z)("ComposerBody",this.attrs.className)},m(u.Z,{user:this.attrs.user,className:"ComposerBody-avatar"}),m("div",{className:"ComposerBody-content"},m("ul",{className:"ComposerBody-header"},(0,d.Z)(this.headerItems().toArray())),m("div",{className:"ComposerBody-editor"},m(a.Z,{submitLabel:this.attrs.submitLabel,placeholder:this.attrs.placeholder,disabled:this.loading||this.attrs.disabled,composer:this.composer,preview:null==(t=this.jumpToPreview)?void 0:t.bind(this),onchange:this.composer.fields.content,onsubmit:this.onsubmit.bind(this),value:this.composer.fields.content()}))),m(i.Z,{display:"unset",containerClassName:(0,l.Z)("ComposerBody-loading",this.loading&&"active"),size:"large"})))}hasChanges(){const t=this.composer.fields.content();return Boolean(t)&&t!==this.attrs.originalContent}headerItems(){return new c.Z}loaded(){this.loading=!1,m.redraw()}}(0,o.Z)(h,"focusOnSelector",null),flarum.reg.add("core","forum/components/ComposerBody",h)},500:(t,s,e)=>{e.r(s),e.d(s,{default:()=>c});var o=e(6789),r=e(3958),i=e(8312),n=e(6597),a=e(9133);function d(t){o.Z.composer.isFullScreen()&&(o.Z.composer.minimize(),t.stopPropagation())}class c extends r.Z{static initAttrs(t){super.initAttrs(t),t.submitLabel=t.submitLabel||o.Z.translator.trans("core.forum.composer_edit.submit_button"),t.confirmExit=t.confirmExit||o.Z.translator.trans("core.forum.composer_edit.discard_confirmation"),t.originalContent=t.originalContent||t.post.content(),t.user=t.user||t.post.user(),t.post.editedContent=t.originalContent}headerItems(){const t=super.headerItems(),s=this.attrs.post;return t.add("title",m("h3",null,m(a.Z,{name:"fas fa-pencil-alt"})," ",m(n.Z,{href:o.Z.route.discussion(s.discussion(),s.number()),onclick:d},o.Z.translator.trans("core.forum.composer_edit.post_link",{number:s.number(),discussion:s.discussion().title()})))),t}jumpToPreview(t){d(t),m.route.set(o.Z.route.post(this.attrs.post))}data(){return{content:this.composer.fields.content()}}onsubmit(){const t=this.attrs.post.discussion();this.loading=!0;const s=this.data();this.attrs.post.save(s).then((s=>{if(o.Z.viewingDiscussion(t))o.Z.current.get("stream").goToNumber(s.number());else{const t=o.Z.alerts.show({type:"success",controls:[m(i.Z,{className:"Button Button--link",onclick:()=>{m.route.set(o.Z.route.post(s)),o.Z.alerts.dismiss(t)}},o.Z.translator.trans("core.forum.composer_edit.view_button"))]},o.Z.translator.trans("core.forum.composer_edit.edited_message"))}this.composer.hide()}),this.loaded.bind(this))}}flarum.reg.add("core","forum/components/EditPostComposer",c)}}]);
//# sourceMappingURL=EditPostComposer.js.map