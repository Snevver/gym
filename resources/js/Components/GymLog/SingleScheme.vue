<script setup>
import { ref, onMounted, nextTick, watch, computed } from "vue";
import { useI18n } from "vue-i18n";
import axios from "axios";
import ExerciseCard from "./ExerciseCard.vue";
import ConfirmModal from "../Common/ConfirmModal.vue";

const { t } = useI18n();

const props = defineProps({
    schemeId: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['scheme-deleted', 'scheme-updated']);

const scheme = ref(null);
const allExercises = ref([]);
const isAddingExercise = ref(false);
const newExerciseName = ref('');
const newExerciseWeight = ref(0);
const showExerciseSelector = ref(false);
const exerciseNameInput = ref(null);
const showDeleteConfirm = ref(false);
const isCollapsed = ref(false);

// Load scheme data
const loadScheme = async () => {
    try {
        const response = await axios.get('/api/training-schemes');
        const schemes = response.data;
        scheme.value = schemes.find(s => s.id === props.schemeId);
    } catch (error) {
        console.error('Failed to load scheme:', error);
    }
};

const loadExercises = async () => {
    try {
        const response = await axios.get('/api/exercises');
        allExercises.value = response.data;
    } catch (error) {
        console.error('Failed to load exercises:', error);
    }
};

// Exercise management
const createExercise = async () => {
    if (!newExerciseName.value.trim()) return;
    
    try {
        const response = await axios.post('/api/exercises', {
            name: newExerciseName.value,
            current_weight: newExerciseWeight.value
        });
        
        await addExerciseToScheme(response.data.id);
        
        newExerciseName.value = '';
        newExerciseWeight.value = 0;
        isAddingExercise.value = false;
        await loadExercises();
    } catch (error) {
        console.error('Failed to create exercise:', error);
    }
};

const addExerciseToScheme = async (exerciseId) => {
    try {
        await axios.post(`/api/training-schemes/${props.schemeId}/exercises`, {
            exercise_id: exerciseId
        });
        await loadScheme();
        emit('scheme-updated');
    } catch (error) {
        console.error('Failed to add exercise to scheme:', error);
    }
};

const removeExerciseFromScheme = async (exercise) => {
    try {
        await axios.delete(`/api/training-schemes/${props.schemeId}/exercises/${exercise.id}`);
        await loadScheme();
        emit('scheme-updated');
    } catch (error) {
        console.error('Failed to remove exercise from scheme:', error);
    }
};

const updateExerciseWeight = async (exercise, newWeight) => {
    try {
        await axios.post(`/api/exercises/${exercise.id}/weight`, {
            current_weight: newWeight
        });
        await loadScheme();
        await loadExercises();
    } catch (error) {
        console.error('Failed to update exercise weight:', error);
    }
};

const openExerciseSelector = () => {
    showExerciseSelector.value = true;
};

const closeExerciseSelector = () => {
    showExerciseSelector.value = false;
};

const selectExercise = async (exerciseId) => {
    await addExerciseToScheme(exerciseId);
    closeExerciseSelector();
};

const openNewExerciseForm = () => {
    isAddingExercise.value = true;
    showExerciseSelector.value = false;
    nextTick(() => {
        exerciseNameInput.value?.focus();
    });
};

const confirmDelete = () => {
    showDeleteConfirm.value = true;
};

const deleteScheme = async () => {
    try {
        await axios.delete(`/api/training-schemes/${props.schemeId}`);
        emit('scheme-deleted', props.schemeId);
    } catch (error) {
        console.error('Failed to delete scheme:', error);
    } finally {
        showDeleteConfirm.value = false;
    }
};

watch(() => props.schemeId, () => {
    loadScheme();
}, { immediate: true });

onMounted(() => {
    loadExercises();
});
</script>

<template>
    <div v-if="scheme">
        <!-- Scheme Header -->
        <div class="mb-6 sm:mb-8 bg-white/5 border border-white/10 rounded-lg p-4 sm:p-6 backdrop-blur-md">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3 flex-1">
                    <h3 class="text-white font-mono text-xl sm:text-2xl uppercase tracking-widest flex-1">
                        {{ scheme.name }}
                    </h3>
                </div>
            </div>
            
            <div class="flex flex-wrap gap-2">
                <button
                    @click="openExerciseSelector"
                    class="px-4 py-2 bg-white/5 border border-white/10 text-white font-mono text-xs uppercase hover:bg-white/10 hover:cursor-pointer transition-colors rounded flex items-center gap-2"
                >
                    <span class="material-symbols-outlined text-base">{{ $t('pages.gym_log.schemes.add_exercise') }}</span>
                </button>
                <button
                    @click="confirmDelete"
                    class="px-4 py-2 bg-red-500/10 border border-red-500/20 text-red-400 font-mono text-xs uppercase hover:bg-red-500/20 hover:cursor-pointer transition-colors rounded flex items-center gap-2"
                >
                    <span class="material-symbols-outlined text-base">{{ $t('pages.gym_log.schemes.delete') }}</span>
                </button>
            </div>
        </div>

        <!-- Add Exercise Modal -->
        <div v-if="isAddingExercise" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6">
            <div class="bg-black border border-white/20 rounded-lg p-4 sm:p-6 max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-white font-mono text-xs sm:text-sm uppercase tracking-wider">
                        {{ $t('pages.gym_log.schemes.create_exercise') }}
                    </h4>
                    <button @click="isAddingExercise = false; newExerciseName = ''; newExerciseWeight = 0" class="text-white/50 hover:text-white hover:cursor-pointer">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="text-white/70 font-mono text-xs uppercase mb-2 block">
                            {{ $t('pages.gym_log.schemes.exercise_name_placeholder') }}
                        </label>
                        <input
                            v-model="newExerciseName"
                            type="text"
                            :placeholder="$t('pages.gym_log.schemes.exercise_name_placeholder')"
                            class="w-full bg-white/10 border border-white/20 text-white font-mono text-sm px-4 py-3 rounded focus:outline-none focus:border-white/40"
                            @keyup.escape="isAddingExercise = false"
                            ref="exerciseNameInput"
                        />
                    </div>
                    <div>
                        <label class="text-white/70 font-mono text-xs uppercase mb-2 block">
                            {{ $t('pages.gym_log.schemes.starting_weight') }}
                        </label>
                        <div class="flex items-center gap-3">
                            <input
                                v-model="newExerciseWeight"
                                type="number"
                                step="0.5"
                                min="0"
                                class="flex-1 bg-white/10 border border-white/20 text-white font-mono text-sm px-4 py-3 rounded focus:outline-none focus:border-white/40"
                            />
                            <span class="text-white/50 font-mono text-xs uppercase">
                                {{ $t('pages.gym_log.schemes.kg') }}
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button
                            @click="createExercise"
                            :disabled="!newExerciseName.trim()"
                            class="flex-1 px-4 py-3 bg-white text-black font-mono text-xs uppercase hover:bg-white/90 hover:cursor-pointer transition-colors rounded disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ $t('pages.gym_log.schemes.create') }}
                        </button>
                        <button
                            @click="isAddingExercise = false; newExerciseName = ''; newExerciseWeight = 0"
                            class="flex-1 px-4 py-3 bg-white/10 border border-white/20 text-white font-mono text-xs uppercase hover:bg-white/20 hover:cursor-pointer transition-colors rounded"
                        >
                            {{ $t('pages.gym_log.schemes.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exercise Selector Modal -->
        <div v-if="showExerciseSelector" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6">
            <div class="bg-black border border-white/20 rounded-lg p-4 sm:p-6 max-w-md w-full max-h-[90vh] overflow-y-auto ">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-white font-mono text-xs sm:text-sm uppercase tracking-wider">
                        {{ $t('pages.gym_log.schemes.select_exercise') }}
                    </h4>
                    <button @click="closeExerciseSelector" class="text-white/50 hover:text-white hover:cursor-pointer">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <div v-if="allExercises.length > 0" class="space-y-2 mb-4">
                    <button
                        v-for="exercise in allExercises"
                        :key="exercise.id"
                        @click="selectExercise(exercise.id)"
                        class="w-full px-4 py-3 bg-white/5 border border-white/10 text-white font-mono text-sm text-left hover:bg-white/10 hover:cursor-pointer transition-all rounded flex items-center justify-between group"
                    >
                        <span>{{ exercise.name }}</span>
                        <span class="text-white/50 text-xs">{{ exercise.current_weight }} kg</span>
                    </button>
                </div>
                <div v-else class="text-center py-8 mb-4">
                    <p class="text-white/50 font-mono text-xs uppercase">
                        {{ $t('pages.gym_log.schemes.no_exercises') }}
                    </p>
                </div>

                <button
                    @click="openNewExerciseForm"
                    class="w-full px-4 py-3 bg-white text-black font-mono text-xs uppercase hover:bg-white/90 hover:cursor-pointer transition-colors rounded flex items-center justify-center gap-2"
                >
                    <span class="material-symbols-outlined text-base">{{ $t('pages.gym_log.schemes.new_exercise') }}</span>
                </button>
            </div>
        </div>

        <!-- Exercises Grid -->
        <transition
            enter-active-class="transition-all duration-300 ease-out"
            leave-active-class="transition-all duration-200 ease-in"
            enter-from-class="opacity-0 max-h-0 overflow-hidden"
            enter-to-class="opacity-100 max-h-[5000px]"
            leave-from-class="opacity-100 max-h-[5000px]"
            leave-to-class="opacity-0 max-h-0 overflow-hidden"
        >
            <div v-if="!isCollapsed">
                <div v-if="scheme.exercises && scheme.exercises.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                    <ExerciseCard
                        v-for="exercise in scheme.exercises"
                        :key="exercise.id"
                        :exercise="exercise"
                        @update-weight="(weight) => updateExerciseWeight(exercise, weight)"
                        @remove="removeExerciseFromScheme(exercise)"
                    />
                </div>
                <div v-else class="text-center py-12 bg-white/5 border border-white/10 rounded-lg backdrop-blur-md">
                    <p class="text-white/50 font-mono text-xs uppercase mb-4">
                        {{ $t('pages.gym_log.schemes.no_exercises') }}
                    </p>
                    <button
                        @click="openExerciseSelector"
                        class="px-4 py-2 bg-white/5 border border-white/10 text-white font-mono text-xs uppercase hover:bg-white/10 hover:cursor-pointer transition-colors rounded inline-flex items-center gap-2"
                    >
                        <span class="material-symbols-outlined text-base">{{ $t('pages.gym_log.schemes.add_exercise') }}</span>
                    </button>
                </div>
            </div>
        </transition>

        <!-- Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteConfirm"
            :title="$t('pages.gym_log.schemes.confirm_delete_title')"
            :message="$t('pages.gym_log.schemes.confirm_delete_scheme')"
            @close="showDeleteConfirm = false"
            @confirm="deleteScheme"
        />
    </div>
</template>