<template>
    <div class="nfi-group">
        <Indicator
            v-for="(indicator, i) in indicators"
            :key="i"
            :indicator="indicator"
            :shape="shape"
            :size="size"
        />

        <!-- aria-hidden so a 100-row index is not announced as 100 em dashes. -->
        <span v-if="!hasIndicators" class="nfi-empty" aria-hidden="true">
            {{ emptyText }}
        </span>
    </div>
</template>

<script setup>
import Indicator from "./Indicator.vue";
import { useIndicator } from "../composables/useIndicator";

const props = defineProps({
    resourceName: { type: String, default: null },
    field: { type: Object, required: true },
});

const { indicators, shape, size, emptyText, hasIndicators } = useIndicator(
    props.field,
);
</script>
