<template>
    <span
        class="nfi"
        :data-shape="shape"
        :data-size="size"
        :class="{ 'nfi-pulse': indicator.pulse }"
        :style="colorVars(indicator)"
        :title="indicator.tooltip || undefined"
        v-bind="ariaAttrs(indicator)"
    >
        <!-- The pill has no separate mark: the tinted pill is the mark. -->
        <span v-if="shape !== 'pill'" class="nfi-mark" aria-hidden="true" />

        <!-- Icons are a redundant channel for sighted users, never the name. -->
        <Icon
            v-if="indicator.icon"
            :name="indicator.icon.name"
            :type="indicator.icon.type"
            class="nfi-icon"
            aria-hidden="true"
        />

        <span v-if="hasLabel" class="nfi-label">{{ indicator.label }}</span>
    </span>
</template>

<script setup>
import { computed } from "vue";
import { Icon } from "laravel-nova-ui";
import { ariaAttrs, colorVars } from "../composables/useIndicator";

const props = defineProps({
    indicator: { type: Object, required: true },
    shape: { type: String, default: "dot" },
    size: { type: String, default: "md" },
});

const hasLabel = computed(
    () => props.indicator.label !== null && props.indicator.label !== "",
);
</script>
