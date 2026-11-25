<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import axios from "axios";

const { t } = useI18n();
const emit = defineEmits(["changed"]);

const currentDate = ref(new Date());
const gymDays = ref(new Set());
const loading = ref(true);
const togglingDay = ref(null);

// Calendar logic
const currentYear = computed(() => currentDate.value.getFullYear());
const currentMonth = computed(() => currentDate.value.getMonth());
const today = new Date();

const currentMonthName = computed(() =>
    t(`pages.gym_log.calendar.months.${currentMonth.value}`)
);
const dayNames = computed(() => [
    t("pages.gym_log.calendar.days.mon"),
    t("pages.gym_log.calendar.days.tue"),
    t("pages.gym_log.calendar.days.wed"),
    t("pages.gym_log.calendar.days.thu"),
    t("pages.gym_log.calendar.days.fri"),
    t("pages.gym_log.calendar.days.sat"),
    t("pages.gym_log.calendar.days.sun"),
]);

const daysInMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value + 1, 0).getDate();
});

const firstDayOfMonth = computed(() => {
    const day = new Date(currentYear.value, currentMonth.value, 1).getDay();
    // Convert Sunday (0) to 6, and shift Monday (1) to 0
    return day === 0 ? 6 : day - 1;
});

const calendarDays = computed(() => {
    const days = [];
    const totalDays = daysInMonth.value;
    const firstDay = firstDayOfMonth.value;

    // Add empty slots for days before month starts (Monday = 0)
    for (let i = 0; i < firstDay; i++) {
        days.push(null);
    }

    // Add actual days
    for (let day = 1; day <= totalDays; day++) {
        days.push(day);
    }

    return days;
});

// Load gym logs from database
const loadGymLogs = async () => {
    loading.value = true;
    try {
        const response = await axios.get("/api/gym-logs", {
            params: {
                year: currentYear.value,
                month: currentMonth.value + 1,
            },
        });
        gymDays.value = new Set(response.data);
    } catch (error) {
        console.error("Failed to load gym logs:", error);
    } finally {
        loading.value = false;
    }
};

const toggleGymDay = async (day) => {
    if (!day) return;

    const dateKey = dateKeyFor(day);

    try {
        togglingDay.value = dateKey;
        await axios.post("/api/gym-logs/toggle", {
            date: dateKey,
        });

        // Update local state
        const nextGymDays = new Set(gymDays.value);
        if (nextGymDays.has(dateKey)) {
            nextGymDays.delete(dateKey);
        } else {
            nextGymDays.add(dateKey);
        }
        gymDays.value = nextGymDays;

        emit("changed");
    } catch (error) {
        console.error("Failed to toggle gym day:", error);
    } finally {
        togglingDay.value = null;
    }
};

const isGymDay = (day) => {
    if (!day) return false;
    return gymDays.value.has(dateKeyFor(day));
};

const isToday = (day) => {
    if (!day) return false;
    const d = new Date(currentYear.value, currentMonth.value, day);
    return (
        d.getFullYear() === today.getFullYear() &&
        d.getMonth() === today.getMonth() &&
        d.getDate() === today.getDate()
    );
};

const dateKeyFor = (day) =>
    `${currentYear.value}-${String(currentMonth.value + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;

const previousMonth = () => {
    currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
};

const nextMonth = () => {
    currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
};

// Watch for month/year changes and reload data
watch([currentYear, currentMonth], () => {
    loadGymLogs();
});

// Load initial data
onMounted(() => {
    loadGymLogs();
});
</script>

<template>
    <!-- Calendar Controls -->
    <div class="mb-6 sm:mb-8">
        <div class="text-center order-1 sm:order-2">
            <h3
                class="text-white font-mono text-lg sm:text-xl uppercase tracking-widest"
            >
                {{ currentMonthName }} {{ currentYear }}
            </h3>
        </div>
    </div>

    <div
        class="mb-6 sm:mb-8 flex flex-row sm:flex-row items-center justify-between gap-4 "
    >
        <button
            @click="previousMonth"
            class="w-full sm:w-auto px-4 backdrop-blur-md py-2 bg-white/5 border border-white/10 text-white font-mono text-xs uppercase hover:bg-white/10 hover:cursor-pointer transition-colors rounded order-2 sm:order-1"
        >
            &larr; {{ $t("pages.gym_log.calendar.previous") }}
        </button>

        <button
            @click="nextMonth"
            class="w-full sm:w-auto px-4 backdrop-blur-md py-2 bg-white/5 border border-white/10 text-white font-mono text-xs uppercase hover:bg-white/10 hover:cursor-pointer transition-colors rounded order-3"
        >
            {{ $t("pages.gym_log.calendar.next") }} &rarr;
        </button>
    </div>

    <!-- Calendar Grid -->
    <div
        class="bg-white/5 border border-white/10 rounded-lg p-3 sm:p-4 md:p-6 backdrop-blur-md"
    >
        <!-- Day Headers -->
        <div class="grid grid-cols-7 gap-1 sm:gap-2 mb-3 sm:mb-4">
            <div
                v-for="(day, index) in dayNames"
                :key="index"
                class="text-center text-white/40 font-mono text-[10px] sm:text-xs uppercase tracking-widest py-1 sm:py-2"
            >
                <span class="hidden sm:inline">{{ day }}</span>
                <span class="sm:hidden">{{ day.substring(0, 1) }}</span>
            </div>
        </div>

        <!-- Calendar Days -->
        <div class="grid grid-cols-7 gap-1 sm:gap-2">
            <template v-if="loading">
                <button
                    v-for="(day, index) in calendarDays"
                    :key="index"
                    disabled
                    class="relative aspect-square flex items-center justify-center font-mono text-xs sm:text-sm transition-all duration-200 border border-white/10 rounded bg-white/5 text-white/70"
                >
                    <div class="w-5 h-5 border-2 border-white/70 border-t-transparent rounded-full animate-spin"></div>
                </button>
            </template>
            <template v-else>
                <button
                    v-for="(day, index) in calendarDays"
                    :key="index"
                    @click="toggleGymDay(day)"
                    :disabled="!day || togglingDay === dateKeyFor(day)"
                    :class="[
                        'relative aspect-square flex items-center justify-center font-mono text-xs sm:text-sm transition-all duration-200',
                        'border border-white/10 rounded',
                        day
                            ? 'cursor-pointer hover:border-white/30 active:scale-95'
                            : 'invisible',
                            isGymDay(day)
                                ? 'bg-white text-black font-bold shadow-[0_0_15px_rgba(255,255,255,0.3)]'
                                : 'bg-white/5 text-white/70 hover:bg-white/10',
                            isToday(day) ? 'ring-2 ring-white/60' : '',
                    ]"
                >
                    <span v-if="togglingDay !== dateKeyFor(day)">{{ day }}</span>

                    <!-- centered spinner while toggling -->
                    <div v-if="togglingDay === dateKeyFor(day)" class="absolute inset-0 flex items-center justify-center bg-transparent">
                        <div class="w-5 h-5 border-2 border-white/70 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                </button>
            </template>
        </div>
    </div>

    <!-- Mobile legend -->
    <div
        class="mt-4 flex items-center justify-center gap-4 text-xs font-mono text-white/50 sm:hidden"
    >
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 bg-white rounded"></div>
            <span>Gym day</span>
        </div>
    </div>
</template>
