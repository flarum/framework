import app from 'flarum/app';

app.initializers.add('flarum-markdown', () => {
    app.extensionData
        .for('flarum-markdown')
        .registerSetting({
            setting: 'flarum-markdown.mdarea',
            type: 'boolean',
            help: app.translator.trans('flarum-markdown.admin.settings.mdarea_help'),
            label: app.translator.trans('flarum-markdown.admin.settings.mdarea_label')
        });
});
