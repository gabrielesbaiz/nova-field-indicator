<template>
    <!-- index is passed through: Nova 5's PanelItem expects it, and the 2.x
         component omitted it. -->
    <PanelItem :index="index" :field="field">
        <template #value>
            <div class="nfi-group">
                <Indicator
                    v-for="(indicator, i) in indicators"
                    :key="i"
                    :indicator="indicator"
                    :shape="shape"
                    :size="size"
                />

                <span
                    v-if="!hasIndicators"
                    class="nfi-empty"
                    aria-hidden="true"
                >
                    {{ emptyText }}
                </span>
            </div>
        </template>
    </PanelItem>
</template>

<script setup>
import Indicator from "./Indicator.vue";
import { useIndicator } from "../composables/useIndicator";

const props = defineProps({
    index: { type: Number, default: 0 },
    resource: { type: Object, default: null },
    resourceName: { type: String, default: null },
    resourceId: { type: [Number, String], default: null },
    field: { type: Object, required: true },
});

const { indicators, shape, size, emptyText, hasIndicators } = useIndicator(
    props.field,
);
</script>
