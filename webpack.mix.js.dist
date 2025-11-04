let mix = require('laravel-mix')
let webpack = require('webpack')
let tailwindcss = require('tailwindcss')
let path = require('path')
let postcssImport = require('postcss-import')
let postcssRtlcss = require('postcss-rtlcss')

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
  .js('resources/js/app.js', 'public')
  .ts('resources/ui/ui.js', 'public/ui.js')
  .vue({ version: 3 })
  // .sourceMaps()
  .extract()
  .setPublicPath('public')
  .postCss('resources/css/app.css', 'public', [
    postcssImport(),
    tailwindcss('tailwind.config.js'),
    postcssRtlcss(),
  ])
  .copy('resources/fonts/', 'public/fonts')
  .alias({ '@': path.join(__dirname, 'resources/js/') })
  .webpackConfig({
    externals: {
      'laravel-nova-ui': 'LaravelNovaUi',
    },
    plugins: [
      new webpack.DefinePlugin({
        // Temporary fixes: https://github.com/vuejs/vue-cli/pull/7443
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false',
      })
    ],
    resolve: {
      symlinks: false,
      alias: { vue: path.resolve("./node_modules/vue") }
    },
    output: { uniqueName: 'laravel/nova' }
  })
  .options({
    vue: {
      exposeFilename: true,
      compilerOptions: {
        isCustomElement: tag => tag.startsWith('trix-'),
      },
    },
    processCssUrls: false,
  })
  .version()
