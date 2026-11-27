<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: { type: Number, required: true },
});

const copy = computed(() => {
    const map = {
        403: {
            eyebrow: 'Access restricted',
            title: 'You do not have permission to view this page.',
            description: 'If you believe you need access for your clinical or administrative role, contact a system administrator.',
            icon: 'bi-shield-lock',
        },
        404: {
            eyebrow: 'Page not found',
            title: 'We could not find that record or page.',
            description: 'The link may have moved, or the patient or record may belong to another scope.',
            icon: 'bi-compass',
        },
        503: {
            eyebrow: 'Maintenance',
            title: 'The hospital portal is temporarily unavailable.',
            description: 'Please check back in a few moments.',
            icon: 'bi-tools',
        },
    };

    return map[props.status] || {
        eyebrow: 'Unexpected error',
        title: 'Something went wrong while processing your request.',
        description: 'Our team has been notified. Please try again or return to the dashboard.',
        icon: 'bi-exclamation-triangle',
    };
});
</script>

<template>
    <Head :title="`${status} - Error`" />
    <div class="min-vh-100 d-flex align-items-center justify-content-center p-4" style="background: var(--hospital-canvas);">
        <div class="card shadow-sm text-center p-4 p-md-5" style="max-width: 500px; width: 100%;">
            <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle"
                 style="width: 64px; height: 64px; background: #eaf3f7; color: #176b87; font-size: 1.75rem;">
                <i class="bi" :class="copy.icon" />
            </div>
            <div class="app-page-eyebrow mb-1">{{ copy.eyebrow }} · {{ status }}</div>
            <h1 class="h4 fw-bold mb-2">{{ copy.title }}</h1>
            <p class="text-muted small mb-4">{{ copy.description }}</p>
            <div class="d-flex justify-content-center gap-2">
                <Link href="/dashboard" class="btn btn-primary">
                    <i class="bi bi-speedometer2 me-1" /> Dashboard
                </Link>
                <Link href="/" class="btn btn-outline-secondary">Home</Link>
            </div>
        </div>
    </div>
</template>
