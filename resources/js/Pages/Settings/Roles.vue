<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';
import { confirmAction } from '@/Composables/useSweetAlert';

const props = defineProps({
    roles: { type: Array, required: true },
    permissionGroups: { type: Array, required: true },
    permissionCount: { type: Number, default: 0 },
});

const allPermissionIds = computed(() =>
    props.permissionGroups.flatMap((group) => group.permissions.map((p) => p.id))
);

// One editable form per role, pre-filled with its current permissions.
const forms = reactive({});
props.roles.forEach((role) => {
    forms[role.id] = useForm({
        name: role.name,
        permissions: [...role.permissions.map((p) => p.id)],
    });
});

const isAdminRole = (role) => role.name === 'Admin';

const toggle = (roleId, permissionId) => {
    const perms = forms[roleId].permissions;
    const index = perms.indexOf(permissionId);
    if (index === -1) {
        perms.push(permissionId);
    } else {
        perms.splice(index, 1);
    }
};

const toggleGroup = (roleId, permissions, checked) => {
    const perms = forms[roleId].permissions;
    permissions.forEach((p) => {
        const index = perms.indexOf(p.id);
        if (checked && index === -1) perms.push(p.id);
        if (!checked && index !== -1) perms.splice(index, 1);
    });
};

const groupChecked = (roleId, permissions) =>
    permissions.every((p) => forms[roleId].permissions.includes(p.id));

const checkedCount = (roleId) => forms[roleId].permissions.length;

const save = (roleId) => {
    const role = props.roles.find((r) => r.id === roleId);
    forms[roleId].transform((data) => ({
        name: data.name,
        permissions: data.permissions,
    }))
        .put(route('settings.roles.update', role), {
            preserveScroll: true,
        });
};

const destroyForm = useForm({});

const destroy = async (role) => {
    const confirmed = await confirmAction({
        title: `Delete the "${role.name}" role?`,
        text: 'Users keep their accounts but lose this role.',
        confirmButtonText: 'Delete role',
    });

    if (confirmed) {
        destroyForm.delete(route('settings.roles.destroy', role), {
            preserveScroll: true,
        });
    }
};

const newRole = useForm({
    name: '',
    permissions: [],
});

const storeRole = () => {
    newRole.post(route('settings.roles.store'), {
        preserveScroll: true,
        onSuccess: () => newRole.reset(),
    });
};

const newPermission = useForm({ name: '' });

const storePermission = () => {
    newPermission.post(route('settings.permissions.store'), {
        preserveScroll: true,
        onSuccess: () => newPermission.reset(),
    });
};
</script>

<template>
    <AppLayout>
        <div class="app-page-header">
            <div class="app-page-header-content">
                <div class="app-page-eyebrow">Administration</div>
                <h1 class="app-page-title">Roles &amp; Permissions</h1>
                <p class="app-page-description">
                    Access control list — {{ permissionCount }} permissions across {{ roles.length }} roles.
                </p>
            </div>

            <!-- Add a role -->
            <form class="app-page-header-actions" @submit.prevent="storeRole">
                <input
                    type="text"
                    class="form-control"
                    style="width: 220px;"
                    placeholder="New role name"
                    :class="{ 'is-invalid': newRole.errors.name }"
                    v-model="newRole.name"
                >
                <button class="btn btn-success" type="submit" :disabled="newRole.processing">
                    Add role
                </button>
                <div class="invalid-feedback d-block" v-if="newRole.errors.name">
                    {{ newRole.errors.name }}
                </div>
            </form>
        </div>

        <!-- Permission matrix -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Permission matrix</h5>
                <span class="text-muted small">Tick what each role is allowed to do</span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 260px;">Permission</th>
                            <th
                                v-for="role in roles"
                                :key="role.id"
                                class="text-center"
                                style="min-width: 160px;"
                            >
                                <div class="fw-bold">{{ role.name }}</div>
                                <div class="small text-muted fw-normal">
                                    {{ role.users_count }} user{{ role.users_count === 1 ? '' : 's' }}
                                </div>
                            </th>
                            <th class="text-center" style="width: 110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="group in permissionGroups" :key="group.group">
                            <tr class="table-secondary-subtle">
                                <td colspan="1" class="text-uppercase small fw-bold text-muted">
                                    {{ group.group }}
                                </td>
                                <td
                                    v-for="role in roles"
                                    :key="'g' + role.id"
                                    class="text-center"
                                >
                                    <div class="form-check d-inline-block m-0">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            :checked="groupChecked(role.id, group.permissions)"
                                            :disabled="isAdminRole(role)"
                                            @change="
                                                toggleGroup(
                                                    role.id,
                                                    group.permissions,
                                                    $event.target.checked
                                                )
                                            "
                                        >
                                    </div>
                                </td>
                                <td></td>
                            </tr>

                            <tr v-for="permission in group.permissions" :key="permission.id">
                                <td class="ps-4">
                                    <code>{{ permission.name }}</code>
                                </td>
                                <td
                                    v-for="role in roles"
                                    :key="permission.id + '-' + role.id"
                                    class="text-center"
                                >
                                    <div class="form-check d-inline-block m-0">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            :checked="
                                                isAdminRole(role) ||
                                                forms[role.id].permissions.includes(permission.id)
                                            "
                                            :disabled="isAdminRole(role)"
                                            @change="toggle(role.id, permission.id)"
                                        >
                                    </div>
                                </td>
                                <td class="text-center text-muted small">—</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white d-flex flex-wrap gap-2">
                <template v-for="role in roles" :key="'save' + role.id">
                    <button
                        v-if="!isAdminRole(role)"
                        class="btn btn-sm btn-primary"
                        :disabled="forms[role.id].processing"
                        @click="save(role.id)"
                    >
                        <span
                            v-if="forms[role.id].processing"
                            class="spinner-border spinner-border-sm me-1"
                            role="status"
                        ></span>
                        Save {{ role.name }}
                        <span class="badge bg-light text-primary ms-1">
                            {{ checkedCount(role.id) }}
                        </span>
                    </button>
                    <span v-else class="badge bg-secondary align-self-center">
                        {{ role.name }} always has full access
                    </span>
                </template>
            </div>
        </div>

        <div class="row g-4">
            <!-- Add a custom permission -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Add a permission</h5>
                    </div>
                    <div class="card-body">
                        <form class="d-flex gap-2" @submit.prevent="storePermission">
                            <input
                                type="text"
                                class="form-control"
                                placeholder="resource.action (e.g. billing.manage)"
                                :class="{ 'is-invalid': newPermission.errors.name }"
                                v-model="newPermission.name"
                            >
                            <button class="btn btn-outline-success" type="submit" :disabled="newPermission.processing">
                                Add
                            </button>
                        </form>
                        <p class="text-danger small mt-2 mb-0" v-if="newPermission.errors.name">
                            {{ newPermission.errors.name }}
                        </p>
                        <p class="text-muted small mt-2 mb-0">
                            Format: lowercase resource, dot, action — then tick it for any role above.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Role list -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Roles</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li
                            v-for="role in roles"
                            :key="'list' + role.id"
                            class="list-group-item d-flex align-items-center justify-content-between"
                        >
                            <div>
                                <div class="fw-bold">{{ role.name }}</div>
                                <small class="text-muted">
                                    {{ role.users_count }} user{{ role.users_count === 1 ? '' : 's' }}
                                    · {{ isAdminRole(role) ? 'all permissions' : role.permissions.length + ' permissions' }}
                                </small>
                            </div>
                            <button
                                v-if="!isAdminRole(role)"
                                class="btn btn-sm btn-outline-danger"
                                @click="destroy(role)"
                            >
                                Delete
                            </button>
                            <span v-else class="badge bg-secondary">system</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
