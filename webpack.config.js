const Encore = require('@symfony/webpack-encore');
const fs = require('fs');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
  .setOutputPath('./src/Resources/dist/bin/')
  .setPublicPath('/src/Resources/dist/bin/')
  .addEntry('prism', './src/Resources/node/prism.js')
  .disableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableBuildNotifications()
  // enables @babel/preset-env polyfills
  .configureBabel(babelConfig => {
    // Get all available languages:
    const languages = fs.readdirSync('node_modules/prismjs/components')
      .filter(f => f !== 'index.js' && !f.endsWith('.min.js'))
      .map(f => f.slice(6, -3));

    // Configure Prism for bundling:
    babelConfig.plugins.push(["prismjs", { languages, plugins: ['treeview'] }]);
  })
  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = 'entry';
    config.corejs = 3;
  })
;

const prismBinary = Encore.getWebpackConfig();

prismBinary.target = 'node';
prismBinary.plugins = prismBinary.plugins.filter(plugin => {
  // Remove useless web plugins:
  return !['ManifestPlugin', 'AssetsWebpackPlugin'].includes(plugin.constructor.name);
});

module.exports = [
  prismBinary,
];
