const path = require("path");

let mix = require("laravel-mix");

require("./nova.mix");

mix.setPublicPath("dist")
    .js("resources/js/field.js", "js")
    .vue({ version: 3 })
    .sass('resources/sass/field.scss', 'dist/css')
    .nova("gabrielesbaiz/nova-field-map");

mix.webpackConfig({
    resolve: {
        alias: {
            "laravel-nova": path.resolve(
                __dirname,
                "vendor/laravel/nova/resources/js/mixins/packages.js"
            ),
        },
    },
});
