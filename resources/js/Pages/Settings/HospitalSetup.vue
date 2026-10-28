<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    setup: { type: Object, required: true },
});

const form = useForm({
    // Branding
    logo: null,
    remove_logo: false,
    favicon: null,
    remove_favicon: false,
    primary_color: props.setup.primary_color || '#0d6efd',
    secondary_color: props.setup.secondary_color || '#6c757d',
    
    // Identity
    name: props.setup.name || '',
    tagline: props.setup.tagline || '',
    
    // Address
    address: props.setup.address || '',
    city: props.setup.city || '',
    state: props.setup.state || '',
    country: props.setup.country || 'Nigeria',
    postal_code: props.setup.postal_code || '',
    
    // Contact
    phone: props.setup.phone || '',
    email: props.setup.email || '',
    website: props.setup.website || '',
    
    // Social / Meta
    social_links: props.setup.social_links || {},
    footer_text: props.setup.footer_text || '',
    
    // Settings
    is_active: props.setup.is_active !== false,
    timezone: props.setup.timezone || 'Africa/Lagos',
    currency: props.setup.currency || 'NGN',
    locale: props.setup.locale || 'en',
});

const fileInput = ref(null);
const faviconInput = ref(null);

const logoPreview = ref(props.setup.logo_url || null);
const faviconPreview = ref(props.setup.favicon_url || null);

watch(
    () => form.logo,
    (file) => {
        if (file) {
            logoPreview.value = URL.createObjectURL(file);
            form.remove_logo = false;
        }
    }
);

watch(
    () => form.remove_logo,
    (remove) => {
        if (remove) {
            form.logo = null;
            logoPreview.value = null;
        }
    }
);

watch(
    () => form.favicon,
    (file) => {
        if (file) {
            faviconPreview.value = URL.createObjectURL(file);
            form.remove_favicon = false;
        }
    }
);

watch(
    () => form.remove_favicon,
    (remove) => {
        if (remove) {
            form.favicon = null;
            faviconPreview.value = null;
        }
    }
);

const initials = computed(() =>
    (props.setup.name || props.setup.hospital?.name || 'HS')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('')
);

const pickLogo = () => fileInput.value?.click();
const pickFavicon = () => faviconInput.value?.click();

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'put',
        remove_logo: data.remove_logo ? '1' : '0',
        remove_favicon: data.remove_favicon ? '1' : '0',
        is_active: data.is_active ? '1' : '0',
        social_links: typeof data.social_links === 'string'
            ? data.social_links
            : JSON.stringify(data.social_links || {}),
    })).post(route('settings.hospital-setup.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('logo', 'favicon');
            form.remove_logo = false;
            form.remove_favicon = false;
            logoPreview.value = props.setup.logo_url || null;
            faviconPreview.value = props.setup.favicon_url || null;
            if (fileInput.value) fileInput.value.value = '';
            if (faviconInput.value) faviconInput.value.value = '';
        },
    });
};
</script>

<template>
    <AppLayout>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="app-page-header">
                    <div class="app-page-header-content">
                    <div class="app-page-eyebrow">Administration</div>
                    <h1 class="app-page-title">Hospital Setup</h1>
                    <p class="app-page-description">
                        Complete branding, contact details, and system settings.
                    </p>
                    </div>
                </div>

                <!-- Branding -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-palette me-2"></i>Branding</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row">
                                <!-- Logo -->
                                <div class="col-md-4 text-center mb-4 mb-md-0">
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        alt="Hospital logo"
                                        class="rounded shadow"
                                        style="width: 160px; height: 160px; object-fit: cover;"
                                    >
                                    <div
                                        v-else
                                        class="rounded bg-secondary text-white mx-auto d-flex align-items-center justify-content-center shadow"
                                        style="width: 160px; height: 160px; font-size: 3rem;"
                                    >
                                        {{ initials }}
                                    </div>

                                    <input
                                        ref="fileInput"
                                        type="file"
                                        class="d-none"
                                        accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                        @change="form.logo = $event.target.files[0] || null"
                                    >
                                    <div class="d-grid gap-2 mt-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm" @click="pickLogo">
                                            <i class="bi bi-upload me-1"></i> Upload logo
                                        </button>
                                        <button
                                            v-if="setup.logo_url && !form.remove_logo"
                                            type="button"
                                            class="btn btn-outline-danger btn-sm"
                                            @click="form.remove_logo = true"
                                        >
                                            Remove logo
                                        </button>
                                    </div>
                                    <p class="text-danger small mt-2 mb-0" v-if="form.errors.logo">
                                        {{ form.errors.logo }}
                                    </p>
                                </div>

                                <!-- Favicon -->
                                <div class="col-md-4 text-center mb-4 mb-md-0">
                                    <img
                                        v-if="faviconPreview"
                                        :src="faviconPreview"
                                        alt="Favicon"
                                        class="rounded shadow"
                                        style="width: 80px; height: 80px; object-fit: cover;"
                                    >
                                    <div
                                        v-else
                                        class="rounded bg-secondary text-white mx-auto d-flex align-items-center justify-content-center shadow"
                                        style="width: 80px; height: 80px; font-size: 1.5rem;"
                                    >
                                        {{ initials }}
                                    </div>

                                    <input
                                        ref="faviconInput"
                                        type="file"
                                        class="d-none"
                                        accept="image/png,image/x-icon,image/svg+xml"
                                        @change="form.favicon = $event.target.files[0] || null"
                                    >
                                    <div class="d-grid gap-2 mt-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm" @click="pickFavicon">
                                            <i class="bi bi-upload me-1"></i> Upload favicon
                                        </button>
                                        <button
                                            v-if="setup.favicon_url && !form.remove_favicon"
                                            type="button"
                                            class="btn btn-outline-danger btn-sm"
                                            @click="form.remove_favicon = true"
                                        >
                                            Remove favicon
                                        </button>
                                    </div>
                                    <p class="text-danger small mt-2 mb-0" v-if="form.errors.favicon">
                                        {{ form.errors.favicon }}
                                    </p>
                                </div>

                                <!-- Colors -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="primary_color">Primary Color</label>
                                        <div class="input-group input-group-sm">
                                            <input
                                                id="primary_color"
                                                type="color"
                                                class="form-control form-control-color"
                                                v-model="form.primary_color"
                                                style="height: 40px; width: 60px;"
                                            >
                                            <input
                                                type="text"
                                                class="form-control"
                                                v-model="form.primary_color"
                                                placeholder="#0d6efd"
                                            >
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="secondary_color">Secondary Color</label>
                                        <div class="input-group input-group-sm">
                                            <input
                                                id="secondary_color"
                                                type="color"
                                                class="form-control form-control-color"
                                                v-model="form.secondary_color"
                                                style="height: 40px; width: 60px;"
                                            >
                                            <input
                                                type="text"
                                                class="form-control"
                                                v-model="form.secondary_color"
                                                placeholder="#6c757d"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Identity -->
                            <hr class="my-3">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="hs_name">Display Name</label>
                                    <input id="hs_name" type="text" class="form-control"
                                           v-model="form.name" placeholder="Shown in UI">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tagline">Tagline</label>
                                    <input id="tagline" type="text" class="form-control"
                                           v-model="form.tagline" placeholder="Your Health, Our Priority">
                                </div>
                            </div>

                            <!-- Address -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Address & Contact</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="hs_address">Street Address</label>
                                            <textarea id="hs_address" class="form-control" rows="2"
                                                      v-model="form.address"></textarea>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="city">City</label>
                                            <input id="city" type="text" class="form-control" v-model="form.city">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="state">State</label>
                                            <input id="state" type="text" class="form-control" v-model="form.state">
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label" for="country">Country</label>
                                            <input id="country" type="text" class="form-control" v-model="form.country">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="postal_code">Postal Code</label>
                                            <input id="postal_code" type="text" class="form-control" v-model="form.postal_code">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="hs_phone">Phone</label>
                                            <input id="hs_phone" type="text" class="form-control" v-model="form.phone">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="hs_email">Email</label>
                                            <input id="hs_email" type="email" class="form-control" v-model="form.email">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="website">Website</label>
                                        <input id="website" type="url" class="form-control" v-model="form.website"
                                               placeholder="https://example.com">
                                    </div>
                                </div>
                            </div>

                            <!-- Social Links -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="bi bi-share me-2"></i>Social Links (JSON)</h5>
                                </div>
                                <div class="card-body">
                                    <textarea class="form-control font-monospace small" rows="6"
                                              v-model="form.social_links"
                                              placeholder='{"facebook": "https://...", "twitter": "https://...", "linkedin": "https://...", "instagram": "https://..."}'></textarea>
                                    <div class="form-text">Valid JSON object with social media URLs.</div>
                                    <p class="text-danger small mt-2 mb-0" v-if="form.errors.social_links">
                                        {{ form.errors.social_links }}
                                    </p>
                                </div>
                            </div>

                            <!-- Footer Text -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Footer Text</h5>
                                </div>
                                <div class="card-body">
                                    <textarea class="form-control" rows="3"
                                              v-model="form.footer_text"
                                              placeholder="© 2026 Hospital Name. All rights reserved."></textarea>
                                </div>
                            </div>

                            <!-- System Settings -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="bi bi-gear me-2"></i>System Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label" for="timezone">Timezone</label>
                                            <select id="timezone" class="form-select" v-model="form.timezone">
                                                <option value="Africa/Lagos">Africa/Lagos (WAT)</option>
                                                <option value="UTC">UTC</option>
                                                <option value="Europe/London">Europe/London</option>
                                                <option value="America/New_York">America/New_York</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="currency">Currency</label>
                                            <input id="currency" type="text" class="form-control" v-model="form.currency"
                                                   maxlength="3" placeholder="NGN">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="locale">Locale</label>
                                            <input id="locale" type="text" class="form-control" v-model="form.locale"
                                                   maxlength="5" placeholder="en">
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check mt-4">
                                                <input id="is_active" type="checkbox" class="form-check-input"
                                                       v-model="form.is_active">
                                                <label class="form-check-label" for="is_active">Active</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Save setup
                                </button>
                                <span class="text-success align-self-center" v-if="form.recentlySuccessful">Saved.</span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>