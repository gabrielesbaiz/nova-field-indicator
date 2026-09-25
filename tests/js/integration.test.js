import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import payloads from "./fixtures/payloads.json";
import IndexField from "../../resources/js/components/IndexField.vue";
import { globalStubs } from "./setup";

/*
 * Mounts the components against the real output of jsonSerialize(), exported by
 * tests/PayloadFixtureTest.php. Every other JS test uses a hand-written field
 * object, which cannot catch the two halves drifting apart — this one can.
 */
const render = (name) =>
    mount(IndexField, { props: { field: payloads[name] }, global: globalStubs });

describe("against real PHP payloads", () => {
    it("renders the enum payload with its resolved colour and icon", () => {
        const w = render("enum");

        expect(w.text()).toContain("Active");
        expect(w.find(".nfi").element.style.getPropertyValue("--nfi-color")).toBe(
            "rgba(var(--colors-green-500))",
        );
        expect(w.find(".icon").attributes("data-icon")).toBe("check-circle");
    });

    it("carries shape and size through from PHP", () => {
        const w = render("pill");

        expect(w.find(".nfi").attributes("data-shape")).toBe("pill");
        expect(w.find(".nfi").attributes("data-size")).toBe("lg");
        // The pill is its own mark, so no separate glyph is rendered.
        expect(w.find(".nfi-mark").exists()).toBe(false);
    });

    it("renders one mark per entry for an array attribute", () => {
        expect(render("multi").findAll(".nfi")).toHaveLength(2);
    });

    it("keeps the mark accessible when PHP omits the label", () => {
        const w = render("nolabel");
        const nfi = w.find(".nfi");

        expect(w.find(".nfi-label").exists()).toBe(false);
        expect(nfi.attributes("role")).toBe("img");
        expect(nfi.attributes("aria-label")).toBe("Status: Active");
    });

    it("renders only the placeholder for a hidden field", () => {
        const w = render("hidden");

        expect(w.find(".nfi").exists()).toBe(false);
        expect(w.find(".nfi-empty").text()).toBe(payloads.hidden.emptyText);
    });
});
