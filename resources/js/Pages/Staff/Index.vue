<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmAction } from '@/Composables/useSweetAlert';

const props = defineProps({
    staff: Object,
    filters: Object,
});

const search = ref(props.filters.search);
const actionError = ref('');

const applySearch = () => router.get(route('staff.index'), {
    search: search.value || undefined,
}, { preserveState: true, replace: true });

const removeStaff = async (member) => {
    const confirmed = await confirmAction({
        title: `Delete ${member.name}'s account?`,
        text: 'This will permanently remove the staff account and cannot be undone.',
        confirmButtonText: 'Delete account',
    });
    if (!confirmed) return;

    actionError.value = '';
    router.delete(route('staff.destroy', member.id), {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = errors.staff || 'Unable to delete this staff account.';
        },
    });
};
</script>

<template>
    <AppLayout>
        <div class="app-page-header">
            <div class="app-page-header-content">
                <div class="app-page-eyebrow">People &amp; access</div>
                <h1 class="app-page-title">Staff</h1>
                <p class="app-page-description">Manage staff login accounts and roles.</p>
            </div>
            <div class="app-page-header-actions">
                <Link :href="route('staff.create')" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i> Add staff
                </Link>
            </div>
        </div>

        <div v-if="actionError" class="alert alert-danger" role="alert">{{ actionError }}</div>

        <div class="card shadow-sm">
            <div class="card-body border-bottom">
                <form class="row g-2" @submit.prevent="applySearch">
                    <div class="col-md-8">
                        <label for="staff-search" class="visually-hidden">Search staff</label>
                        <input
                            id="staff-search"
                            v-model="search"
                            class="form-control"
                            placeholder="Search by name, email, or role"
                        >
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">Search</button>
                        <button type="button" class="btn btn-outline-secondary" @click="search = ''; applySearch()">
                            Clear
                        </button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="member in staff.data" :key="member.id">
                            <td class="fw-semibold">{{ member.name }}</td>
                            <td>{{ member.email }}</td>
                            <td>
                                <span v-for="role in member.roles" :key="role.id" class="badge bg-light text-dark border me-1">
                                    {{ role.name }}
                                </span>
                                <span v-if="member.roles.length === 0" class="text-muted">No role assigned</span>
                            </td>
                            <td>
                                <span class="badge" :class="member.is_active ? 'bg-success' : 'bg-secondary'">
                                    {{ member.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div v-if="member.can_manage" class="btn-group btn-group-sm">
                                    <Link :href="route('staff.edit', member.id)" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </Link>
                                    <button
                                        v-if="member.can_delete"
                                        type="button"
                                        class="btn btn-outline-danger"
                                        @click="removeStaff(member)"
                                    >
                                        <i class="bi bi-trash me-1"></i>Delete
                                    </button>
                                    <span v-else class="btn btn-outline-secondary disabled" title="Own accounts or accounts with payment audit history cannot be deleted">
                                        Protected
                                    </span>
                                </div>
                                <span v-else class="small text-muted">Managed separately</span>
                            </td>
                        </tr>
                        <tr v-if="staff.data.length === 0">
                            <td colspan="5" class="text-center py-5 text-muted">No staff accounts found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="staff.last_page > 1" class="card-footer bg-white d-flex justify-content-between align-items-center">
                <span class="small text-muted">Showing {{ staff.from }}–{{ staff.to }} of {{ staff.total }}</span>
                <div class="d-flex gap-2">
                    <Link
                        v-if="staff.prev_page_url"
                        :href="staff.prev_page_url"
                        class="btn btn-sm btn-outline-secondary"
                    >Previous</Link>
                    <Link
                        v-if="staff.next_page_url"
                        :href="staff.next_page_url"
                        class="btn btn-sm btn-outline-secondary"
                    >Next</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
