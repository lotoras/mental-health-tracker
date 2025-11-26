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
const isSubmitting = ref(false);
const isDeleting = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const hasExistingState = ref(false);

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

const isFutureDate = (day) => {
    if (!day) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const checkDate = new Date(currentYear.value, currentMonthNum.value, day);
    return checkDate > today;
};

const openStateModal = (day) => {
    if (!day || isFutureDate(day)) return;

    const dateStr = `${currentYear.value}-${String(currentMonthNum.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    selectedDate.value = dateStr;

    // Pre-fill if state exists
    const existingState = props.states[dateStr];
    if (existingState) {
        selectedStateKey.value = existingState.state_key;
        notes.value = existingState.notes || '';
        hasExistingState.value = true;
    } else {
        hasExistingState.value = false;
    }

    showModal.value = true;
};

const saveState = () => {
    if (!selectedStateKey.value || isSubmitting.value) return;

    isSubmitting.value = true;

    router.post('/calendar/state', {
        date: selectedDate.value,
        state_key: selectedStateKey.value,
        notes: notes.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            selectedStateKey.value = null;
            notes.value = '';
            isSubmitting.value = false;

            // Show success toast
            toastMessage.value = 'Mental state saved successfully!';
            showToast.value = true;
            setTimeout(() => {
                showToast.value = false;
            }, 3000);
        },
        onError: () => {
            isSubmitting.value = false;
            toastMessage.value = 'Failed to save state. Please try again.';
            showToast.value = true;
            setTimeout(() => {
                showToast.value = false;
            }, 3000);
        }
    });
};

const closeModal = () => {
    showModal.value = false;
    selectedStateKey.value = null;
    notes.value = '';
    hasExistingState.value = false;
};

const deleteState = () => {
    if (isDeleting.value) return;

    isDeleting.value = true;

    router.delete('/calendar/state', {
        data: {
            date: selectedDate.value
        },
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            selectedStateKey.value = null;
            notes.value = '';
            hasExistingState.value = false;
            isDeleting.value = false;

            // Show success toast
            toastMessage.value = 'Tag wurde zurückgesetzt!';
            showToast.value = true;
            setTimeout(() => {
                showToast.value = false;
            }, 3000);
        },
        onError: () => {
            isDeleting.value = false;
            toastMessage.value = 'Fehler beim Zurücksetzen. Bitte erneut versuchen.';
            showToast.value = true;
            setTimeout(() => {
                showToast.value = false;
            }, 3000);
        }
    });
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
        <!-- Mobile-First Header with Gradient -->
        <template #header>
            <div class="space-y-4">
                <!-- Title -->
                <h2 class="bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 bg-clip-text text-2xl font-black tracking-tight text-transparent md:text-3xl">
                    Mental Health Kalender
                </h2>

                <!-- Stats Row -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Capacity Card -->
                    <div class="flex-1 overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-medium uppercase tracking-wider text-gray-500">Mentale Kapazität</div>
                                <div class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ currentCapacity }}%
                                </div>
                                <div class="mt-0.5 text-xs font-semibold" :class="[
                                    currentCapacity >= 70 ? 'text-emerald-600' : '',
                                    currentCapacity >= 40 && currentCapacity < 70 ? 'text-amber-600' : '',
                                    currentCapacity >= 20 && currentCapacity < 40 ? 'text-orange-600' : '',
                                    currentCapacity < 20 ? 'text-red-600' : ''
                                ]">
                                    {{ capacityRiskLevel }}
                                </div>
                            </div>
                            <div class="relative h-20 w-20">
                                <!-- Progress Circle -->
                                <svg class="h-20 w-20 -rotate-90 transform" viewBox="0 0 36 36">
                                    <!-- Background circle -->
                                    <circle cx="18" cy="18" r="16" fill="none" stroke="#e5e7eb" stroke-width="3" />
                                    <!-- Progress circle -->
                                    <circle
                                        cx="18"
                                        cy="18"
                                        r="16"
                                        fill="none"
                                        :stroke="currentCapacity >= 70 ? '#10b981' : currentCapacity >= 40 ? '#f59e0b' : currentCapacity >= 20 ? '#f97316' : '#ef4444'"
                                        stroke-width="3"
                                        stroke-linecap="round"
                                        :stroke-dasharray="`${currentCapacity} 100`"
                                        class="transition-all duration-700 ease-out"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Button -->
                    <Link
                        href="/statistics"
                        class="group relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 to-blue-600 px-6 py-3.5 text-center font-bold text-white shadow-lg transition-all hover:scale-105 hover:shadow-xl active:scale-95"
                    >
                        <span class="relative z-10 flex items-center justify-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Statistiken
                        </span>
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-purple-600 opacity-0 transition-opacity group-hover:opacity-100"></div>
                    </Link>
                </div>
            </div>
        </template>

        <!-- Main Content -->
        <div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-4 md:py-8">
            <div class="mx-auto max-w-5xl px-3 sm:px-6 lg:px-8">
                <!-- Calendar Card -->
                <div class="overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-black/5">
                    <div class="p-4 md:p-8">
                        <!-- Month Navigation -->
                        <div class="mb-6 flex items-center justify-between gap-2 md:mb-8">
                            <button
                                @click="previousMonth"
                                class="group flex items-center gap-2 rounded-2xl bg-gradient-to-r from-purple-100 to-pink-100 px-4 py-2.5 font-semibold text-purple-700 shadow-sm transition-all hover:scale-105 hover:shadow-md active:scale-95 md:px-5 md:py-3"
                            >
                                <svg class="h-5 w-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span class="hidden sm:inline">Zurück</span>
                            </button>

                            <div class="text-center">
                                <h3 class="bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-2xl font-black text-transparent md:text-3xl">
                                    {{ monthNames[currentMonthNum] }}
                                </h3>
                                <p class="text-sm font-semibold text-gray-500 md:text-base">{{ currentYear }}</p>
                            </div>

                            <button
                                @click="nextMonth"
                                class="group flex items-center gap-2 rounded-2xl bg-gradient-to-r from-blue-100 to-purple-100 px-4 py-2.5 font-semibold text-blue-700 shadow-sm transition-all hover:scale-105 hover:shadow-md active:scale-95 md:px-5 md:py-3"
                            >
                                <span class="hidden sm:inline">Weiter</span>
                                <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="grid grid-cols-7 gap-1.5 md:gap-3">
                            <!-- Day headers -->
                            <div class="p-1 text-center text-xs font-bold text-gray-500 md:p-2 md:text-sm">So</div>
                            <div class="p-1 text-center text-xs font-bold text-gray-500 md:p-2 md:text-sm">Mo</div>
                            <div class="p-1 text-center text-xs font-bold text-gray-500 md:p-2 md:text-sm">Di</div>
                            <div class="p-1 text-center text-xs font-bold text-gray-500 md:p-2 md:text-sm">Mi</div>
                            <div class="p-1 text-center text-xs font-bold text-gray-500 md:p-2 md:text-sm">Do</div>
                            <div class="p-1 text-center text-xs font-bold text-gray-500 md:p-2 md:text-sm">Fr</div>
                            <div class="p-1 text-center text-xs font-bold text-gray-500 md:p-2 md:text-sm">Sa</div>

                            <!-- Calendar days -->
                            <div
                                v-for="(day, index) in calendarDays"
                                :key="index"
                                class="flex items-center justify-center"
                            >
                                <button
                                    v-if="day"
                                    @click="openStateModal(day)"
                                    :disabled="isFutureDate(day)"
                                    :style="getDateState(day) && !isFutureDate(day) ? { backgroundColor: getDateState(day).state_type.color } : {}"
                                    :class="[
                                        'group relative flex h-10 w-10 items-center justify-center rounded-2xl text-sm font-bold transition-all duration-300 md:h-16 md:w-16 md:text-lg',
                                        isFutureDate(day)
                                            ? 'cursor-not-allowed bg-gray-200 text-gray-400 opacity-50'
                                            : [
                                                'hover:scale-110 active:scale-95',
                                                getDateState(day)
                                                    ? 'text-white shadow-lg hover:shadow-2xl'
                                                    : 'bg-gradient-to-br from-gray-50 to-gray-100 text-gray-700 shadow-sm hover:from-purple-50 hover:to-blue-50 hover:text-purple-700 hover:shadow-md'
                                            ]
                                    ]"
                                >
                                    <span class="relative z-10">{{ day }}</span>
                                    <div v-if="!getDateState(day) && !isFutureDate(day)" class="absolute inset-0 rounded-2xl bg-gradient-to-br from-purple-400/0 to-blue-400/0 opacity-0 transition-opacity group-hover:from-purple-400/10 group-hover:to-blue-400/10 group-hover:opacity-100"></div>
                                </button>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="mt-6 rounded-2xl bg-gradient-to-r from-purple-50 via-pink-50 to-blue-50 p-4 md:mt-8 md:p-6">
                            <h4 class="mb-3 text-sm font-bold uppercase tracking-wider text-gray-600">Stimmungen</h4>
                            <div class="grid grid-cols-2 gap-2.5 md:flex md:flex-wrap md:gap-3">
                                <div
                                    v-for="stateType in stateTypes"
                                    :key="stateType.key"
                                    class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 shadow-sm transition-all hover:scale-105 hover:shadow-md"
                                >
                                    <div
                                        :style="{ backgroundColor: stateType.color }"
                                        class="h-4 w-4 rounded-full shadow-sm md:h-5 md:w-5"
                                    ></div>
                                    <span class="text-xs font-semibold text-gray-700 md:text-sm">{{ stateType.label }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- State Selection Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-0 backdrop-blur-sm sm:items-center p-2 rounded"
                    @click.self="closeModal"
                >
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                        enter-to-class="translate-y-0 sm:scale-100 opacity-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="translate-y-0 sm:scale-100 opacity-100"
                        leave-to-class="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                    >
                        <div v-if="showModal" class="w-full max-w-2xl overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl">
                            <!-- Header with Gradient -->
                            <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 p-6">
                                <h3 class="text-2xl font-black text-white">Wie geht es dir heute?</h3>
                                <p class="mt-1 text-sm font-medium text-white/80">{{ selectedDate }}</p>
                            </div>

                            <div class="p-6">
                                <!-- State Selection Grid -->
                                <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
                                    <button
                                        v-for="stateType in stateTypes"
                                        :key="stateType.key"
                                        @click="selectedStateKey = stateType.key"
                                        :style="{ backgroundColor: stateType.color }"
                                        :class="[
                                            'group relative flex flex-col items-center justify-center overflow-hidden rounded-2xl p-4 text-white shadow-lg transition-all duration-300 active:scale-95',
                                            selectedStateKey === stateType.key
                                                ? 'scale-105 ring-4 ring-purple-400 ring-offset-2 shadow-2xl'
                                                : 'hover:scale-105 hover:shadow-xl'
                                        ]"
                                    >
                                        <span class="relative z-10 text-center text-sm font-bold">{{ stateType.label }}</span>
                                        <span class="relative z-10 mt-1 rounded-full bg-white/20 px-2 py-0.5 text-xs font-semibold">
                                            {{ stateType.capacity_impact > 0 ? '+' : '' }}{{ stateType.capacity_impact }}%
                                        </span>
                                        <div v-if="selectedStateKey === stateType.key" class="absolute inset-0 bg-white/10"></div>
                                    </button>
                                </div>

                                <!-- Notes Section -->
                                <div class="mb-6">
                                    <label class="mb-2 flex items-center gap-2 text-sm font-bold text-gray-700">
                                        <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Notizen (optional)
                                    </label>
                                    <textarea
                                        v-model="notes"
                                        rows="3"
                                        class="w-full rounded-2xl border-2 border-gray-200 bg-gray-50 px-4 py-3 transition-all focus:border-purple-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-purple-100"
                                        placeholder="Füge Notizen zu deinem Tag hinzu..."
                                    ></textarea>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-col gap-3">
                                    <!-- Reset Button (only show if state exists) -->
                                    <button
                                        v-if="hasExistingState"
                                        @click="deleteState"
                                        :disabled="isDeleting || isSubmitting"
                                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-500 to-rose-500 px-6 py-3.5 font-bold text-white shadow-lg transition-all hover:shadow-xl active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <svg
                                            v-if="isDeleting"
                                            class="h-5 w-5 animate-spin"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle
                                                class="opacity-25"
                                                cx="12"
                                                cy="12"
                                                r="10"
                                                stroke="currentColor"
                                                stroke-width="4"
                                            ></circle>
                                            <path
                                                class="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                            ></path>
                                        </svg>
                                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>{{ isDeleting ? 'Wird zurückgesetzt...' : 'Tag zurücksetzen' }}</span>
                                    </button>

                                    <!-- Save and Cancel Buttons -->
                                    <div class="flex gap-3">
                                        <button
                                            @click="closeModal"
                                            :disabled="isSubmitting || isDeleting"
                                            class="flex-1 rounded-2xl bg-gray-100 px-6 py-3.5 font-bold text-gray-700 transition-all hover:bg-gray-200 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Abbrechen
                                        </button>
                                        <button
                                            @click="saveState"
                                            :disabled="!selectedStateKey || isSubmitting || isDeleting"
                                            :class="[
                                                'flex flex-1 items-center justify-center gap-2 rounded-2xl px-6 py-3.5 font-bold text-white shadow-lg transition-all active:scale-95',
                                                selectedStateKey && !isSubmitting && !isDeleting
                                                    ? 'bg-gradient-to-r from-purple-600 to-blue-600 hover:shadow-xl'
                                                    : 'cursor-not-allowed bg-gray-300'
                                            ]"
                                        >
                                            <svg
                                                v-if="isSubmitting"
                                                class="h-5 w-5 animate-spin"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle
                                                    class="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    stroke-width="4"
                                                ></circle>
                                                <path
                                                    class="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                ></path>
                                            </svg>
                                            <span>{{ isSubmitting ? 'Speichern...' : 'Speichern' }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Toast Notification -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="translate-y-full sm:translate-y-0 sm:translate-x-full opacity-0 scale-50"
                enter-to-class="translate-y-0 sm:translate-x-0 opacity-100 scale-100"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="translate-y-0 sm:translate-x-0 opacity-100 scale-100"
                leave-to-class="translate-y-full sm:translate-y-0 sm:translate-x-full opacity-0 scale-75"
            >
                <div
                    v-if="showToast"
                    class="fixed bottom-4 left-4 right-4 z-50 mx-auto flex max-w-md items-center gap-3 overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 px-5 py-4 text-white shadow-2xl ring-2 ring-white/50 sm:bottom-8 sm:left-auto sm:right-8 sm:mx-0"
                >
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            stroke-width="3"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            ></path>
                        </svg>
                    </div>
                    <span class="flex-1 font-bold">{{ toastMessage }}</span>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
