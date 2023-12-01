<div
    x-data="{
        get positionTop() {
            if (window.innerHeight < this.$store.finderContextMenu.position.top + this.$el.offsetHeight) {
                return (window.innerHeight - this.$el.offsetHeight) + 'px';
            } else {
                return this.$store.finderContextMenu.position.top + 'px';
            }
        },
        get positionLeft() {
            if (window.innerWidth < this.$store.finderContextMenu.position.left + this.$el.offsetWidth) {
                return (this.$store.finderContextMenu.position.left - this.$el.offsetWidth) + 'px';
            } else {
                return this.$store.finderContextMenu.position.left + 'px';
            }
        }
    }"
    x-show="$store.finderContextMenu.visible"
    @click.away="$store.finderContextMenu.close()"
    class="z-50 min-w-[8rem] text-gray-800 dark:text-gray-300 rounded-lg border border-gray-200/70 dark:border-gray-700/70 bg-white dark:bg-gray-800 text-sm fixed p-1 shadow-md w-64"
    :style="$store.finderContextMenu.visible
        ? `top: ${positionTop}; left: ${positionLeft};`
        : 'display: none;'
    "
>
    <ul class="flex flex-col gap-0.5">
        <template
            x-for="menu of $store.finderContextMenu.items"
        >
            <li :key="menu.label">
                <template x-if="menu.seperator">
                    <div class="h-px my-1 -mx-1 bg-gray-200"></div>
                </template>
                <template x-if="!menu.seperator && menu.url === null">
                    <button
                        type="button"
                        @click="
                            const actionName = menu.actionName;
                            const data = $store.finderContextMenu.data;

                            $store.finderContextMenu.close();

                            $wire.call('mountAction', actionName, data ?? {});
                        "
                        class="w-full relative text-left flex cursor-pointer select-none group items-center rounded-md space-x-2 px-2.5 py-1.5 hover:bg-primary-600 hover:text-white outline-none data-[disabled]:opacity-50 data-[disabled]:pointer-events-none"
                    >
                        <figure
                            class="w-5 h-5 p-px"
                            x-html="menu.icon"
                        ></figure>
                        <div x-text="menu.label"></div>
                    </button>
                </template>
                <template x-if="!menu.seperator && menu.url !== null">
                    <a
                        :href="menu.url"
                        :target="menu.shouldOpenInNewTab ? '_blank' : '_self'"
                        class="w-full relative text-left flex cursor-pointer select-none group items-center rounded-md space-x-2 px-2.5 py-1.5 hover:bg-primary-600 hover:text-white outline-none data-[disabled]:opacity-50 data-[disabled]:pointer-events-none"
                    >
                        <figure
                            class="w-5 h-5 p-px"
                            x-html="menu.icon"
                        ></figure>
                        <div x-text="menu.label"></div>
                    </a>
                </template>
            </li>
        </template>
    </ul>
</div>
