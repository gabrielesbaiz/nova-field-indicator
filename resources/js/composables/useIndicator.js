import { computed, unref } from "vue";

/**
 * Turns the serialized field into everything the template needs.
 *
 * There is no lookup logic here on purpose. PHP resolves labels, colours and
 * icons per value and sends the result, so this only shapes what it receives.
 */
export function useIndicator(field) {
    // unref(), not `field.value ?? field`. The serialized field carries its own
    // `value` key — the resolved attribute, usually a string — so reaching for
    // `.value` to unwrap a possible ref read that instead and every payload
    // resolved to no indicators at all.
    const payload = computed(() => unref(field));

    const indicators = computed(() => {
        if (payload.value.shouldHide) {
            return [];
        }

        return payload.value.indicators ?? [];
    });

    const shape = computed(() => payload.value.shape ?? "dot");
    const size = computed(() => payload.value.size ?? "md");
    const emptyText = computed(() => payload.value.emptyText ?? "—");

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
