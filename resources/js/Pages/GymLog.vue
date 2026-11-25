<script setup>
import AppLayout from "../Layouts/AppLayout.vue";
import GymDashboard from "../Components/GymLog/GymDashboard.vue";
import GymCalendar from "../Components/GymLog/GymCalendar.vue";
import GymLogSidebar from "../Components/GymLog/GymLogSidebar.vue";
import SingleScheme from "../Components/GymLog/SingleScheme.vue";
import ConfirmModal from "../Components/Common/ConfirmModal.vue";
import { ref, onMounted, computed, nextTick, watch } from "vue";
import { useI18n } from "vue-i18n";
import axios from "axios";

const { t } = useI18n();

// Check current route to determine initial section (preserve deep links)
const currentPath = window.location.pathname;
let initialSection = 'dashboard';
// /scheme/:id should open the selected scheme
const schemeMatch = currentPath.match(/^\/scheme\/(\d+)(?:\/.*)?$/);
if (schemeMatch) {
    initialSection = `scheme-${schemeMatch[1]}`;
} else if (currentPath === '/calendar') {
    initialSection = 'calendar';
}

const activeSection = ref(initialSection);
const schemes = ref([]);
const isAddingScheme = ref(false);
const newSchemeName = ref('');
const schemeNameInput = ref(null);
const dashboardRef = ref(null);

// Load schemes
const loadSchemes = async () => {
    try {
        const response = await axios.get('/api/training-schemes');
        schemes.value = response.data;
    } catch (error) {
        console.error('Failed to load schemes:', error);
    }
};

const reorderSchemes = async (orderedIds) => {
    // optimistic local reorder so UI updates immediately
    const idSet = new Set(orderedIds.map(Number));
    const map = new Map(schemes.value.map(s => [s.id, s]));
    const newSchemes = [];
    for (const id of orderedIds) {
        const n = map.get(Number(id));
        if (n) newSchemes.push(n);
    }
    // append any missing schemes at the end
    for (const s of schemes.value) {
        if (!idSet.has(s.id)) newSchemes.push(s);
    }
    schemes.value = newSchemes;

    try {
        await axios.post('/api/training-schemes/reorder', { ordered_ids: orderedIds });
        // server authoritative refresh
        await loadSchemes();
    } catch (e) {
        console.error('Failed to reorder schemes', e);
        // fallback: reload from server to restore correct order
        await loadSchemes();
    }
};

// Create new scheme
const createScheme = async () => {
    if (!newSchemeName.value.trim()) return;
    
    try {
        const response = await axios.post('/api/training-schemes', {
            name: newSchemeName.value
        });
        newSchemeName.value = '';
        isAddingScheme.value = false;
        await loadSchemes();
        // Switch to the newly created scheme
        activeSection.value = `scheme-${response.data.id}`;
    } catch (error) {
        console.error('Failed to create scheme:', error);
    }
};

const openAddScheme = () => {
    isAddingScheme.value = true;
    nextTick(() => {
        schemeNameInput.value?.focus();
    });
};

const handleSchemeDeleted = async (schemeId) => {
    await loadSchemes();
    // Switch back to dashboard
    activeSection.value = 'dashboard';
    window.history.pushState({}, '', '/');
};

const handleSchemeUpdated = async () => {
    await loadSchemes();
};

const navigateToDashboard = () => {
    activeSection.value = 'dashboard';
    window.history.pushState({}, '', '/');
};

const navigateToCalendar = () => {
    activeSection.value = 'calendar';
    window.history.pushState({}, '', '/calendar');
};

const navigateToScheme = (schemeId) => {
    activeSection.value = `scheme-${schemeId}`;
    window.history.pushState({}, '', `/scheme/${schemeId}`);
};

// Watch for active section changes to update URL
watch(activeSection, (newSection) => {
    if (newSection === 'dashboard') {
        window.history.pushState({}, '', '/');
    } else if (newSection === 'calendar') {
        window.history.pushState({}, '', '/calendar');
    } else if (newSection.startsWith('scheme-')) {
        const schemeId = newSection.replace('scheme-', '');
        window.history.pushState({}, '', `/scheme/${schemeId}`);
    }
});

// Computed property for sidebar sections
const sidebarSections = computed(() => {
    const sections = [];
    
    // Add dashboard first
    sections.push(
        { id: 'dashboard', label: 'pages.gym_log.sidebar.dashboard', type: 'section' }
    );
    
    // Add each scheme as a section
    schemes.value.forEach(scheme => {
        sections.push({
            id: `scheme-${scheme.id}`,
            label: scheme.name,
            type: 'scheme',
            schemeId: scheme.id
        });
    });
    
    // Add separator and calendar at the end
    sections.push(
        { id: 'calendar', label: 'pages.gym_log.sidebar.calendar', type: 'section' },
    );
    
    return sections;
});

const selectedSchemeId = computed(() => {
    if (activeSection.value.startsWith('scheme-')) {
        const schemeId = Number.parseInt(activeSection.value.replace('scheme-', ''), 10);
        return Number.isNaN(schemeId) ? null : schemeId;
    }
    return null;
});

const refreshDashboardStats = () => {
    dashboardRef.value?.loadData?.();
};

onMounted(() => {
    loadSchemes();
});
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2
                    class="text-white font-mono text-lg sm:text-xl md:text-2xl tracking-[0.2em] sm:tracking-[0.3em] uppercase opacity-90"
                >
                    [{{ $t('pages.gym_log.title') }}]
                </h2>
            </div>
        </template>

        <template #body>
            <div class="flex flex-col lg:flex-row gap-4 sm:gap-6 lg:gap-8 max-w-7xl mx-auto p-4 sm:p-6 md:p-8 lg:p-12">
                <!-- Sidebar Component -->
                <GymLogSidebar
                    :active-section="activeSection"
                    :sections="sidebarSections"
                    @update:active-section="activeSection = $event"
                    @add-scheme="openAddScheme"
                    @reorder="reorderSchemes"
                />

                <!-- Main Content -->
                <main class="flex-1 w-full lg:w-auto min-w-0">
                    <!-- Dashboard Section -->
                    <GymDashboard
                        ref="dashboardRef"
                        v-show="activeSection === 'dashboard'"
                        @navigate-to-scheme="navigateToScheme"
                    />
                    
                    <!-- Calendar Section -->
                    <GymCalendar
                        v-if="activeSection === 'calendar'"
                        @changed="refreshDashboardStats"
                    />
                    
                    <!-- Single Scheme -->
                    <SingleScheme 
                        v-if="selectedSchemeId !== null"
                        :scheme-id="selectedSchemeId"
                        @scheme-deleted="handleSchemeDeleted"
                        @scheme-updated="handleSchemeUpdated"
                    />
                </main>
            </div>

            <!-- Add Scheme Modal -->
            <div v-if="isAddingScheme" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6">
                <div class="bg-black border border-white/20 rounded-lg p-4 sm:p-6 max-w-md w-full">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-white font-mono text-xs sm:text-sm uppercase tracking-wider">
                            {{ $t('pages.gym_log.schemes.create_scheme') }}
                        </h4>
                        <button @click="isAddingScheme = false; newSchemeName = ''" class="text-white/50 hover:text-white hover:cursor-pointer">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <input
                            v-model="newSchemeName"
                            type="text"
                            :placeholder="$t('pages.gym_log.schemes.scheme_name_placeholder')"
                            class="w-full bg-white/10 border border-white/20 text-white font-mono text-sm px-4 py-3 rounded focus:outline-none focus:border-white/40"
                            @keyup.enter="createScheme"
                            @keyup.escape="isAddingScheme = false"
                            ref="schemeNameInput"
                        />
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button
                                @click="createScheme"
                                :disabled="!newSchemeName.trim()"
                                class="flex-1 px-4 py-3 bg-white text-black font-mono text-xs uppercase hover:bg-white/90 hover:cursor-pointer transition-colors rounded disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ $t('pages.gym_log.schemes.create') }}
                            </button>
                            <button
                                @click="isAddingScheme = false; newSchemeName = ''"
                                class="flex-1 px-4 py-3 bg-white/10 border border-white/20 text-white font-mono text-xs uppercase hover:bg-white/20 hover:cursor-pointer transition-colors rounded"
                            >
                                {{ $t('pages.gym_log.schemes.cancel') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
