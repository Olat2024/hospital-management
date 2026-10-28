import '../sass/app.scss';
import 'bootstrap';
import 'sweetalert2/dist/sweetalert2.min.css';
import 'vue-loading-overlay/dist/css/index.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, Fragment, h, ref } from 'vue';
import LoadingOverlay from 'vue-loading-overlay';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { showToast } from '@/Composables/useSweetAlert';

const configuredAppName = import.meta.env.VITE_APP_NAME;
const appName = configuredAppName && configuredAppName.toLowerCase() !== 'laravel'
    ? configuredAppName
    : 'Hospital Management';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const Root = {
            setup() {
                const isLoading = ref(false);

                router.on('start', () => {
                    isLoading.value = true;
                });
                router.on('finish', () => {
                    isLoading.value = false;
                });
                router.on('exception', (event) => {
                    isLoading.value = false;
                    showToast(
                        event.detail.exception?.message || 'The request could not be completed.',
                        'error',
                    );
                });
                router.on('cancel', () => {
                    isLoading.value = false;
                });
                router.on('error', (event) => {
                    const errors = Object.values(event.detail.errors || {});
                    const firstError = errors[0];
                    showToast(
                        Array.isArray(firstError) ? firstError[0] : firstError || 'Please review the form and try again.',
                        'error',
                    );
                });

                return () => h(Fragment, null, [
                    h(App, props),
                    h(LoadingOverlay, {
                        active: isLoading.value,
                        canCancel: false,
                        isFullPage: true,
                        color: '#176b87',
                        loader: 'dots',
                        opacity: 0.35,
                        backgroundColor: '#f5f8fb',
                        zIndex: 3000,
                    }),
                ]);
            },
        };

        return createApp(Root)
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
