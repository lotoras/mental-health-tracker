<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    statistics: {
        type: Object,
        required: true
    },
    currentMonth: {
        type: String,
        default: new Date().toISOString().slice(0, 7)
    },
    range: {
        type: String,
        default: 'month'
    }
});

const toggleRange = () => {
    const newRange = props.range === 'month' ? 'all' : 'month';
    router.get('/statistics', { range: newRange, month: props.currentMonth }, {
        preserveState: true,
        preserveScroll: true
    });
};

// Prepare data for state breakdown chart (excluding untracked days)
const stateBreakdownLabels = computed(() => {
    return Object.values(props.statistics.state_breakdown)
        .filter(item => item.label !== 'Nicht erfasst')
        .map(item => item.label);
});

const stateBreakdownSeries = computed(() => {
    return Object.values(props.statistics.state_breakdown)
        .filter(item => item.label !== 'Nicht erfasst')
        .map(item => item.percentage);
});

const stateBreakdownColors = computed(() => {
    return Object.values(props.statistics.state_breakdown)
        .filter(item => item.label !== 'Nicht erfasst')
        .map(item => item.color);
});

const chartOptions = computed(() => ({
    chart: {
        type: 'donut',
        fontFamily: 'Figtree, sans-serif',
    },
    labels: stateBreakdownLabels.value,
    colors: stateBreakdownColors.value,
    legend: {
        position: 'bottom',
        fontSize: '14px',
        fontWeight: 600,
        markers: {
            width: 12,
            height: 12,
            radius: 12
        },
        itemMargin: {
            horizontal: 8,
            vertical: 4
        }
    },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: '14px',
                        fontWeight: 600,
                        color: '#6b7280',
                        offsetY: -10
                    },
                    value: {
                        show: true,
                        fontSize: '32px',
                        fontWeight: 900,
                        color: '#111827',
                        offsetY: 10,
                        formatter: function (val) {
                            return val;
                        }
                    },
                    total: {
                        show: true,
                        showAlways: true,
                        label: 'Tage insgesamt',
                        fontSize: '14px',
                        fontWeight: 600,
                        color: '#6b7280',
                        formatter: function (w) {
                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                        }
                    }
                }
            }
        }
    },
    dataLabels: {
        enabled: true,
        style: {
            fontSize: '14px',
            fontWeight: 700,
            colors: ['#fff']
        },
        dropShadow: {
            enabled: true,
            top: 1,
            left: 1,
            blur: 2,
            opacity: 0.5
        },
        formatter: function (val, opts) {
            const breakdown = Object.values(props.statistics.state_breakdown)
                .filter(item => item.label !== 'Nicht erfasst');
            const item = breakdown[opts.seriesIndex];
            return item.count + ' (' + val.toFixed(1) + '%)';
        }
    },
    stroke: {
        width: 0
    },
    states: {
        hover: {
            filter: {
                type: 'darken',
                value: 0.15
            }
        },
        active: {
            filter: {
                type: 'darken',
                value: 0.15
            }
        }
    },
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                width: 320
            },
            legend: {
                position: 'bottom',
                fontSize: '12px'
            },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            name: {
                                fontSize: '12px'
                            },
                            value: {
                                fontSize: '28px'
                            },
                            total: {
                                fontSize: '12px'
                            }
                        }
                    }
                }
            }
        }
    }]
}));

// Capacity timeline chart
const capacityChartOptions = computed(() => ({
    chart: {
        type: 'line',
        fontFamily: 'Figtree, sans-serif',
        toolbar: {
            show: false
        }
    },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    colors: ['#3b82f6'],
    xaxis: {
        categories: props.statistics.capacity_timeline.map(item => {
            const date = new Date(item.date);
            return `${date.getDate()}.${date.getMonth() + 1}`;
        }),
        title: {
            text: 'Tag'
        }
    },
    yaxis: {
        min: 0,
        max: 100,
        title: {
            text: 'Kapazität %'
        }
    },
    annotations: {
        yaxis: [
            {
                y: 70,
                borderColor: '#10b981',
                label: {
                    text: 'Gut',
                    style: {
                        color: '#fff',
                        background: '#10b981'
                    }
                }
            },
            {
                y: 40,
                borderColor: '#f59e0b',
                label: {
                    text: 'Mittel',
                    style: {
                        color: '#fff',
                        background: '#f59e0b'
                    }
                }
            },
            {
                y: 20,
                borderColor: '#ef4444',
                label: {
                    text: 'Kritisch',
                    style: {
                        color: '#fff',
                        background: '#ef4444'
                    }
                }
            }
        ]
    },
    tooltip: {
        y: {
            formatter: function(val) {
                return val + '%';
            }
        }
    }
}));

const capacitySeries = computed(() => [{
    name: 'Mentale Kapazität',
    data: props.statistics.capacity_timeline.map(item => item.capacity)
}]);

const getRiskColor = (risk) => {
    switch(risk) {
        case 'low': return 'bg-green-100 text-green-800';
        case 'medium': return 'bg-yellow-100 text-yellow-800';
        case 'high': return 'bg-orange-100 text-orange-800';
        case 'critical': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getRiskLabel = (risk) => {
    switch(risk) {
        case 'low': return 'Niedrig';
        case 'medium': return 'Mittel';
        case 'high': return 'Hoch';
        case 'critical': return 'Kritisch';
        default: return 'Unbekannt';
    }
};
</script>

<template>
    <Head title="Statistiken" />

    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-4">
                <!-- Title and Calendar Button -->
                <div class="flex items-center justify-between">
                    <h2 class="bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 bg-clip-text text-2xl font-black tracking-tight text-transparent md:text-3xl">
                        Statistiken & Analysen
                    </h2>
                    <Link
                        href="/calendar"
                        class="group relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 to-blue-600 px-6 py-3 font-bold text-white shadow-lg transition-all hover:scale-105 hover:shadow-xl active:scale-95"
                    >
                        <span class="relative z-10 flex items-center justify-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Kalender
                        </span>
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-purple-600 opacity-0 transition-opacity group-hover:opacity-100"></div>
                    </Link>
                </div>

                <!-- Range Toggle -->
                <div class="flex items-center justify-center">
                    <div class="inline-flex items-center rounded-full bg-white p-1 shadow-lg ring-1 ring-black/5">
                        <button
                            @click="toggleRange"
                            :class="[
                                'rounded-full px-6 py-2 text-sm font-bold transition-all duration-300',
                                range === 'month'
                                    ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-md'
                                    : 'text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            Dieser Monat
                        </button>
                        <button
                            @click="toggleRange"
                            :class="[
                                'rounded-full px-6 py-2 text-sm font-bold transition-all duration-300',
                                range === 'all'
                                    ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-md'
                                    : 'text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            Gesamt
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-4 md:py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-3 sm:px-6 lg:px-8">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="group overflow-hidden rounded-3xl bg-white p-6 shadow-xl ring-1 ring-black/5 transition-all hover:scale-105 hover:shadow-2xl">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-gray-500">Einträge gesamt</div>
                                <div class="mt-2 text-3xl font-black text-gray-900">
                                    {{ statistics.total_entries }}
                                </div>
                                <div class="mt-1 text-xs font-semibold text-purple-600">
                                    {{ range === 'month' ? 'Diesen Monat' : 'Alle Einträge' }}
                                </div>
                            </div>
                            <div class="rounded-2xl bg-gradient-to-br from-purple-100 to-pink-100 p-3">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="group overflow-hidden rounded-3xl bg-gradient-to-br from-purple-600 via-pink-600 to-blue-600 p-6 shadow-xl ring-1 ring-black/5 transition-all hover:scale-105 hover:shadow-2xl">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-white/80">Aktuelle Kapazität</div>
                                <div class="mt-2 text-3xl font-black text-white">
                                    {{ statistics.current_capacity }}%
                                </div>
                                <div class="mt-1 text-xs font-semibold text-white/90">Mental Energy Level</div>
                            </div>
                            <div class="rounded-2xl bg-white/20 p-3 backdrop-blur-sm">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="group overflow-hidden rounded-3xl bg-white p-6 shadow-xl ring-1 ring-black/5 transition-all hover:scale-105 hover:shadow-2xl">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-gray-500">Tage seit Breakdown</div>
                                <div class="mt-2 text-3xl font-black text-gray-900">
                                    {{ statistics.days_since_last_breakdown ?? 'N/A' }}
                                </div>
                                <div class="mt-1 text-xs font-semibold text-emerald-600">Letzter "Im Loch" Tag</div>
                            </div>
                            <div class="rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 p-3">
                                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="group overflow-hidden rounded-3xl bg-white p-6 shadow-xl ring-1 ring-black/5 transition-all hover:scale-105 hover:shadow-2xl">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-gray-500">
                                    {{ range === 'month' ? 'Breakdowns diesen Monat' : 'Breakdowns gesamt' }}
                                </div>
                                <div class="mt-2 text-3xl font-black text-gray-900">
                                    {{ statistics.monthly_breakdown_count }}
                                </div>
                                <div class="mt-1 text-xs font-semibold text-orange-600">"Im/Halb im Loch" Tage</div>
                            </div>
                            <div class="rounded-2xl bg-gradient-to-br from-orange-100 to-red-100 p-3">
                                <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Capacity Timeline -->
                <div class="overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-black/5">
                    <div class="bg-gradient-to-r from-purple-50 via-pink-50 to-blue-50 p-6">
                        <h3 class="flex items-center gap-2 text-xl font-black text-gray-900">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                            {{ range === 'month' ? 'Kapazitätsverlauf diesen Monat' : 'Kapazitätsverlauf gesamt' }}
                        </h3>
                    </div>
                    <div class="p-6">
                        <div v-if="statistics.capacity_timeline.length > 0">
                            <VueApexCharts
                                type="line"
                                :options="capacityChartOptions"
                                :series="capacitySeries"
                                height="350"
                            />
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-16">
                            <div class="rounded-full bg-gradient-to-br from-purple-100 to-pink-100 p-6">
                                <svg class="h-12 w-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <p class="mt-4 text-center font-semibold text-gray-500">Keine Daten verfügbar</p>
                            <p class="mt-1 text-center text-sm text-gray-400">Füge Einträge im Kalender hinzu</p>
                        </div>
                    </div>
                </div>

                <!-- Breakdown Analysis -->
                <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-purple-600 via-pink-600 to-blue-600 shadow-xl ring-1 ring-black/5">
                    <div class="p-6">
                        <h3 class="mb-6 flex items-center gap-2 text-xl font-black text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Breakdown-Analyse (90 Tage)
                        </h3>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="group overflow-hidden rounded-2xl bg-white p-6 shadow-lg transition-all hover:scale-105 hover:shadow-2xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-3xl font-black text-purple-600">
                                            {{ statistics.breakdown_analysis.total_breakdowns }}
                                        </div>
                                        <div class="mt-1 text-sm font-semibold text-gray-600">Breakdowns insgesamt</div>
                                    </div>
                                    <div class="rounded-xl bg-purple-100 p-3">
                                        <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="group overflow-hidden rounded-2xl bg-white p-6 shadow-lg transition-all hover:scale-105 hover:shadow-2xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-3xl font-black text-pink-600">
                                            {{ statistics.breakdown_analysis.percentage_triggered }}%
                                        </div>
                                        <div class="mt-1 text-sm font-semibold text-gray-600">Durch Kapazität ausgelöst</div>
                                    </div>
                                    <div class="rounded-xl bg-pink-100 p-3">
                                        <svg class="h-8 w-8 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="group overflow-hidden rounded-2xl bg-white p-6 shadow-lg transition-all hover:scale-105 hover:shadow-2xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-3xl font-black text-blue-600">
                                            {{ statistics.breakdown_analysis.avg_capacity_before_breakdown ?? 'N/A' }}%
                                        </div>
                                        <div class="mt-1 text-sm font-semibold text-gray-600">Ø Kapazität vor Breakdown</div>
                                    </div>
                                    <div class="rounded-xl bg-blue-100 p-3">
                                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- State Distribution -->
                <div class="overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-black/5">
                    <div class="bg-gradient-to-r from-purple-50 via-pink-50 to-blue-50 p-6">
                        <h3 class="flex items-center gap-2 text-xl font-black text-gray-900">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                            Verteilung der Zustände
                        </h3>
                    </div>
                    <div class="p-6">
                        <div v-if="statistics.total_entries > 0" class="flex justify-center">
                            <VueApexCharts
                                type="donut"
                                :options="chartOptions"
                                :series="stateBreakdownSeries"
                                width="450"
                            />
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-16">
                            <div class="rounded-full bg-gradient-to-br from-purple-100 to-pink-100 p-6">
                                <svg class="h-12 w-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                </svg>
                            </div>
                            <p class="mt-4 text-center font-semibold text-gray-500">Keine Daten verfügbar</p>
                            <p class="mt-1 text-center text-sm text-gray-400">Füge Einträge im Kalender hinzu</p>
                        </div>
                    </div>
                </div>

                <!-- Capacity Forecast -->
                <!-- <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 shadow-xl ring-1 ring-black/5">
                    <div class="p-6">
                        <h3 class="mb-6 flex items-center gap-2 text-xl font-black text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Kapazitätsprognose (7 Tage)
                        </h3>
                        <div class="space-y-3">
                            <div
                                v-for="forecast in statistics.capacity_forecast"
                                :key="forecast.date"
                                class="group flex items-center justify-between overflow-hidden rounded-2xl bg-white p-4 shadow-lg transition-all hover:scale-105 hover:shadow-2xl"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="rounded-xl bg-gradient-to-br from-purple-100 to-blue-100 p-3">
                                        <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="text-sm font-bold text-gray-700">
                                        {{ new Date(forecast.date).toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit' }) }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="text-2xl font-black text-gray-900">
                                        {{ forecast.projected_capacity }}%
                                    </div>
                                    <span
                                        :class="getRiskColor(forecast.risk_level)"
                                        class="rounded-full px-4 py-1.5 text-xs font-bold shadow-sm"
                                    >
                                        {{ getRiskLabel(forecast.risk_level) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </AuthenticatedLayout>
</template>
