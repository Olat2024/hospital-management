<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    id: { type: String, default: 'patient_id' },
    modelValue: { type: [String, Number], default: '' },
    patients: { type: Array, default: () => [] },
    gender: { type: String, default: '' },
    placeholder: { type: String, default: 'Choose a patient...' },
    emptyText: { type: String, default: 'Register a patient first.' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    showMrn: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'select']);

const query = ref('');
const remoteResults = ref(null);
const searching = ref(false);
let searchTimer = null;

watch(query, (value) => {
    clearTimeout(searchTimer);
    const trimmed = value.trim();

    if (trimmed === '') {
        remoteResults.value = null;
        searching.value = false;
        return;
    }

    searchTimer = setTimeout(async () => {
        searching.value = true;
        try {
            const { data } = await window.axios.get(route('patients.search'), {
                params: {
                    q: trimmed,
                    gender: props.gender || undefined,
                },
            });
            remoteResults.value = Array.isArray(data) ? data : [];
        } catch {
            // Fall back to client-side filtering of the preloaded list on transient errors.
            const lower = trimmed.toLowerCase();
            remoteResults.value = props.patients.filter((patient) =>
                `${patient.first_name} ${patient.last_name} ${patient.mrn || ''}`
                    .toLowerCase()
                    .includes(lower)
            );
        } finally {
            searching.value = false;
        }
    }, 250);
});

const allKnownPatients = computed(() => {
    const map = new Map();
    for (const patient of props.patients) {
        map.set(Number(patient.id), patient);
    }
    if (Array.isArray(remoteResults.value)) {
        for (const patient of remoteResults.value) {
            map.set(Number(patient.id), patient);
        }
    }
    return map;
});

const options = computed(() => {
    const base = remoteResults.value !== null ? remoteResults.value : props.patients;
    const selectedId = Number(props.modelValue);
    if (!selectedId || base.some((p) => Number(p.id) === selectedId)) {
        return base;
    }
    const selected = allKnownPatients.value.get(selectedId);
    return selected ? [selected, ...base] : base;
});

const onChange = (event) => {
    const value = event.target.value;
    emit('update:modelValue', value);
    emit('select', allKnownPatients.value.get(Number(value)) || null);
};
</script>

<template>
    <div>
        <div class="input-group input-group-sm mb-2">
            <span class="input-group-text bg-white"><i class="bi bi-search" /></span>
            <input
                v-model="query"
                type="search"
                class="form-control"
                placeholder="Search patient by name, MRN, or phone..."
                :aria-label="`Search ${id}`"
            >
            <span v-if="searching" class="input-group-text bg-white">
                <span class="spinner-border spinner-border-sm" aria-hidden="true" />
            </span>
        </div>
        <select
            :id="id"
            :value="modelValue"
            class="form-select"
            :class="{ 'is-invalid': Boolean(error) }"
            :required="required"
            @change="onChange"
        >
            <option value="" disabled>{{ placeholder }}</option>
            <option v-for="patient in options" :key="patient.id" :value="patient.id">
                {{ patient.last_name }}, {{ patient.first_name }}<template v-if="showMrn && patient.mrn"> · {{ patient.mrn }}</template>
            </option>
        </select>
        <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>
        <div v-if="patients.length === 0 && (!remoteResults || remoteResults.length === 0)" class="form-text text-danger">
            {{ emptyText }}
        </div>
    </div>
</template>
