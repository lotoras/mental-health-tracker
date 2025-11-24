<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    states: {
        type: Object,
        default: () => ({})
    },
    stateTypes: {
        type: Array,
        default: () => []
    },
    currentMonth: {
        type: String,
        default: new Date().toISOString().slice(0, 7)
    },
    currentCapacity: {
        type: Number,
        default: 100
    }
});

const selectedDate = ref(null);
const selectedStateKey = ref(null);
const showModal = ref(false);
const notes = ref('');

const [year, month] = props.currentMonth.split('-').map(Number);
const currentYear = ref(year);
const currentMonthNum = ref(month - 1);

const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const calendarDays = computed(() => {
    const year = currentYear.value;
    const month = currentMonthNum.value;

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    const days = [];

    // Add empty cells for days before the month starts
    for (let i = 0; i < startingDayOfWeek; i++) {
        days.push(null);
    }

    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        days.push(day);
    }

    return days;
});

const getDateState = (day) => {
    if (!day) return null;
    const dateStr = `${currentYear.value}-${String(currentMonthNum.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    return props.states[dateStr] || null;
};

const openStateModal = (day) => {
    if (!day) return;

    const dateStr = `${currentYear.value}-${String(currentMonthNum.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    selectedDate.value = dateStr;

    // Pre-fill if state exists
    const existingState = props.states[dateStr];
    if (existingState) {
        selectedStateKey.value = existingState.state_key;
        notes.value = existingState.notes || '';
    }

    showModal.value = true;
};

const saveState = () => {
    if (!selectedStateKey.value) return;

    router.post('/calendar/state', {
        date: selectedDate.value,
        state_key: selectedStateKey.value,
        notes: notes.value
    }, {
        onSuccess: () => {
            showModal.value = false;
            selectedStateKey.value = null;
            notes.value = '';
        }
    });
};

const closeModal = () => {
    showModal.value = false;
    selectedStateKey.value = null;
    notes.value = '';
};

const previousMonth = () => {
    if (currentMonthNum.value === 0) {
        currentMonthNum.value = 11;
        currentYear.value--;
    } else {
        currentMonthNum.value--;
    }
    navigateToMonth();
};

const nextMonth = () => {
    if (currentMonthNum.value === 11) {
        currentMonthNum.value = 0;
        currentYear.value++;
    } else {
        currentMonthNum.value++;
    }
    navigateToMonth();
};

const navigateToMonth = () => {
    const monthStr = `${currentYear.value}-${String(currentMonthNum.value + 1).padStart(2, '0')}`;
    router.get('/calendar', { month: monthStr }, { preserveState: true });
};

const capacityColor = computed(() => {
    if (props.currentCapacity >= 70) return 'bg-green-500';
    if (props.currentCapacity >= 40) return 'bg-yellow-500';
    if (props.currentCapacity >= 20) return 'bg-orange-500';
    return 'bg-red-500';
});

const capacityRiskLevel = computed(() => {
    if (props.currentCapacity >= 70) return 'Gut';
    if (props.currentCapacity >= 40) return 'Mittel';
    if (props.currentCapacity >= 20) return 'Hoch';
    return 'Kritisch';
});
</script>

<template>
    <Head title="Kalender" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Mental Health Kalender
                </h2>

                <div class="flex items-center gap-4">
                    <Link
                        href="/statistics"
                        class="rounded-lg bg-purple-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-purple-600"
                    >
                        Statistiken
                    </Link>

                    <!-- Capacity Indicator -->
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Mentale Kapazität</div>
                        <div class="text-sm font-semibold text-gray-700">
                            {{ currentCapacity }}% - {{ capacityRiskLevel }}
                        </div>
                    </div>
                    <div class="w-32 overflow-hidden rounded-full bg-gray-200">
                        <div
                            :class="capacityColor"
                            class="h-3 transition-all duration-500"
                            :style="{ width: `${currentCapacity}%` }"
                        ></div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Month Navigation -->
                        <div class="mb-6 flex items-center justify-between">
                            <button
                                @click="previousMonth"
                                class="rounded-full bg-gray-200 px-4 py-2 transition hover:bg-gray-300"
                            >
                                ← Vorheriger
                            </button>
                            <h3 class="text-2xl font-bold">
                                {{ monthNames[currentMonthNum] }} {{ currentYear }}
                            </h3>
                            <button
                                @click="nextMonth"
                                class="rounded-full bg-gray-200 px-4 py-2 transition hover:bg-gray-300"
                            >
                                Nächster →
                            </button>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="grid grid-cols-7 gap-2">
                            <!-- Day headers -->
                            <div class="p-2 text-center font-semibold text-gray-600">So</div>
                            <div class="p-2 text-center font-semibold text-gray-600">Mo</div>
                            <div class="p-2 text-center font-semibold text-gray-600">Di</div>
                            <div class="p-2 text-center font-semibold text-gray-600">Mi</div>
                            <div class="p-2 text-center font-semibold text-gray-600">Do</div>
                            <div class="p-2 text-center font-semibold text-gray-600">Fr</div>
                            <div class="p-2 text-center font-semibold text-gray-600">Sa</div>

                            <!-- Calendar days -->
                            <div
                                v-for="(day, index) in calendarDays"
                                :key="index"
                                class="aspect-square"
                            >
                                <button
                                    v-if="day"
                                    @click="openStateModal(day)"
                                    :style="getDateState(day) ? { backgroundColor: getDateState(day).state_type.color } : {}"
                                    :class="[
                                        'flex h-full w-full items-center justify-center rounded-full text-lg font-medium text-white transition-all',
                                        getDateState(day)
                                            ? 'shadow-lg hover:scale-110'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    ]"
                                >
                                    {{ day }}
                                </button>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="mt-8">
                            <h4 class="mb-3 text-sm font-semibold text-gray-700">Legende</h4>
                            <div class="flex flex-wrap gap-3">
                                <div
                                    v-for="stateType in stateTypes"
                                    :key="stateType.key"
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        :style="{ backgroundColor: stateType.color }"
                                        class="h-5 w-5 rounded-full"
                                    ></div>
                                    <span class="text-sm">{{ stateType.label }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- State Selection Modal -->
        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
                @click.self="closeModal"
            >
                <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
                    <h3 class="mb-4 text-xl font-bold">Wie geht es dir heute?</h3>
                    <p class="mb-4 text-sm text-gray-600">{{ selectedDate }}</p>

                    <!-- State Selection -->
                    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <button
                            v-for="stateType in stateTypes"
                            :key="stateType.key"
                            @click="selectedStateKey = stateType.key"
                            :style="{ backgroundColor: stateType.color }"
                            :class="[
                                'flex flex-col items-center justify-center rounded-xl p-4 text-white transition-transform',
                                selectedStateKey === stateType.key ? 'scale-105 ring-4 ring-blue-400' : 'hover:scale-105'
                            ]"
                        >
                            <span class="text-sm font-medium">{{ stateType.label }}</span>
                            <span class="mt-1 text-xs opacity-75">
                                {{ stateType.capacity_impact > 0 ? '+' : '' }}{{ stateType.capacity_impact }}%
                            </span>
                        </button>
                    </div>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Notizen (optional)
                        </label>
                        <textarea
                            v-model="notes"
                            rows="3"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Füge Notizen zu deinem Tag hinzu..."
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <button
                            @click="closeModal"
                            class="rounded-lg bg-gray-200 px-4 py-2 font-medium transition hover:bg-gray-300"
                        >
                            Abbrechen
                        </button>
                        <button
                            @click="saveState"
                            :disabled="!selectedStateKey"
                            :class="[
                                'rounded-lg px-4 py-2 font-medium text-white transition',
                                selectedStateKey
                                    ? 'bg-blue-500 hover:bg-blue-600'
                                    : 'cursor-not-allowed bg-gray-400'
                            ]"
                        >
                            Speichern
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
