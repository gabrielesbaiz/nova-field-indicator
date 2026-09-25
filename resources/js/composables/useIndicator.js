import { computed } from "vue";

/**
 * Turns the serialized field into everything the template needs.
 *
 * There is no lookup logic here on purpose. PHP resolves labels, colours and
 * icons per value and sends the result, so this only shapes what it receives.
 */
export function useIndicator(field) {
    const indicators = computed(() => {
        const value = field.value ?? field;

        if (value.shouldHide) {
            return [];
        }

        return value.indicators ?? [];
    });

    const shape = computed(() => (field.value ?? field).shape ?? "dot");
    const size = computed(() => (field.value ?? field).size ?? "md");
    const emptyText = computed(() => (field.value ?? field).emptyText ?? "—");

    /** Whether anything at all should render. */
    const hasIndicators = computed(() => indicators.value.length > 0);

    return { indicators, shape, size, emptyText, hasIndicators };
}

/**
 * The inline custom properties carrying one indicator's colour.
 *
 * Binding an object rather than building a style string matters: Vue applies
 * these through CSSStyleDeclaration.setProperty(), which parses the value as
 * CSS and drops anything unparseable. 2.x concatenated the colour into a
 * `background:${color};` string instead.
 */
export function colorVars(indicator) {
    return {
        "--nfi-color": indicator.color.light,
        "--nfi-color-dark": indicator.color.dark,
        "--nfi-soft": indicator.color.soft,
    };
}

/**
 * Accessibility attributes for one mark.
 *
 * Two distinct cases. With a visible label the mark is decoration and the text
 * is the accessible name — adding an aria-label there would override the
 * visible text and break voice control. With no visible label the colour alone
 * carries meaning, so the element becomes a named graphic.
 */
export function ariaAttrs(indicator) {
    if (indicator.label !== null && indicator.label !== "") {
        return {};
    }

    return { role: "img", "aria-label": indicator.ariaLabel };
}
