let mix = require("laravel-mix");
let NovaExtension = require("laravel-nova-devtool");

mix.extend("nova", new NovaExtension());

mix.setPublicPath("dist")
    .js("resources/js/field.js", "js")
    .vue({ version: 3 })
    .sass("resources/sass/field.scss", "dist/css")
    .nova("gabrielesbaiz/nova-field-map")
    .version();
