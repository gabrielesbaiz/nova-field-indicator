/**
 * Globals Nova injects that our components lean on.
 *
 * PanelItem is globally registered by Nova's own require.context sweep, so it
 * is never imported and therefore never resolved in a test without a stub.
 */
export const globalStubs = {
    components: {
        PanelItem: {
            props: ["index", "field"],
            template: '<div class="panel-item"><slot name="value" /></div>',
        },
    },
};

/** A serialized field, matching what jsonSerialize() actually emits. */
export function makeField(overrides = {}) {
    return {
        shape: "dot",
        size: "md",
        shouldHide: false,
        emptyText: "—",
        indicators: [makeIndicator()],
        ...overrides,
    };
}

export function makeIndicator(overrides = {}) {
    return {
        value: "active",
        label: "Active",
        ariaLabel: "Active",
        tooltip: null,
        color: {
            token: "success",
            light: "rgba(var(--colors-green-500))",
            dark: "rgba(var(--colors-green-400))",
            soft: "color-mix(in srgb, rgba(var(--colors-green-500)) 15%, transparent)",
        },
        icon: null,
        pulse: false,
        ...overrides,
    };
}
