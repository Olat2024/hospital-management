<!-- resources/js/Layouts/AppLayout.vue -->
<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { showToast } from '@/Composables/useSweetAlert';

const page = usePage();
const userRoles = computed(() => page.props.auth.roles || []);
const userPermissions = computed(() => page.props.auth.permissions || []);
const user = computed(() => page.props.auth.user);
const hospital = computed(() => page.props.hospital);
const hospitalLogoUrl = computed(() => page.props.hospital_logo_url);
const flash = computed(() => page.props.flash || {});
const isSidebarCollapsed = ref(localStorage.getItem('hospital-sidebar-collapsed') === 'true');
const isMobileSidebarOpen = ref(false);

const toggleSidebar = () => {
    if (window.matchMedia('(max-width: 767.98px)').matches) {
        isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
        return;
    }

    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem('hospital-sidebar-collapsed', String(isSidebarCollapsed.value));
};

watch(
    () => [flash.value.success, flash.value.error],
    ([success, error]) => {
        if (success) showToast(success, 'success');
        else if (error) showToast(error, 'error');
    },
    { immediate: true },
);

watch(() => page.url, () => {
    isMobileSidebarOpen.value = false;
});

// Helper function to check roles
const hasRole = (role) => userRoles.value.includes(role);
const hasPermission = (permission) => userPermissions.value.includes(permission) || hasRole('Admin');

const initials = computed(() => {
    const name = user.value?.name || '?';
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');
});
</script>

<template>
    <div class="app-shell d-flex" style="min-height: 100vh;">

        <!-- Sidebar Navigation -->
        <aside
            id="app-sidebar"
            class="app-sidebar bg-dark text-white p-3 d-flex flex-column"
            :class="{
                'sidebar-collapsed': isSidebarCollapsed,
                'sidebar-mobile-open': isMobileSidebarOpen,
            }"
            aria-label="Main navigation"
        >
            <!-- Hospital branding -->
            <div class="sidebar-brand mb-4 mt-2">
                <img
                    v-if="hospitalLogoUrl"
                    :src="hospitalLogoUrl"
                    :alt="hospital?.name"
                    class="sidebar-brand-logo rounded-circle"
                >
                <div
                    v-else
                    class="sidebar-brand-logo sidebar-brand-fallback rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                >
                    HS
                </div>
                <div class="sidebar-brand-name">{{ hospital?.name || 'Hospital System' }}</div>
            </div>

            <ul class="nav flex-column gap-2 mb-auto">
                <li class="nav-item">
                    <Link :href="route('dashboard')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Dashboard' : undefined" :class="{ 'bg-primary rounded': $page.url === '/dashboard' }">
                        <i class="bi bi-speedometer2 me-2"></i><span class="sidebar-label">Dashboard</span>
                    </Link>
                </li>

                <li class="nav-item" v-if="hasPermission('patients.view')">
                    <Link :href="route('patients.index')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'All Patients' : undefined" :class="{ 'bg-primary rounded': $page.url === '/patients' }">
                        <i class="bi bi-people me-2"></i><span class="sidebar-label">All Patients</span>
                    </Link>
                </li>

                <li class="nav-item" v-if="hasPermission('appointments.view')">
                    <Link :href="route('appointments.index')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Appointments' : undefined" :class="{ 'bg-primary rounded': $page.url.startsWith('/appointments') && !$page.url.startsWith('/appointments/create') }">
                        <i class="bi bi-calendar3 me-2"></i><span class="sidebar-label">Appointments</span>
                    </Link>
                </li>

                <li class="nav-item" v-if="hasPermission('nursing.view')">
                    <Link :href="route('nursing.index')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Nursing' : undefined" :class="{ 'bg-primary rounded': $page.url.startsWith('/nursing') }">
                        <i class="bi bi-heart-pulse me-2"></i><span class="sidebar-label">Nursing</span>
                    </Link>
                </li>

                <li class="nav-item" v-if="hasPermission('admissions.view')">
                    <Link :href="route('admissions.index')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Admissions' : undefined" :class="{ 'bg-primary rounded': $page.url.startsWith('/admissions') }">
                        <i class="bi bi-hospital me-2"></i><span class="sidebar-label">Admissions</span>
                    </Link>
                </li>

                <li class="nav-item" v-if="hasPermission('patients.create')">
                    <Link :href="route('patients.create')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Register Patient' : undefined" :class="{ 'bg-primary rounded': $page.url.startsWith('/patients/create') }">
                        <i class="bi bi-person-plus me-2"></i><span class="sidebar-label">Register Patient</span>
                    </Link>
                </li>

                <li class="nav-item" v-if="hasPermission('appointments.create')">
                    <Link :href="route('appointments.create')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Book Appointment' : undefined" :class="{ 'bg-primary rounded': $page.url.startsWith('/appointments/create') }">
                        <i class="bi bi-calendar-plus me-2"></i><span class="sidebar-label">Book Appointment</span>
                    </Link>
                </li>

                <li class="nav-item" v-if="hasPermission('billing.view')">
                    <Link :href="route('billing.index')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Billing' : undefined" :class="{ 'bg-primary rounded': $page.url.startsWith('/billing') }">
                        <i class="bi bi-receipt me-2"></i><span class="sidebar-label">Billing</span>
                    </Link>
                </li>
                <li class="nav-item" v-if="hasPermission('pharmacy.view')">
                    <Link :href="route('pharmacy.index')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Pharmacy' : undefined" :class="{ 'bg-primary rounded': $page.url.startsWith('/pharmacy') }">
                        <i class="bi bi-capsule me-2"></i><span class="sidebar-label">Pharmacy</span>
                    </Link>
                </li>

                <!-- Administration -->
                <li class="nav-item" v-if="hasRole('Admin')">
                    <div class="sidebar-section-label text-uppercase small text-white-50 px-2 mt-3 mb-1">Administration</div>
                </li>
                <li class="nav-item" v-if="hasPermission('doctors.manage')">
                    <Link :href="route('doctors.index')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Doctors' : undefined" :class="{ 'bg-primary rounded': $page.url.startsWith('/doctors') }">
                        <i class="bi bi-person-badge me-2"></i><span class="sidebar-label">Doctors</span>
                    </Link>
                </li>
                <li class="nav-item" v-if="hasPermission('users.manage')">
                    <Link :href="route('staff.index')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Staff' : undefined" :class="{ 'bg-primary rounded': $page.url.startsWith('/staff') }">
                        <i class="bi bi-person-gear me-2"></i><span class="sidebar-label">Staff</span>
                    </Link>
                </li>
                <li class="nav-item" v-if="hasPermission('hospital.manage')">
                    <Link :href="route('settings.hospital-setup.edit')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Hospital Setup' : undefined" :class="{ 'bg-primary rounded': $page.url.startsWith('/settings/hospital') }">
                        <i class="bi bi-building me-2"></i><span class="sidebar-label">Hospital Setup</span>
                    </Link>
                </li>
                <li class="nav-item" v-if="hasPermission('roles.manage')">
                    <Link :href="route('settings.roles')" class="nav-link text-white p-2" :title="isSidebarCollapsed ? 'Roles & Permissions' : undefined" :class="{ 'bg-primary rounded': $page.url.startsWith('/settings/roles') }">
                        <i class="bi bi-shield-lock me-2"></i><span class="sidebar-label">Roles &amp; Permissions</span>
                    </Link>
                </li>
            </ul>

            <hr class="text-white-50">

            <!-- User photo & Logout -->
            <div class="sidebar-user mt-2 d-flex align-items-center gap-2 px-1 mb-2">
                <img
                    v-if="user?.avatar_url"
                    :src="user.avatar_url"
                    :alt="user.name"
                    class="rounded-circle"
                    style="width: 40px; height: 40px; object-fit: cover;"
                >
                <div
                    v-else
                    class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold"
                    style="width: 40px; height: 40px;"
                >
                    {{ initials }}
                </div>
                <div class="sidebar-user-details flex-grow-1">
                    <div class="fw-bold text-truncate" style="max-width: 140px;">{{ user.name }}</div>
                    <Link :href="route('profile.edit')" class="small text-white-50 text-decoration-none">
                        My photo &amp; profile
                    </Link>
                </div>
            </div>
            <Link :href="route('logout')" method="post" as="button" class="sidebar-logout btn btn-outline-light btn-sm w-100" title="Log out">
                <i class="bi bi-box-arrow-right"></i><span class="sidebar-label ms-1">Log Out</span>
            </Link>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-grow-1 bg-light">
            <nav class="navbar navbar-light bg-white shadow-sm px-4 py-2 app-topbar">
                <div class="container-fluid d-flex justify-content-between align-items-center gap-3">
                    <button
                        type="button"
                        class="btn btn-link sidebar-toggle p-2"
                        :aria-label="isMobileSidebarOpen ? 'Close navigation menu' : isSidebarCollapsed ? 'Expand navigation menu' : 'Collapse navigation menu'"
                        aria-controls="app-sidebar"
                        :aria-expanded="isMobileSidebarOpen || !isSidebarCollapsed"
                        @click="toggleSidebar"
                    >
                        <i class="bi" :class="isMobileSidebarOpen ? 'bi-x-lg' : 'bi-list'"></i>
                    </button>
                    <Link :href="route('profile.edit')" class="text-decoration-none text-dark d-flex align-items-center gap-2">
                        <img
                            v-if="user?.avatar_url"
                            :src="user.avatar_url"
                            :alt="user.name"
                            class="rounded-circle"
                            style="width: 32px; height: 32px; object-fit: cover;"
                        >
                        <span
                            v-else
                            class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px; font-size: .8rem;"
                        >
                            {{ initials }}
                        </span>
                        <span>{{ user.name }}</span>
                    </Link>
                </div>
            </nav>

            <button
                v-if="isMobileSidebarOpen"
                type="button"
                class="sidebar-backdrop"
                aria-label="Close navigation menu"
                @click="isMobileSidebarOpen = false"
            ></button>

            <!-- Page Content Injection -->
            <main class="p-4">
                <slot />
            </main>
        </div>

    </div>
</template>

<style scoped>
.sidebar-brand {
    align-items: center;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    text-align: center;
}

.app-sidebar {
    flex: 0 0 260px;
    min-height: 100vh;
    overflow-x: hidden;
    transition: flex-basis 0.2s ease, width 0.2s ease, padding 0.2s ease;
    width: 260px;
}

.sidebar-collapsed.app-sidebar {
    flex-basis: 76px;
    padding-left: 0.75rem !important;
    padding-right: 0.75rem !important;
    width: 76px;
}

.sidebar-collapsed .sidebar-brand {
    margin-bottom: 1.25rem !important;
}

.sidebar-collapsed .sidebar-brand-logo {
    flex-basis: 44px;
    height: 44px;
    width: 44px;
}

.sidebar-collapsed .sidebar-brand-name,
.sidebar-collapsed .sidebar-label,
.sidebar-collapsed .sidebar-section-label,
.sidebar-collapsed .sidebar-user-details {
    display: none;
}

.sidebar-collapsed .nav-link {
    text-align: center;
}

.sidebar-collapsed .nav-link i {
    margin-right: 0 !important;
}

.sidebar-collapsed .sidebar-user {
    justify-content: center;
    padding-left: 0 !important;
    padding-right: 0 !important;
}

.sidebar-collapsed .sidebar-logout {
    padding-left: 0.3rem;
    padding-right: 0.3rem;
}

.app-topbar {
    min-height: 58px;
}

.sidebar-toggle {
    color: #176b87;
    font-size: 1.55rem;
    line-height: 1;
    text-decoration: none;
}

.sidebar-toggle:hover,
.sidebar-toggle:focus {
    color: #124e64;
}

.sidebar-backdrop {
    display: none;
}

.sidebar-brand-logo {
    border: 2px solid rgb(255 255 255 / 25%);
    flex: 0 0 72px;
    height: 72px;
    object-fit: cover;
    width: 72px;
}

.sidebar-brand-fallback {
    font-size: 1.6rem;
}

.sidebar-brand-name {
    align-self: stretch;
    font-size: 0.95rem;
    font-weight: 700;
    line-height: 1.35;
    overflow-wrap: anywhere;
    text-align: center;
}

/* Ensure smooth active state transitions */
.nav-link {
    transition: background-color 0.2s ease-in-out;
}
.nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

@media (max-width: 767.98px) {
    .app-sidebar {
        bottom: 0;
        flex-basis: 260px;
        left: 0;
        max-width: min(84vw, 290px);
        position: fixed;
        top: 0;
        transform: translateX(-100%);
        transition: transform 0.2s ease;
        width: 260px;
        z-index: 1080;
    }

    .app-sidebar.sidebar-mobile-open {
        transform: translateX(0);
    }

    .app-sidebar.sidebar-collapsed {
        flex-basis: 260px;
        padding: 1rem !important;
        width: 260px;
    }

    .app-sidebar.sidebar-collapsed .sidebar-brand {
        margin-bottom: 1.5rem !important;
    }

    .app-sidebar.sidebar-collapsed .sidebar-brand-logo {
        flex-basis: 72px;
        height: 72px;
        width: 72px;
    }

    .app-sidebar.sidebar-collapsed .sidebar-brand-name,
    .app-sidebar.sidebar-collapsed .sidebar-label,
    .app-sidebar.sidebar-collapsed .sidebar-section-label,
    .app-sidebar.sidebar-collapsed .sidebar-user-details {
        display: initial;
    }

    .app-sidebar.sidebar-collapsed .nav-link {
        text-align: left;
    }

    .app-sidebar.sidebar-collapsed .nav-link i {
        margin-right: 0.5rem !important;
    }

    .app-sidebar.sidebar-collapsed .sidebar-user {
        justify-content: flex-start;
        padding-left: 0.25rem !important;
        padding-right: 0.25rem !important;
    }

    .app-sidebar.sidebar-collapsed .sidebar-logout {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .sidebar-backdrop {
        background: rgb(18 32 48 / 45%);
        border: 0;
        display: block;
        inset: 0;
        position: fixed;
        z-index: 1070;
    }

    .app-topbar {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
}
</style>
