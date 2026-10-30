<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    hospitalName: {
        type: String,
        default: 'CarePoint Hospital',
    },
    logoUrl: {
        type: String,
        default: null,
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Create account" />
    <div class="register-page">
        <aside class="register-aside">
            <Link href="/" class="register-brand">
                <img v-if="logoUrl" :src="logoUrl" :alt="hospitalName" class="register-brand-logo">
                <span v-else class="register-brand-mark"><i class="bi bi-heart-pulse-fill" /></span>
                <span>{{ hospitalName }}</span>
            </Link>

            <div class="register-aside-content">
                <div class="register-kicker"><span /> A BETTER WAY TO CARE</div>
                <h1>One team.<br>One connected<br><span>care journey.</span></h1>
                <p>Set up your account to join the people making every patient experience more coordinated.</p>

                <div class="register-art" aria-hidden="true">
                    <div class="register-art-orbit orbit-outer" />
                    <div class="register-art-orbit orbit-inner" />
                    <div class="register-art-cross"><i class="bi bi-plus-lg" /></div>
                    <div class="register-art-center"><i class="bi bi-heart-pulse-fill" /></div>
                    <div class="register-art-node node-patient"><i class="bi bi-person-heart" /></div>
                    <div class="register-art-node node-clinical"><i class="bi bi-clipboard2-pulse" /></div>
                    <div class="register-art-node node-team"><i class="bi bi-people" /></div>
                    <div class="register-art-note">
                        <i class="bi bi-shield-check" />
                        <span><strong>Care, in sync</strong><small>Connected and secure</small></span>
                    </div>
                    <span class="register-spark spark-one">✳</span>
                    <span class="register-spark spark-two">✦</span>
                </div>

                <div class="register-benefits">
                    <div><i class="bi bi-check2-circle" /> Patient-centered tools</div>
                    <div><i class="bi bi-check2-circle" /> One secure team workspace</div>
                </div>
            </div>

            <div class="register-aside-footer">Together, we make care feel more connected.</div>
        </aside>

        <main class="register-main">
            <div class="register-mobile-brand">
                <img v-if="logoUrl" :src="logoUrl" :alt="hospitalName" class="register-brand-logo">
                <span v-else class="register-brand-mark"><i class="bi bi-heart-pulse-fill" /></span>
                <span>{{ hospitalName }}</span>
            </div>

            <section class="register-card">
                <div class="register-eyebrow">STAFF ACCOUNT</div>
                <h2>Create your account</h2>
                <p class="register-intro">A few details and you’ll be ready to get started.</p>

                <form class="register-form" @submit.prevent="submit">
                    <div class="register-field">
                        <label for="name">Your name</label>
                        <div class="register-input" :class="{ 'is-invalid': form.errors.name }">
                            <i class="bi bi-person" />
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                name="name"
                                autocomplete="name"
                                placeholder="e.g. Amara Okafor"
                                autofocus
                                required
                            >
                        </div>
                        <div v-if="form.errors.name" class="register-error" role="alert">{{ form.errors.name }}</div>
                    </div>

                    <div class="register-field">
                        <label for="email">Work email</label>
                        <div class="register-input" :class="{ 'is-invalid': form.errors.email }">
                            <i class="bi bi-envelope" />
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                name="email"
                                autocomplete="username"
                                placeholder="you@hospital.com"
                                required
                            >
                        </div>
                        <div v-if="form.errors.email" class="register-error" role="alert">{{ form.errors.email }}</div>
                    </div>

                    <div class="register-field">
                        <label for="password">Create password</label>
                        <div class="register-input" :class="{ 'is-invalid': form.errors.password }">
                            <i class="bi bi-lock" />
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                placeholder="Choose a secure password"
                                required
                            >
                        </div>
                        <div v-if="form.errors.password" class="register-error" role="alert">{{ form.errors.password }}</div>
                    </div>

                    <div class="register-field">
                        <label for="password_confirmation">Confirm password</label>
                        <div class="register-input" :class="{ 'is-invalid': form.errors.password_confirmation }">
                            <i class="bi bi-shield-lock" />
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                placeholder="Enter your password again"
                                required
                            >
                        </div>
                        <div v-if="form.errors.password_confirmation" class="register-error" role="alert">{{ form.errors.password_confirmation }}</div>
                    </div>

                    <button class="register-submit" type="submit" :disabled="form.processing">
                        <span v-if="form.processing" class="register-spinner" aria-hidden="true" />
                        <span>{{ form.processing ? 'Creating your account…' : 'Create account' }}</span>
                        <i v-if="!form.processing" class="bi bi-arrow-right" />
                    </button>
                </form>

                <div class="register-security">
                    <span class="register-divider"><span>SECURE ACCESS</span></span>
                    <p><i class="bi bi-shield-lock" /> Your account and patient information are protected.</p>
                </div>

                <p class="register-signin">
                    Already have an account?
                    <Link :href="route('login')">Sign in</Link>
                </p>
            </section>

            <footer class="register-footer">
                <Link href="/" class="register-back"><i class="bi bi-arrow-left" /> Back to home</Link>
                <span>© {{ new Date().getFullYear() }} {{ hospitalName }}</span>
            </footer>
        </main>
    </div>
</template>

<style scoped>
.register-page {
    --register-ink: #193b42;
    --register-muted: #7b8d90;
    --register-teal: #167f78;
    display: grid;
    min-height: 100vh;
    grid-template-columns: minmax(360px, .92fr) 1.08fr;
    color: var(--register-ink);
    background: #fff;
    font-family: Figtree, ui-sans-serif, system-ui, sans-serif;
}

.register-aside {
    position: relative;
    display: flex;
    min-height: 100vh;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    padding: 34px clamp(28px, 5vw, 72px) 25px;
    color: #fff;
    background:
        radial-gradient(ellipse at 87% 24%, rgba(80, 177, 152, .28), transparent 30%),
        radial-gradient(ellipse at 14% 93%, rgba(27, 105, 107, .5), transparent 37%),
        linear-gradient(148deg, #173f49 0%, #14515a 52%, #176665 100%);
}

.register-aside::before,
.register-aside::after {
    position: absolute;
    border: 1px solid rgba(218, 246, 235, .09);
    border-radius: 50%;
    content: "";
    pointer-events: none;
}

.register-aside::before {
    top: 20%;
    right: -190px;
    width: 470px;
    height: 470px;
}

.register-aside::after {
    right: -115px;
    bottom: 4%;
    width: 300px;
    height: 300px;
}

.register-brand,
.register-mobile-brand {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 11px;
    color: #fff;
    font-size: 15px;
    font-weight: 750;
    text-decoration: none;
}

.register-brand-mark {
    display: grid;
    width: 40px;
    height: 40px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, .26);
    border-radius: 12px;
    color: #fff;
    background: rgba(255, 255, 255, .13);
    font-size: 19px;
}

.register-brand-logo {
    width: 42px;
    height: 42px;
    flex: 0 0 auto;
    border-radius: 10px;
    object-fit: contain;
}

.register-aside-content {
    position: relative;
    z-index: 1;
    width: min(100%, 520px);
    margin: 48px auto;
}

.register-kicker {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #a9d8c6;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .16em;
}

.register-kicker span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #81d1ae;
    box-shadow: 0 0 0 4px rgba(129, 209, 174, .13);
}

.register-aside-content h1 {
    margin: 20px 0 12px;
    color: #fff;
    font-size: clamp(38px, 4vw, 53px);
    font-weight: 750;
    letter-spacing: -.055em;
    line-height: 1.04;
}

.register-aside-content h1 span {
    color: #9cddc1;
}

.register-aside-content > p {
    max-width: 380px;
    margin: 0;
    color: #c1d9d4;
    font-size: 14px;
    line-height: 1.7;
}

.register-art {
    position: relative;
    width: min(100%, 410px);
    height: 235px;
    margin: 23px auto 15px;
}

.register-art-orbit {
    position: absolute;
    top: 50%;
    left: 50%;
    border: 1px solid rgba(207, 240, 224, .19);
    border-radius: 50%;
    transform: translate(-50%, -50%);
}

.orbit-outer {
    width: 226px;
    height: 226px;
}

.orbit-inner {
    width: 154px;
    height: 154px;
    border-style: dashed;
    border-color: rgba(207, 240, 224, .22);
}

.register-art-center,
.register-art-node {
    position: absolute;
    display: grid;
    place-items: center;
    border: 1px solid rgba(227, 255, 241, .3);
    border-radius: 50%;
    box-shadow: 0 15px 32px rgba(7, 37, 43, .22);
}

.register-art-center {
    top: 50%;
    left: 50%;
    width: 78px;
    height: 78px;
    color: #fff;
    background: linear-gradient(145deg, #44a894, #247c79);
    font-size: 29px;
    transform: translate(-50%, -50%);
}

.register-art-node {
    width: 48px;
    height: 48px;
    color: #23665f;
    background: #e4f5eb;
    font-size: 20px;
}

.node-patient { top: 19px; left: calc(50% - 24px); }
.node-clinical { bottom: 25px; left: 17%; }
.node-team { right: 16%; bottom: 25px; }

.register-art-cross {
    position: absolute;
    top: 32px;
    right: 20%;
    display: grid;
    width: 34px;
    height: 34px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, .2);
    border-radius: 11px;
    color: #fff;
    background: rgba(255, 255, 255, .12);
}

.register-art-note {
    position: absolute;
    right: 1%;
    bottom: 2px;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px 13px;
    border: 1px solid rgba(236, 255, 246, .2);
    border-radius: 12px;
    color: #8fe0bc;
    background: rgba(232, 255, 244, .12);
    box-shadow: 0 14px 30px rgba(12, 45, 50, .15);
    backdrop-filter: blur(8px);
}

.register-art-note > i { font-size: 20px; }
.register-art-note span { display: flex; flex-direction: column; }
.register-art-note strong { color: #e4f6ed; font-size: 11px; }
.register-art-note small { color: #b5d3c9; font-size: 9px; }

.register-spark {
    position: absolute;
    color: #d9c68e;
}

.spark-one { top: 20px; left: 19%; font-size: 18px; }
.spark-two { right: 13%; bottom: 64px; color: #91d6bb; font-size: 17px; }

.register-benefits {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    color: #d1e5df;
    font-size: 11px;
    font-weight: 600;
}

.register-benefits div {
    display: flex;
    align-items: center;
    gap: 7px;
}

.register-benefits i { color: #83d2b0; font-size: 14px; }

.register-aside-footer {
    position: relative;
    z-index: 1;
    color: #aac9c6;
    font-size: 11px;
}

.register-main {
    display: flex;
    min-height: 100vh;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 38px 28px 25px;
    background:
        radial-gradient(ellipse at 85% 4%, rgba(233, 246, 239, .5), transparent 30%),
        #fff;
}

.register-card {
    width: min(100%, 450px);
    margin: auto;
}

.register-eyebrow {
    color: var(--register-teal);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .18em;
}

.register-card h2 {
    margin: 12px 0 7px;
    color: #1c3e45;
    font-size: 34px;
    font-weight: 750;
    letter-spacing: -.055em;
}

.register-intro {
    margin: 0;
    color: var(--register-muted);
    font-size: 14px;
}

.register-form {
    display: flex;
    flex-direction: column;
    gap: 17px;
    margin-top: 29px;
}

.register-field label {
    display: block;
    margin-bottom: 7px;
    color: #29484d;
    font-size: 12px;
    font-weight: 700;
}

.register-input {
    display: flex;
    height: 47px;
    align-items: center;
    gap: 11px;
    padding: 0 14px;
    border: 1px solid #dce7e3;
    border-radius: 9px;
    color: #8ba09f;
    background: #fff;
    transition: border-color .2s ease, box-shadow .2s ease;
}

.register-input:focus-within {
    border-color: #71b3a0;
    box-shadow: 0 0 0 3px rgba(22, 127, 120, .1);
}

.register-input.is-invalid { border-color: #d47a74; }
.register-input > i { font-size: 16px; }

.register-input input {
    width: 100%;
    height: 100%;
    border: 0;
    outline: 0;
    color: #244349;
    background: transparent;
    font-family: inherit;
    font-size: 13px;
}

.register-input input::placeholder { color: #aab8b8; }

.register-error {
    margin-top: 6px;
    color: #b6413a;
    font-size: 11px;
}

.register-submit {
    display: flex;
    width: 100%;
    min-height: 50px;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 3px;
    border: 0;
    border-radius: 9px;
    color: #fff;
    background: var(--register-teal);
    box-shadow: 0 8px 17px rgba(22, 127, 120, .16);
    cursor: pointer;
    font-family: inherit;
    font-size: 13px;
    font-weight: 750;
    transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
}

.register-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    background: #116b68;
    box-shadow: 0 10px 20px rgba(22, 127, 120, .22);
}

.register-submit:disabled { cursor: wait; opacity: .75; }

.register-spinner {
    width: 15px;
    height: 15px;
    border: 2px solid rgba(255, 255, 255, .4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: register-spin .7s linear infinite;
}

@keyframes register-spin {
    to { transform: rotate(360deg); }
}

.register-divider {
    display: flex;
    align-items: center;
    gap: 13px;
    margin: 24px 0 14px;
    color: #a2b0b0;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .14em;
}

.register-divider::before,
.register-divider::after {
    height: 1px;
    flex: 1;
    background: #edf1ef;
    content: "";
}

.register-security p {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    margin: 0;
    color: #879899;
    font-size: 11px;
}

.register-security p i { color: #5b9c84; font-size: 14px; }

.register-signin {
    margin: 22px 0 0;
    color: #859395;
    font-size: 12px;
    text-align: center;
}

.register-signin a {
    margin-left: 4px;
    color: var(--register-teal);
    font-weight: 700;
    text-decoration: none;
}

.register-signin a:hover { text-decoration: underline; }

.register-footer {
    display: flex;
    width: min(100%, 480px);
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: #9aa8a8;
    font-size: 10px;
}

.register-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #6b8284;
    font-weight: 650;
    text-decoration: none;
}

.register-back:hover { color: var(--register-teal); }
.register-mobile-brand { display: none; }

@media (max-width: 920px) {
    .register-page { grid-template-columns: minmax(300px, .85fr) 1.15fr; }
    .register-aside { padding-right: 32px; padding-left: 32px; }
    .register-aside-content h1 { font-size: 42px; }
    .register-benefits { flex-direction: column; gap: 9px; }
}

@media (max-width: 700px) {
    .register-page {
        display: block;
        min-height: 100vh;
        background:
            radial-gradient(ellipse at 95% 0%, #eaf5ee 0, transparent 32%),
            #fff;
    }

    .register-aside { display: none; }
    .register-main { min-height: 100vh; padding: 26px 23px 20px; background: transparent; }
    .register-mobile-brand {
        display: flex;
        width: min(100%, 450px);
        margin: 0 auto 36px;
        color: #23434a;
        font-size: 15px;
    }

    .register-mobile-brand .register-brand-mark {
        border-color: #d9eae2;
        color: #fff;
        background: var(--register-teal);
    }

    .register-mobile-brand .register-brand-logo { width: 38px; height: 38px; }
    .register-card { margin: 0 auto auto; }
    .register-card h2 { font-size: 31px; }
    .register-form { margin-top: 25px; }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: .01ms !important;
        transition-duration: .01ms !important;
    }
}
</style>
