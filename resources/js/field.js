import "../css/field.css";

import DetailField from "./components/DetailField.vue";
import IndexField from "./components/IndexField.vue";

Nova.booting((app) => {
    // PascalCase on purpose: Nova.hasComponent() capitalizes and camelizes the
    // name before looking it up, so a kebab registration is invisible to it,
    // while Vue still resolves <component is="index-nova-field-indicator" />.
    app.component("IndexNovaFieldIndicator", IndexField);
    app.component("DetailNovaFieldIndicator", DetailField);
});
