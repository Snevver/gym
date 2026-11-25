<script setup>
import { ref, defineProps, defineEmits } from "vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
    activeSection: {
        type: String,
        required: true
    },
    sections: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['update:activeSection', 'add-scheme', 'reorder']);

let draggingId = null;

const onDragStart = (e, section) => {
    if (section.type !== 'scheme') return;
    draggingId = section.id;
    e.dataTransfer.effectAllowed = 'move';
    try {
        e.dataTransfer.setData('text/plain', String(section.id));
    } catch (err) {
        // ignore
    }
    console.log('dragstart', section.id);
};

const onDragOver = (e, section) => {
    if (section.type !== 'scheme') return;
    // mark as drag-over target
    try { e.dataTransfer.dropEffect = 'move'; } catch (err) {}
    const el = e.currentTarget;
    if (el && el.classList) {
        el.classList.add('drag-over');
    }
    console.log('dragover', section.id);
};

const onDrop = (e, section, sections) => {
    e.preventDefault();
    if (!draggingId) return;
    console.log('drop on', section.id, 'dragging', draggingId);
    // remove visual markers
    try { e.currentTarget.classList.remove('drag-over'); } catch (err) {}
    const schemeSections = sections.filter(s => s.type === 'scheme');
    const draggedIndex = schemeSections.findIndex(s => s.id === draggingId);
    const targetIndex = schemeSections.findIndex(s => s.id === section.id);
    if (draggedIndex === -1 || targetIndex === -1) return;

    // produce new ordered array
    const newOrder = schemeSections.slice();
    const [moved] = newOrder.splice(draggedIndex, 1);
    newOrder.splice(targetIndex, 0, moved);

    // map to ids (strip 'scheme-' prefix if present)
    const ids = newOrder.map(s => {
        const parts = String(s.id).split('-');
        return Number(parts[1] ?? parts[0]);
    });

    emit('reorder', ids);
    draggingId = null;
};

const onDragLeave = (e, section) => {
    try { e.currentTarget.classList.remove('drag-over'); } catch (err) {}
    console.log('dragleave', section.id);
};

const isMobileMenuOpen = ref(false);

const selectSection = (sectionId) => {
    emit('update:activeSection', sectionId);
    isMobileMenuOpen.value = false;
};

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};
</script>

<template>
    <div>
        <!-- Mobile Toggle Button (shows only when menu is closed) -->
        <button
            v-if="!isMobileMenuOpen"
            @click="toggleMobileMenu"
            class="lg:hidden fixed bottom-6 right-6 z-50 p-3 bg-white/5 text-white border border-white/30 hover:bg-white/8 transition-all duration-150 active:scale-95"
            aria-label="Open navigation"
        >
            <span class="material-symbols-outlined text-xl uppercase">menu</span>
        </button>

        <!-- Overlay for mobile -->
        <div
            v-if="isMobileMenuOpen"
            @click="toggleMobileMenu"
            class="lg:hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-40"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed lg:relative lg:w-64 w-72 max-w-[85vw] shrink-0',
                'bg-black/95 lg:bg-transparent backdrop-blur-lg lg:backdrop-blur-none',
                'border-r border-white/10 lg:border-0 p-6 lg:p-0',
                'transition-transform duration-300 ease-in-out z-40',
                'top-0 lg:top-auto left-0',
                'h-screen lg:h-auto',
                'overflow-y-auto lg:overflow-visible',
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]"
        >
            <!-- Mobile Header (no close button here) -->
            <div class="lg:hidden mb-6 pb-4 border-b border-white/10">
                <h3 class="text-white font-mono text-sm uppercase tracking-wider">
                    Navigation
                </h3>
            </div>

            <nav class="space-y-2">
                <!-- Dashboard and non-scheme sections first -->
                <button
                    v-for="section in sections.filter(s => s.type === 'section' && s.id === 'dashboard')"
                    :key="section.id"
                    @click="selectSection(section.id)"
                    :class="[
                        'w-full flex items-center gap-3 px-4 py-3 font-mono text-xs uppercase tracking-wider transition-all hover:cursor-pointer backdrop-blur-md',
                        'border border-white/10 rounded',
                        activeSection === section.id
                            ? 'bg-white text-black font-bold shadow-[0_0_15px_rgba(255,255,255,0.2)]'
                            : 'bg-white/5 text-white/70 hover:bg-white/10 hover:text-white active:scale-95'
                    ]"
                >
                    <span class="truncate">{{ $t(section.label) }}</span>
                </button>

                <!-- Separator -->
                <div class="py-4">
                    <div class="border-t border-white/40"></div>
                </div>
                
                <!-- Scheme sections (draggable) -->
                <div v-for="section in sections.filter(s => s.type === 'scheme')" :key="section.id">
                    <div
                        role="button"
                        tabindex="0"
                        @dragover.prevent="(e) => onDragOver(e, section)"
                        @drop="(e) => onDrop(e, section, sections)"
                        @click="selectSection(section.id)"
                        :class="[
                            'w-full flex items-center gap-3 px-4 py-3 font-mono text-xs uppercase tracking-wider transition-all backdrop-blur-md',
                            'border border-white/10 rounded',
                            'hover:cursor-pointer',
                            activeSection === section.id
                                ? 'bg-white text-black font-bold shadow-[0_0_15px_rgba(255,255,255,0.2)]'
                                : 'bg-white/5 text-white/70 hover:bg-white/10 hover:text-white active:scale-95'
                        ]"
                    >
                        <span class="truncate">{{ section.label }}</span>
                        <span class="material-symbols-outlined text-sm ml-auto opacity-50 drag-handle" 
                              draggable="true"
                              @dragstart="(e) => onDragStart(e, section)"
                              @dragenter="(e) => onDragOver(e, section)"
                              @dragleave="(e) => onDragLeave(e, section)"
                              ></span>
                    </div>
                </div>
                
                <!-- Add Scheme Button -->
                <button
                    @click="emit('add-scheme')"
                    class="w-full flex items-center justify-center gap-2 px-4 py-3 font-mono text-xs uppercase tracking-wider transition-all hover:cursor-pointer border border-white/20 border-dashed rounded bg-white/5 text-white/70 hover:bg-white/10 hover:text-white hover:border-white/30 backdrop-blur-md"
                >
                    <span class="material-symbols-outlined text-base">{{ $t('pages.gym_log.schemes.new_scheme') }}</span>
                </button>
                
                <!-- Separator -->
                <div class="py-4">
                    <div class="border-t border-white/40"></div>
                </div>
                
                <!-- Calendar -->
                <button
                    v-for="section in sections.filter(s => s.type === 'section' && s.id !== 'dashboard')"
                    :key="section.id"
                    @click="selectSection(section.id)"
                    :class="[
                        'w-full flex items-center gap-3 px-4 py-3 font-mono text-xs uppercase tracking-wider transition-all hover:cursor-pointer backdrop-blur-md',
                        'border border-white/10 rounded',
                        activeSection === section.id
                            ? 'bg-white text-black font-bold shadow-[0_0_15px_rgba(255,255,255,0.2)]'
                            : 'bg-white/5 text-white/70 hover:bg-white/10 hover:text-white active:scale-95'
                    ]"
                >
                    <span class="truncate">{{ $t(section.label) }}</span>
                </button>
            </nav>

            <!-- Mobile close button at bottom of menu -->
            <div v-if="isMobileMenuOpen" class="lg:hidden mt-auto pt-4 border-t border-white/10">
                <div class="p-4">
                    <button
                        @click="toggleMobileMenu"
                        class="w-full px-4 py-3 bg-white/5 hover:bg-white/10 text-white rounded-sm border border-white/10 transition-all duration-150 uppercase"
                        aria-label="Close navigation"
                    >
                        {{ $t('common.close') }}
                    </button>
                </div>
            </div>
        </aside>
    </div>
</template>

<style>
.drag-over {
    background-color: rgba(255,255,255,0.04) !important;
}
</style>
