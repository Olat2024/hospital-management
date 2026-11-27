<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    paginator: { type: Object, required: true },
    ariaLabel: { type: String, default: 'Pagination' },
    showSummary: { type: Boolean, default: false },
    buttonMode: { type: Boolean, default: false },
    preserveScroll: { type: Boolean, default: true },
    preserveState: { type: Boolean, default: false },
});

const emit = defineEmits(['navigate']);

const formatLabel = (label) => String(label || '')
    .replace(/&laquo;/g, '«')
    .replace(/&raquo;/g, '»')
    .replace(/&hellip;/g, '…')
    .replace(/&amp;/g, '&');
</script>

<template>
    <div
        v-if="paginator && paginator.last_page > 1"
        class="d-flex flex-wrap align-items-center gap-2"
        :class="showSummary ? 'justify-content-between' : 'justify-content-center'"
    >
        <span v-if="showSummary" class="small text-muted">
            Showing {{ paginator.from }}–{{ paginator.to }} of {{ paginator.total }}
        </span>

        <div v-if="buttonMode" class="btn-group" role="group" :aria-label="ariaLabel">
            <button
                v-for="(link, index) in paginator.links"
                :key="index"
                type="button"
                class="btn btn-sm"
                :class="link.active ? 'btn-primary' : 'btn-outline-secondary'"
                :disabled="!link.url"
                @click="link.url && emit('navigate', link.url)"
            >
                {{ formatLabel(link.label) }}
            </button>
        </div>

        <nav v-else :aria-label="ariaLabel">
            <ul class="pagination pagination-sm mb-0 flex-wrap">
                <li
                    v-for="(link, index) in paginator.links"
                    :key="index"
                    class="page-item"
                    :class="{ active: link.active, disabled: !link.url }"
                >
                    <Link
                        v-if="link.url"
                        class="page-link"
                        :href="link.url"
                        :preserve-scroll="preserveScroll"
                        :preserve-state="preserveState"
                    >
                        {{ formatLabel(link.label) }}
                    </Link>
                    <span v-else class="page-link">{{ formatLabel(link.label) }}</span>
                </li>
            </ul>
        </nav>
    </div>
</template>
