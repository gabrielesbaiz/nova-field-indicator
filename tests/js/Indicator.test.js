import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import Indicator from "../../resources/js/components/Indicator.vue";
import { makeIndicator } from "./setup";

function render(indicator = {}, props = {}) {
    return mount(Indicator, {
        props: { indicator: makeIndicator(indicator), ...props },
    });
}

describe("Indicator", () => {
    it("binds both colour custom properties from the payload", () => {
        const el = render().element;

        expect(el.style.getPropertyValue("--nfi-color")).toBe(
            "rgba(var(--colors-green-500))",
        );
        expect(el.style.getPropertyValue("--nfi-color-dark")).toBe(
            "rgba(var(--colors-green-400))",
        );
    });

    it("puts shape and size on the root as data attributes", () => {
        const wrapper = render({}, { shape: "ring", size: "lg" });

        expect(wrapper.attributes("data-shape")).toBe("ring");
        expect(wrapper.attributes("data-size")).toBe("lg");
    });

    it("renders a mark for every shape except the pill", () => {
        expect(render({}, { shape: "dot" }).find(".nfi-mark").exists()).toBe(
            true,
        );
        expect(render({}, { shape: "ring" }).find(".nfi-mark").exists()).toBe(
            true,
        );
        expect(render({}, { shape: "square" }).find(".nfi-mark").exists()).toBe(
            true,
        );
        // The tinted pill *is* the mark.
        expect(render({}, { shape: "pill" }).find(".nfi-mark").exists()).toBe(
            false,
        );
    });

    it("treats the mark as decoration when a label is visible", () => {
        const wrapper = render({ label: "Active" });

        expect(wrapper.find(".nfi-mark").attributes("aria-hidden")).toBe(
            "true",
        );
        // An aria-label here would override the visible text and break voice
        // control ("click Active").
        expect(wrapper.attributes("role")).toBeUndefined();
        expect(wrapper.attributes("aria-label")).toBeUndefined();
    });

    it("names the mark when colour alone carries the meaning", () => {
        const wrapper = render({ label: null, ariaLabel: "Status: Active" });

        expect(wrapper.attributes("role")).toBe("img");
        expect(wrapper.attributes("aria-label")).toBe("Status: Active");
        expect(wrapper.find(".nfi-label").exists()).toBe(false);
    });

    it("renders the icon only when one was resolved, and hides it from AT", () => {
        expect(render().find(".icon").exists()).toBe(false);

        const wrapper = render({
            icon: { name: "check-circle", type: "solid" },
        });
        const icon = wrapper.find(".icon");

        expect(icon.attributes("data-icon")).toBe("check-circle");
        expect(icon.attributes("data-type")).toBe("solid");
        expect(icon.attributes("aria-hidden")).toBe("true");
    });

    it("toggles the pulse class", () => {
        expect(render({ pulse: true }).classes()).toContain("nfi-pulse");
        expect(render({ pulse: false }).classes()).not.toContain("nfi-pulse");
    });

    it("sets a title only when a tooltip was resolved", () => {
        expect(render().attributes("title")).toBeUndefined();
        expect(render({ tooltip: "Since May" }).attributes("title")).toBe(
            "Since May",
        );
    });

    /*
     * Regressions from 2.x: whitespace-no-wrap was Tailwind v1 syntax and did
     * nothing under Nova 5's Tailwind 3, and a static indicator-grey class was
     * always emitted alongside the real colour class, only losing to it by
     * accident of stylesheet source order.
     */
    it("emits no dead Tailwind v1 or colour classes", () => {
        const html = render().html();

        expect(html).not.toContain("whitespace-no-wrap");
        expect(html).not.toContain("indicator-grey");
        expect(html).not.toContain("indicator-");
    });
});
