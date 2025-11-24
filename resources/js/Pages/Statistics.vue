<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
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
    }
});

// Prepare data for state breakdown chart
const stateBreakdownLabels = computed(() => {
    return Object.values(props.statistics.state_breakdown).map(item => item.label);
});

const stateBreakdownSeries = computed(() => {
    return Object.values(props.statistics.state_breakdown).map(item => item.count);
});

const stateBreakdownColors = computed(() => {
    return Object.values(props.statistics.state_breakdown).map(item => item.color);
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
        fontSize: '14px'
    },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Tage insgesamt',
                        fontSize: '16px',
                        fontWeight: 600,
                    }
                }
            }
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function (val, opts) {
            return opts.w.config.series[opts.seriesIndex];
        }
    },
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                width: 300
            },
            legend: {
                position: 'bottom'
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
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Mental Health Statistiken
                </h2>
                <Link
                    href="/calendar"
                    class="rounded-lg bg-blue-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-600"
                >
                    Zum Kalender
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Einträge gesamt</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">
                            {{ statistics.total_entries }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">Diesen Monat</div>
                    </div>

                    <div class="overflow-hidden rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 shadow-sm">
                        <div class="text-sm font-medium text-blue-100">Aktuelle Kapazität</div>
                        <div class="mt-2 text-3xl font-bold text-white">
                            {{ statistics.current_capacity }}%
                        </div>
                        <div class="mt-1 text-xs text-blue-100">Mental Energy Level</div>
                    </div>

                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Tage seit Breakdown</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">
                            {{ statistics.days_since_last_breakdown ?? 'N/A' }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">Letzter "Im Loch" Tag</div>
                    </div>

                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Breakdowns diesen Monat</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">
                            {{ statistics.monthly_breakdown_count }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">"Im/Halb im Loch" Tage</div>
                    </div>
                </div>

                <!-- Capacity Timeline -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl">
                    <div class="p-6">
                        <h3 class="mb-6 text-lg font-semibold text-gray-900">
                            Kapazitätsverlauf diesen Monat
                        </h3>
                        <div v-if="statistics.capacity_timeline.length > 0">
                            <VueApexCharts
                                type="line"
                                :options="capacityChartOptions"
                                :series="capacitySeries"
                                height="350"
                            />
                        </div>
                        <div v-else class="py-12 text-center text-gray-500">
                            <p>Keine Daten verfügbar</p>
                        </div>
                    </div>
                </div>

                <!-- Breakdown Analysis -->
                <div class="overflow-hidden bg-gradient-to-br from-purple-50 to-indigo-50 shadow-sm sm:rounded-xl">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">
                            🔍 Breakdown-Analyse (90 Tage)
                        </h3>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="rounded-lg bg-white p-4">
                                <div class="text-2xl font-bold text-purple-600">
                                    {{ statistics.breakdown_analysis.total_breakdowns }}
                                </div>
                                <div class="text-sm text-gray-600">Breakdowns insgesamt</div>
                            </div>
                            <div class="rounded-lg bg-white p-4">
                                <div class="text-2xl font-bold text-indigo-600">
                                    {{ statistics.breakdown_analysis.percentage_triggered }}%
                                </div>
                                <div class="text-sm text-gray-600">Durch Kapazität ausgelöst</div>
                            </div>
                            <div class="rounded-lg bg-white p-4">
                                <div class="text-2xl font-bold text-blue-600">
                                    {{ statistics.breakdown_analysis.avg_capacity_before_breakdown ?? 'N/A' }}%
                                </div>
                                <div class="text-sm text-gray-600">Ø Kapazität vor Breakdown</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- State Distribution -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl">
                    <div class="p-6">
                        <h3 class="mb-6 text-lg font-semibold text-gray-900">
                            Verteilung der Zustände
                        </h3>
                        <div v-if="statistics.total_entries > 0" class="flex justify-center">
                            <VueApexCharts
                                type="donut"
                                :options="chartOptions"
                                :series="stateBreakdownSeries"
                                width="450"
                            />
                        </div>
                        <div v-else class="py-12 text-center text-gray-500">
                            <p>Keine Daten verfügbar</p>
                        </div>
                    </div>
                </div>

                <!-- Capacity Forecast -->
                <div class="overflow-hidden bg-gradient-to-br from-green-50 to-teal-50 shadow-sm sm:rounded-xl">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">
                            📊 Kapazitätsprognose (7 Tage)
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="forecast in statistics.capacity_forecast"
                                :key="forecast.date"
                                class="flex items-center justify-between rounded-lg bg-white p-3"
                            >
                                <div class="text-sm font-medium text-gray-700">
                                    {{ new Date(forecast.date).toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit' }) }}
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="text-lg font-bold text-gray-900">
                                        {{ forecast.projected_capacity }}%
                                    </div>
                                    <span
                                        :class="getRiskColor(forecast.risk_level)"
                                        class="rounded-full px-3 py-1 text-xs font-semibold"
                                    >
                                        {{ getRiskLabel(forecast.risk_level) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
