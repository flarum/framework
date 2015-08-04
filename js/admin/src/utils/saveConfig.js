export default function saveConfig(config) {
  const oldConfig = JSON.parse(JSON.stringify(app.config));

  Object.assign(app.config, config);

  return app.request({
    method: 'POST',
    url: app.forum.attribute('apiUrl') + '/config',
    data: {config}
  }).catch(error => {
    app.config = oldConfig;
    throw error;
  });
}
