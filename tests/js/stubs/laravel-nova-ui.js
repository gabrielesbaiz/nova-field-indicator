/**
 * Stand-in for Nova's UI kit, which only exists inside a running Nova app.
 * The bundle keeps it external either way; tests only need the name and type
 * to show up in the rendered markup so they can be asserted on.
 */
export const Icon = {
    props: ["name", "type"],
    template: '<span class="icon" :data-icon="name" :data-type="type" />',
};
