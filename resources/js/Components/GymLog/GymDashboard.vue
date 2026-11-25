<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t } = useI18n();
const emit = defineEmits(['navigate-to-scheme']);

const schemes = ref([]);
const stats = ref({
    total_workouts: 0,
    workouts_this_month: 0
});
const loading = ref(true);

// Load schemes and stats
const loadData = async () => {
    loading.value = true;
    try {
        const [schemesResponse, statsResponse] = await Promise.all([
            axios.get('/api/training-schemes'),
            axios.get('/api/gym-logs/stats')
        ]);
        schemes.value = schemesResponse.data;
        stats.value = statsResponse.data;
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
    } finally {
        loading.value = false;
    }
};

const navigateToScheme = (schemeId) => {
    emit('navigate-to-scheme', schemeId);
};

defineExpose({ loadData });

onMounted(() => {
    loadData();
});
</script>

<template>
    <div class="space-y-6 sm:space-y-8">
        <!-- Stats Section -->
        <div class="grid grid-rows-1 sm:grid-rows-2 gap-4">
            <!-- Total Workouts Card -->
            <div class="bg-white/5 border border-white/10 rounded-lg p-6 text-center backdrop-blur-md">
                <p class="text-white/50 font-mono text-xs uppercase tracking-wider mb-2">
                    {{ $t('pages.gym_log.dashboard.total_workouts') }}
                </p>
                <p class="text-white font-mono text-5xl sm:text-6xl font-bold">
                    {{ stats.total_workouts }}
                </p>
            </div>

            <!-- This Month Card -->
            <div class="bg-white/5 border border-white/10 rounded-lg p-6 text-center backdrop-blur-md">
                <p class="text-white/50 font-mono text-xs uppercase tracking-wider mb-2">
                    {{ $t('pages.gym_log.dashboard.workouts_this_month') }}
                </p>
                <p class="text-white font-mono text-5xl sm:text-6xl font-bold">
                    {{ stats.workouts_this_month }}
                </p>
            </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-white/10"></div>

        <!-- Training Schemes Section -->
        <div>
            <h3 class="text-white font-mono text-lg sm:text-xl uppercase tracking-wider mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined">{{ $t('pages.gym_log.dashboard.your_schemes') }}</span>
            </h3>

            <div v-if="loading" class="text-white/50 font-mono text-sm">
                {{ $t('pages.gym_log.dashboard.loading') }}
            </div>

            <div v-else-if="schemes.length === 0" class="bg-white/5 border border-white/10 rounded-lg p-8 text-center backdrop-blur-md">
                <span class="material-symbols-outlined text-5xl text-white/30 mb-4">
                    assignment
                </span>
                <p class="text-white/50 font-mono text-sm">
                    {{ $t('pages.gym_log.dashboard.no_schemes') }}
                </p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 backdrop-blur-md">
                <button
                    v-for="scheme in schemes"
                    :key="scheme.id"
                    @click="navigateToScheme(scheme.id)"
                    class="bg-white/5 border border-white/10 rounded-lg p-6 hover:bg-white/10 hover:border-white/20 hover:cursor-pointer transition-all active:scale-95 text-left group"
                >
                    <div class="flex items-start justify-between mb-3">
                        <span class="text-white font-mono text-base sm:text-lg font-bold mb-2">
                            {{ scheme.name }}
                        </span>
                        <span class="text-2xl text-white/50 group-hover:text-white/70 transition-colors">
                            →
                        </span>
                    </div>
                    <h4 class="text-white font-mono text-base sm:text-lg font-bold mb-2">
                        
                    </h4>
                    <p class="text-white/80 font-mono text-xs">
                        {{ scheme.exercises?.length || 0 }} {{ $t('pages.gym_log.dashboard.exercises') }}
                    </p>
                </button>
            </div>
        </div>
    </div>
</template>
