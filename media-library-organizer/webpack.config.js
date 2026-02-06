const path = require("path");
const glob = require("glob");
const fs = require("fs");
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const RemoveEmptyScriptsPlugin = require("webpack-remove-empty-scripts");
const TerserPlugin = require("terser-webpack-plugin");

function getEntries() {
  const entries = {};

  // Root SCSS → CSS
  glob.sync("./assets/scss/*.scss").forEach((file) => {
    const name = file
      .replace("./assets/scss/", "assets/css/")
      .replace(".scss", "");
    entries[name] = path.resolve(__dirname, file);
  });

  // Root JS → minified
  glob.sync("./assets/js/*.js").forEach((file) => {
    if (file.includes("-min.js")) return;
    const name = file
      .replace("./assets/js/", "assets/js/min/")
      .replace(".js", "-min");
    entries[name] = path.resolve(__dirname, file);
  });

  glob.sync("./assets/scss/*.scss").forEach((file) => {
    const name = file
      .replace("./_modules/dashboard/scss/", "_modules/dashboard/css/")
      .replace(".scss", "");
    entries[name] = path.resolve(__dirname, file);
  });

  glob.sync("./_modules/dashboard/js/*.js").forEach((file) => {
    if (file.includes("-min.js")) return;
    const name = file
      .replace("./_modules/dashboard/js", "_modules/dashboard/js/min")
      .replace(".js", "-min");
    entries[name] = path.resolve(__dirname, file);
  });

  // Addons
  const addonFolders = fs
    .readdirSync(path.resolve(__dirname, "addons"))
    .filter((f) =>
      fs.statSync(path.join(__dirname, "addons", f)).isDirectory()
    );

  addonFolders.forEach((addon) => {
    // SCSS → CSS
    glob.sync(`./addons/${addon}/assets/scss/*.scss`).forEach((file) => {
      const name = file
        .replace(`./addons/${addon}/`, `addons/${addon}/`)
        .replace("/scss/", "/css/")
        .replace(".scss", "");
      entries[name] = path.resolve(__dirname, file);
    });

    // JS → minified
    glob.sync(`./addons/${addon}/assets/js/*.js`).forEach((file) => {
      if (file.includes("-min.js")) return;
      const name = file
        .replace(`./addons/${addon}/`, `addons/${addon}/`)
        .replace("/js/", "/js/min/")
        .replace(".js", "-min");
      entries[name] = path.resolve(__dirname, file);
    });
  });

  entries['./assets/build/settings'] = path.resolve(__dirname, './assets/src/settings.js');
  entries['./assets/build/sidebar'] = path.resolve(__dirname, './assets/src/sidebar/sidebar.js');

  return entries;
}

module.exports = {
  ...defaultConfig,
  entry: getEntries(),
  output: {
    filename: "[name].js",
    path: path.resolve(__dirname),
    iife: false,
  },
  optimization: {
    ...defaultConfig.optimization,
    usedExports: false, // disable tree-shaking
    minimizer: [
      new TerserPlugin({
        extractComments: false,
        terserOptions: {
          compress: false, // prevent removal of unused functions
          keep_fnames: true, // keep function names as functions are called globally within the other files.
          format: {
            comments: false, // remove all comments
          },
        },
      }),
    ],
  },
  plugins: [
    // Overrides default behavior. Prevents the generation of `.assets.php` files and empty JS files.
    new RemoveEmptyScriptsPlugin(), // Remove empty JS files generated when processing SASS files as separated entries.
    new MiniCssExtractPlugin({
      filename: "[name].css",
    }),
  ],
  resolve: {
    ...defaultConfig.resolve,
    alias: {
      "@": path.resolve(__dirname),
    },
  },
  externals: {
    jquery: "jQuery",
    wp: "wp",
    _: "_",
  },
};
