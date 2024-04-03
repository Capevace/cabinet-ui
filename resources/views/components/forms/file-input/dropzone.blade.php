<div
    x-data="{
        dragging: false,

        onDrop(event) {
            console.log(event);
        }
    }"
    {{ $attributes->class(['bg-gray-100 h-12 w-full']) }}

    :class="{
        'bg-gray-200': dragging,
        'bg-gray-100': !dragging,
    }"
    @dragover="dragging = true"
    @dragleave="dragging = false"
    @drop="onDrop"
>
</div>
