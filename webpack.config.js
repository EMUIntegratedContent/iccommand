// https://symfony.com/doc/current/frontend/encore/simple-example.html
const Encore = require('@symfony/webpack-encore')
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
    .enableSassLoader(function(sassOptions) {}, {
       resolveUrlLoader: false
    })
    // allow legacy applications to use $/jQuery as a global variable
    .autoProvidejQuery()
    .enableSourceMaps(!Encore.isProduction())
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
