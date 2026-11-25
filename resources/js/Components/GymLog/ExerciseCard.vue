<script setup>
import { ref, defineProps, defineEmits } from "vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
    exercise: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update-weight', 'remove']);

const isEditing = ref(false);
const editWeight = ref(props.exercise.current_weight);

const adjustWeight = (amount) => {
    const newWeight = Math.max(0, parseFloat(props.exercise.current_weight || 0) + amount);
    emit('update-weight', newWeight);
};

const saveEdit = () => {
    const newWeight = Math.max(0, parseFloat(editWeight.value || 0));
    emit('update-weight', newWeight);
    isEditing.value = false;
};

const cancelEdit = () => {
    isEditing.value = false;
};
</script>

<template>
    <div class="bg-white/5 border border-white/10 rounded-lg p-3 sm:p-4 hover:border-white/20 transition-all backdrop-blur-md">
        <!-- Exercise Name -->
        <h4 class="text-white font-mono text-xs sm:text-sm uppercase tracking-wider mb-3 sm:mb-4">
            {{ exercise.name }}
        </h4>

        <!-- Weight Display/Edit -->
        <div class="mb-3 sm:mb-4">
            <div v-if="!isEditing" class="flex items-baseline gap-2">
                <span class="text-white font-mono text-2xl sm:text-3xl font-bold">
                    {{ exercise.current_weight || 0 }}
                </span>
                <span class="text-white/50 font-mono text-xs sm:text-sm uppercase">
                    {{ $t('pages.gym_log.schemes.kg') }}
                </span>
            </div>
            <div v-else class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <input
                    v-model="editWeight"
                    type="number"
                    step="0.5"
                    min="0"
                    class="bg-white/10 border border-white/20 text-white font-mono text-lg sm:text-xl px-3 py-2 rounded w-full sm:w-24 focus:outline-none focus:border-white/40"
                    @keyup.enter="saveEdit"
                    @keyup.escape="cancelEdit"
                />
                <div class="flex gap-2">
                    <button
                        @click="saveEdit"
                        class="flex-1 sm:flex-none px-3 py-2 bg-white text-black font-mono text-xs uppercase hover:bg-white/90 hover:cursor-pointer transition-colors rounded"
                    >
                        {{ $t('pages.gym_log.schemes.save') }}
                    </button>
                    <button
                        @click="cancelEdit"
                        class="flex-1 sm:flex-none px-3 py-2 bg-white/10 border border-white/20 text-white font-mono text-xs uppercase hover:bg-white/20 hover:cursor-pointer transition-colors rounded"
                    >
                        {{ $t('pages.gym_log.schemes.cancel') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Adjust Buttons -->
        <div class="grid grid-cols-2 gap-2 mb-3">
            <button
                @click="adjustWeight(-5)"
                class="px-2 sm:px-3 py-2 bg-white/5 border border-white/10 text-white/70 font-mono text-xs uppercase hover:bg-white/10 hover:text-white hover:cursor-pointer transition-all rounded"
            >
                -5 {{ $t('pages.gym_log.schemes.kg') }}
            </button>
            <button
                @click="adjustWeight(5)"
                class="px-2 sm:px-3 py-2 bg-white/5 border border-white/10 text-white/70 font-mono text-xs uppercase hover:bg-white/10 hover:text-white hover:cursor-pointer transition-all rounded"
            >
                +5 {{ $t('pages.gym_log.schemes.kg') }}
            </button>
            <button
                @click="adjustWeight(-2.5)"
                class="px-2 sm:px-3 py-2 bg-white/5 border border-white/10 text-white/70 font-mono text-xs uppercase hover:bg-white/10 hover:text-white hover:cursor-pointer transition-all rounded"
            >
                -2.5 {{ $t('pages.gym_log.schemes.kg') }}
            </button>
            <button
                @click="adjustWeight(2.5)"
                class="px-2 sm:px-3 py-2 bg-white/5 border border-white/10 text-white/70 font-mono text-xs uppercase hover:bg-white/10 hover:text-white hover:cursor-pointer transition-all rounded"
            >
                +2.5 {{ $t('pages.gym_log.schemes.kg') }}
            </button>
        </div>

        <!-- Remove Button -->
        <button
            @click="$emit('remove')"
            class="w-full px-3 py-2 bg-red-500/10 border border-red-500/20 text-red-400 font-mono text-xs uppercase hover:bg-red-500/20 hover:cursor-pointer transition-all rounded flex items-center justify-center gap-2"
        >
            <span class="material-symbols-outlined text-base">{{ $t('pages.gym_log.schemes.remove_exercise') }}</span>
            
        </button>
    </div>
</template>
