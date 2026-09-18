// https://symfony.com/doc/current/frontend/encore/simple-example.html
// @symfony/webpack-encore v7+ is an ES module; under CommonJS require() the
// Encore instance is exposed on the interop `default` export.
const Encore = require('@symfony/webpack-encore').default
const path = require('path')
const webpack = require('webpack')

Encore
    .enableSingleRuntimeChunk()
    // the project directory where all compiled assets will be stored
    .setOutputPath('public/build/')
    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')
    // will create public/build/app.js and public/build/app.css
    .addEntry('app', './assets/js/app.js')
    // allow sass/scss files to be processed
    .enableSassLoader(function(options) {
        // Bootstrap 4 and Font Awesome 4 SCSS trip Dart Sass 2.0 deprecations
        // (mixed-decls, / division, abs() percent). quietDeps silences warnings
        // originating from @imported dependencies (node_modules) while keeping
        // warnings for our own SCSS.
        options.sassOptions = { quietDeps: true }
    }, {
       resolveUrlLoader: false
    })
    // allow legacy applications to use $/jQuery as a global variable
    .autoProvidejQuery()
    .enableSourceMaps(!Encore.isProduction())
    // encore 7 no longer minifies CSS by default and ships no bundled
    // minifier; opt in explicitly and use cssnano (encore 6's old default)
    .configureCssMinimizerPlugin((options, MinimizerPlugin) => {
        options.minify = MinimizerPlugin.cssnanoMinify
    })
    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()
    // show OS notifications when builds finish/fail
    .enableBuildNotifications()
    // create hashed filenames (e.g. app.abc123.css)
    // .enableVersioning()
    // enable vuejs
    .enableVueLoader(() => {}, {
        version: 3,
        runtimeCompilerBuild: true,
    })
    .addAliases({
        '@': path.resolve('assets/js'),
        vue$: 'vue/dist/vue.esm-bundler',
    })
    .addPlugin(
        new webpack.DefinePlugin({
                __VUE_OPTIONS_API__: true, // Allows the old Vue2 style (options API)
                __VUE_PROD_DEVTOOLS__: true,
            })
    )

// export the final configuration
module.exports = Encore.getWebpackConfig()
