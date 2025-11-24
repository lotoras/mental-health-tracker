<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 px-4 py-8 sm:px-6 lg:px-8"
    >
        <!-- Logo -->
        <div class="mb-8">
            <Link href="/">
                <ApplicationLogo class="h-24 w-24 text-indigo-600 transition-transform duration-300 hover:scale-110" />
            </Link>
        </div>

        <!-- App Title -->
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-gray-800 sm:text-3xl">Mental Health Tracker</h1>
            <p class="mt-1 text-sm text-gray-600">Your journey to better mental wellness</p>
        </div>

        <!-- Auth Card -->
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl bg-white px-6 py-8 shadow-xl sm:px-10 sm:py-10"
        >
            <slot />
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-xs text-gray-500">
                A safe space to track your mental health journey
            </p>
        </div>
    </div>
</template>
