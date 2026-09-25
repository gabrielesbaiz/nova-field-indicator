import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import DetailField from "../../resources/js/components/DetailField.vue";
import IndexField from "../../resources/js/components/IndexField.vue";
import { globalStubs, makeField, makeIndicator } from "./setup";

function renderIndex(field = {}) {
    return mount(IndexField, {
        props: { field: makeField(field) },
        global: globalStubs,
    });
}

function renderDetail(field = {}, props = {}) {
    return mount(DetailField, {
        props: { field: makeField(field), ...props },
        global: globalStubs,
    });
}

describe("IndexField", () => {
    it("renders one indicator per entry", () => {
        const wrapper = renderIndex({
            indicators: [
                makeIndicator({ label: "Active" }),
                makeIndicator({ label: "Banned" }),
            ],
        });

        expect(wrapper.findAll(".nfi")).toHaveLength(2);
        expect(wrapper.text()).toContain("Active");
        expect(wrapper.text()).toContain("Banned");
    });

    it("renders nothing but the placeholder when the field is hidden", () => {
        const wrapper = renderIndex({ shouldHide: true, indicators: [] });

        expect(wrapper.find(".nfi").exists()).toBe(false);
        expect(wrapper.find(".nfi-empty").exists()).toBe(true);
    });

    it("ignores stale indicators if shouldHide is set", () => {
        const wrapper = renderIndex({
            shouldHide: true,
            indicators: [makeIndicator()],
        });

        expect(wrapper.find(".nfi").exists()).toBe(false);
    });

    it("keeps the placeholder out of the accessibility tree", () => {
        // 100 rows of "em dash" would be noise, not information.
        const wrapper = renderIndex({ indicators: [] });

        expect(wrapper.find(".nfi-empty").attributes("aria-hidden")).toBe(
            "true",
        );
        expect(wrapper.find(".nfi-empty").text()).toBe("—");
    });

    it("passes the field-level shape and size down", () => {
        const wrapper = renderIndex({ shape: "pill", size: "sm" });

        expect(wrapper.find(".nfi").attributes("data-shape")).toBe("pill");
        expect(wrapper.find(".nfi").attributes("data-size")).toBe("sm");
    });
});

describe("DetailField", () => {
    it("renders inside a PanelItem", () => {
        expect(renderDetail().find(".panel-item").exists()).toBe(true);
    });

    it("forwards the index PanelItem expects", () => {
        // The 2.x component omitted this prop entirely.
        const wrapper = renderDetail({}, { index: 3 });

        expect(
            wrapper.findComponent({ name: "PanelItem" }).props("index"),
        ).toBe(3);
    });

    it("renders the indicator in the value slot", () => {
        expect(renderDetail().find(".panel-item .nfi").exists()).toBe(true);
    });
});
