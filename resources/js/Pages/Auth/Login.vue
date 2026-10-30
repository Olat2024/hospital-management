<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    canRegister: Boolean,
    status: String,
    hospitalName: {
        type: String,
        default: 'Siloam Grace Clinic & Maternity Home',
    },
    logoUrl: {
        type: String,
        default: null,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Staff sign in" />
    <div class="login-page">
        <aside class="login-aside">
            <Link href="/" class="login-brand">
                <img v-if="logoUrl" :src="logoUrl" :alt="hospitalName" class="login-brand-logo">
                <span v-else class="login-brand-mark"><i class="bi bi-heart-pulse-fill" /></span>
                <span>{{ hospitalName }}</span>
            </Link>

            <div class="aside-content">
                <div class="aside-kicker"><span /> YOUR CARE TEAM, CONNECTED</div>
                <h1>Good care is a team effort.</h1>
                <p>One secure place for the people who make every patient visit matter.</p>

                <div class="login-illustration" aria-hidden="true">
                    <div class="illustration-halo" />
                    <div class="illustration-cross"><i class="bi bi-plus-lg" /></div>
                    <div class="illustration-person person-back">
                        <div class="person-head" />
                        <div class="person-body"><i class="bi bi-heart-pulse" /></div>
                    </div>
                    <div class="illustration-person person-front">
                        <div class="person-head" />
                        <div class="person-body"><i class="bi bi-person-heart" /></div>
                    </div>
                    <div class="illustration-card"><i class="bi bi-shield-check" /><span>Care, in sync</span></div>
                    <span class="illustration-spark spark-a">✳</span>
                    <span class="illustration-spark spark-b">✦</span>
                </div>

                <div class="aside-points">
                    <div><i class="bi bi-check2-circle" /> Patient-centered workflows</div>
                    <div><i class="bi bi-check2-circle" /> Connected hospital teams</div>
                </div>
            </div>

            <div class="aside-footer">A little more connected. A lot more caring.</div>
        </aside>

        <main class="login-main">
            <div class="login-mobile-brand">
                <img v-if="logoUrl" :src="logoUrl" :alt="hospitalName" class="login-brand-logo">
                <span v-else class="login-brand-mark"><i class="bi bi-heart-pulse-fill" /></span>
                <span>{{ hospitalName }}</span>
            </div>

            <section class="login-card">
                <div class="form-eyebrow">STAFF PORTAL</div>
                <h2>Welcome back</h2>
                <p class="form-intro">Sign in to continue to your workspace.</p>

                <div v-if="status" class="login-status" role="status">
                    <i class="bi bi-check-circle-fill" /> {{ status }}
                </div>

                <form class="login-form" @submit.prevent="submit">
                    <div class="field-group">
                        <label for="email">Work email</label>
                        <div class="input-wrap" :class="{ 'input-invalid': form.errors.email }">
                            <i class="bi bi-envelope" />
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                name="email"
                                autocomplete="username"
                                placeholder="you@hospital.com"
                                autofocus
                                required
                            >
                        </div>
                        <div v-if="form.errors.email" class="field-error" role="alert">{{ form.errors.email }}</div>
                    </div>

                    <div class="field-group">
                        <div class="password-label">
                            <label for="password">Password</label>
                            <Link v-if="canResetPassword" :href="route('password.request')" class="forgot-link">
                                Forgot password?
                            </Link>
                        </div>
                        <div class="input-wrap" :class="{ 'input-invalid': form.errors.password }">
                            <i class="bi bi-lock" />
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                name="password"
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                required
                            >
                        </div>
                        <div v-if="form.errors.password" class="field-error" role="alert">{{ form.errors.password }}</div>
                    </div>

                    <label class="remember-option">
                        <input v-model="form.remember" type="checkbox" name="remember">
                        <span class="remember-check"><i class="bi bi-check" /></span>
                        <span>Keep me signed in</span>
                    </label>

                    <button class="login-submit" type="submit" :disabled="form.processing">
                        <span v-if="form.processing" class="login-spinner" aria-hidden="true" />
                        <span>{{ form.processing ? 'Signing in…' : 'Sign in to your account' }}</span>
                        <i v-if="!form.processing" class="bi bi-arrow-right" />
                    </button>
                </form>

                <div class="login-divider"><span>SECURE ACCESS</span></div>
                <p class="security-note"><i class="bi bi-shield-lock" /> Your account and patient information are protected.</p>

                <p v-if="canRegister" class="register-prompt">
                    New to the team?
                    <Link :href="route('register')">Create an account</Link>
                </p>
            </section>

            <footer class="login-footer">
                <Link href="/" class="back-home"><i class="bi bi-arrow-left" /> Back to home</Link>
                <span>© {{ new Date().getFullYear() }} {{ hospitalName }}</span>
            </footer>
        </main>
    </div>
</template>

<style scoped>
.login-page {
    --ink: #193b42;
    --muted: #7b8d90;
    --teal: #167f78;
    display: grid;
    min-height: 100vh;
    grid-template-columns: minmax(360px, .92fr) 1.08fr;
    color: var(--ink);
    background: #fff;
    font-family: Figtree, ui-sans-serif, system-ui, sans-serif;
}

.login-aside {
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

.login-aside::before,
.login-aside::after {
    position: absolute;
    border: 1px solid rgba(218, 246, 235, .09);
    border-radius: 50%;
    content: "";
    pointer-events: none;
}

.login-aside::before {
    top: 20%;
    right: -190px;
    width: 470px;
    height: 470px;
}

.login-aside::after {
    right: -115px;
    bottom: -180px;
    width: 390px;
    height: 390px;
}

.login-brand {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    color: #fff;
    font-size: 16px;
    font-weight: 750;
    letter-spacing: -.025em;
    text-decoration: none;
}

.login-brand-mark {
    display: grid;
    width: 40px;
    height: 40px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, .18);
    border-radius: 13px;
    color: #fff;
    background: rgba(235, 255, 247, .12);
    font-size: 19px;
}

.login-brand-logo {
    width: 42px;
    height: 42px;
    object-fit: contain;
}

.aside-content {
    position: relative;
    z-index: 1;
    max-width: 470px;
    margin: 30px auto;
}

.aside-kicker {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #a7dbcb;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .16em;
}

.aside-kicker span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #72d3ad;
    box-shadow: 0 0 0 4px rgba(114, 211, 173, .13);
}

.aside-content h1 {
    max-width: 420px;
    margin: 22px 0 13px;
    color: #fff;
    font-size: clamp(38px, 4vw, 53px);
    font-weight: 740;
    letter-spacing: -.06em;
    line-height: 1.08;
}

.aside-content > p {
    max-width: 390px;
    margin: 0;
    color: #c1d9d7;
    font-size: 15px;
    line-height: 1.75;
}

.login-illustration {
    position: relative;
    display: flex;
    height: 260px;
    align-items: end;
    justify-content: center;
    margin: 11px 0 13px;
}

.illustration-halo {
    position: absolute;
    bottom: 9px;
    width: 250px;
    height: 220px;
    border: 1px solid rgba(207, 240, 223, .18);
    border-radius: 50% 50% 42% 42%;
    background: radial-gradient(ellipse at center, rgba(167, 224, 195, .14), rgba(208, 237, 218, .025) 70%);
}

.illustration-person {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.person-back {
    z-index: 0;
    margin: 0 -12px 0 0;
    transform: scale(.82);
}

.person-front {
    margin-left: -13px;
}

.person-head {
    z-index: 2;
    width: 56px;
    height: 62px;
    margin-bottom: -8px;
    border: 5px solid #e7c7a6;
    border-radius: 48% 48% 43% 43%;
    background: #805c50;
    box-shadow: inset 0 -18px 0 #e7c7a6;
}

.person-back .person-head {
    border-color: #bc9271;
    background: #3a3b3d;
    box-shadow: inset 0 -18px 0 #bc9271;
}

.person-body {
    display: grid;
    width: 110px;
    height: 133px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, .24);
    border-radius: 42px 42px 15px 15px;
    color: rgba(255, 255, 255, .92);
    background: linear-gradient(155deg, #fff 0%, #dceee5 100%);
    box-shadow: 0 13px 30px rgba(8, 41, 46, .22);
    font-size: 37px;
}

.person-back .person-body {
    width: 100px;
    color: #d7eee3;
    background: linear-gradient(155deg, #90b8a9, #427d77);
}

.illustration-cross {
    position: absolute;
    top: 20px;
    left: 17%;
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, .2);
    border-radius: 12px;
    color: #fff;
    background: rgba(255, 255, 255, .12);
    font-size: 21px;
}

.illustration-card {
    position: absolute;
    right: 2%;
    bottom: 35px;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 12px 14px;
    border: 1px solid rgba(236, 255, 246, .2);
    border-radius: 12px;
    color: #e4f6ed;
    background: rgba(232, 255, 244, .12);
    box-shadow: 0 14px 30px rgba(12, 45, 50, .15);
    font-size: 12px;
    font-weight: 700;
    backdrop-filter: blur(8px);
}

.illustration-card i {
    color: #8fe0bc;
    font-size: 19px;
}

.illustration-spark {
    position: absolute;
    color: #d9c68e;
}

.spark-a {
    top: 37px;
    right: 20%;
    font-size: 19px;
}

.spark-b {
    bottom: 101px;
    left: 13%;
    color: #91d6bb;
    font-size: 17px;
}

.aside-points {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    color: #d1e5df;
    font-size: 11px;
    font-weight: 600;
}

.aside-points div {
    display: flex;
    align-items: center;
    gap: 7px;
}

.aside-points i {
    color: #83d2b0;
    font-size: 14px;
}

.aside-footer {
    position: relative;
    z-index: 1;
    color: #aac9c6;
    font-size: 11px;
}

.login-main {
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

.login-card {
    width: min(100%, 410px);
    margin: auto;
}

.form-eyebrow {
    color: var(--teal);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .18em;
}

.login-card h2 {
    margin: 12px 0 7px;
    color: #1c3e45;
    font-size: 34px;
    font-weight: 750;
    letter-spacing: -.055em;
}

.form-intro {
    margin: 0;
    color: var(--muted);
    font-size: 14px;
}

.login-status {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-top: 23px;
    padding: 12px 14px;
    border: 1px solid #d5eee0;
    border-radius: 9px;
    color: #28725b;
    background: #f0faf4;
    font-size: 13px;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 34px;
}

.field-group label {
    display: block;
    margin-bottom: 8px;
    color: #29484d;
    font-size: 12px;
    font-weight: 700;
}

.input-wrap {
    display: flex;
    height: 49px;
    align-items: center;
    gap: 11px;
    padding: 0 14px;
    border: 1px solid #dce7e3;
    border-radius: 9px;
    color: #8ba09f;
    background: #fff;
    transition: border-color .2s ease, box-shadow .2s ease;
}

.input-wrap:focus-within {
    border-color: #71b3a0;
    box-shadow: 0 0 0 3px rgba(22, 127, 120, .1);
}

.input-wrap.input-invalid {
    border-color: #d47a74;
}

.input-wrap > i {
    font-size: 16px;
}

.input-wrap input {
    width: 100%;
    height: 100%;
    border: 0;
    outline: 0;
    color: #244349;
    background: transparent;
    font-family: inherit;
    font-size: 13px;
}

.input-wrap input::placeholder {
    color: #aab8b8;
}

.password-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.forgot-link {
    margin-bottom: 8px;
    color: var(--teal);
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
}

.forgot-link:hover {
    color: #105d59;
    text-decoration: underline;
}

.field-error {
    margin-top: 6px;
    color: #b6413a;
    font-size: 11px;
}

.remember-option {
    position: relative;
    display: inline-flex;
    width: fit-content;
    align-items: center;
    gap: 9px;
    color: #637a7d;
    cursor: pointer;
    font-size: 12px;
}

.remember-option input {
    position: absolute;
    width: 18px;
    height: 18px;
    margin: 0;
    opacity: 0;
}

.remember-check {
    display: grid;
    width: 17px;
    height: 17px;
    place-items: center;
    border: 1px solid #cddbd6;
    border-radius: 4px;
    color: transparent;
    background: #fff;
    font-size: 13px;
    transition: background .15s ease, border-color .15s ease;
}

.remember-option input:checked + .remember-check {
    border-color: var(--teal);
    color: #fff;
    background: var(--teal);
}

.remember-option input:focus-visible + .remember-check {
    outline: 3px solid rgba(22, 127, 120, .2);
    outline-offset: 2px;
}

.login-submit {
    display: flex;
    width: 100%;
    min-height: 50px;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border: 0;
    border-radius: 9px;
    color: #fff;
    background: var(--teal);
    box-shadow: 0 8px 17px rgba(22, 127, 120, .16);
    cursor: pointer;
    font-family: inherit;
    font-size: 13px;
    font-weight: 750;
    transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
}

.login-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    background: #116b68;
    box-shadow: 0 10px 20px rgba(22, 127, 120, .22);
}

.login-submit:disabled {
    cursor: wait;
    opacity: .75;
}

.login-spinner {
    width: 15px;
    height: 15px;
    border: 2px solid rgba(255, 255, 255, .4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: login-spin .7s linear infinite;
}

@keyframes login-spin {
    to { transform: rotate(360deg); }
}

.login-divider {
    display: flex;
    align-items: center;
    gap: 13px;
    margin: 25px 0 15px;
    color: #a2b0b0;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .14em;
}

.login-divider::before,
.login-divider::after {
    height: 1px;
    flex: 1;
    background: #edf1ef;
    content: "";
}

.security-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    margin: 0;
    color: #879899;
    font-size: 11px;
}

.security-note i {
    color: #5b9c84;
    font-size: 14px;
}

.register-prompt {
    margin: 26px 0 0;
    color: #859395;
    font-size: 12px;
    text-align: center;
}

.register-prompt a {
    margin-left: 4px;
    color: var(--teal);
    font-weight: 700;
    text-decoration: none;
}

.register-prompt a:hover {
    text-decoration: underline;
}

.login-footer {
    display: flex;
    width: min(100%, 480px);
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: #9aa8a8;
    font-size: 10px;
}

.back-home {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #6b8284;
    font-weight: 650;
    text-decoration: none;
}

.back-home:hover {
    color: var(--teal);
}

.login-mobile-brand {
    display: none;
}

@media (max-width: 920px) {
    .login-page {
        grid-template-columns: minmax(300px, .85fr) 1.15fr;
    }

    .login-aside {
        padding-right: 32px;
        padding-left: 32px;
    }

    .aside-content h1 {
        font-size: 42px;
    }

    .aside-points {
        flex-direction: column;
        gap: 9px;
    }
}

@media (max-width: 700px) {
    .login-page {
        display: block;
        min-height: 100vh;
        background:
            radial-gradient(ellipse at 95% 0%, #eaf5ee 0, transparent 32%),
            #fff;
    }

    .login-aside {
        display: none;
    }

    .login-main {
        min-height: 100vh;
        padding: 26px 23px 20px;
        background: transparent;
    }

    .login-mobile-brand {
        display: flex;
        width: min(100%, 410px);
        align-items: center;
        gap: 10px;
        margin: 0 auto 50px;
        color: #23434a;
        font-size: 15px;
        font-weight: 750;
    }

    .login-brand-mark {
        width: 36px;
        height: 36px;
        border-color: #d9eae2;
        color: #fff;
        background: var(--teal);
    }

    .login-brand-logo {
        width: 38px;
        height: 38px;
    }

    .login-card {
        margin: 0 auto auto;
    }

    .login-card h2 {
        font-size: 31px;
    }

    .login-form {
        margin-top: 28px;
    }
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
