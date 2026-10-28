<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    hospitalName: {
        type: String,
        default: 'CarePoint Hospital',
    },
    tagline: {
        type: String,
        default: 'Compassionate care. Connected teams.',
    },
    logoUrl: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <Head title="Welcome" />
    <div class="welcome-page">
        <header class="welcome-nav">
            <Link href="/" class="brand-lockup" aria-label="Home">
                <img v-if="logoUrl" :src="logoUrl" :alt="hospitalName" class="brand-logo">
                <span v-else class="brand-mark" aria-hidden="true">
                    <i class="bi bi-heart-pulse-fill" />
                </span>
                <span class="brand-name">{{ hospitalName }}</span>
            </Link>

            <nav class="welcome-nav-actions" aria-label="Main navigation">
                <a href="#services" class="nav-text-link">Our services</a>
                <Link v-if="canLogin && $page.props.auth.user" :href="route('dashboard')" class="nav-login">
                    Go to dashboard <i class="bi bi-arrow-up-right" />
                </Link>
                <template v-else>
                    <Link v-if="canLogin" :href="route('login')" class="nav-text-link">Staff sign in</Link>
                    <Link v-if="canRegister" :href="route('register')" class="nav-register">Create account</Link>
                </template>
            </nav>
        </header>

        <main>
            <section class="welcome-hero">
                <div class="hero-copy">
                    <div class="eyebrow"><span class="eyebrow-dot" /> YOUR HEALTH, OUR PRIORITY</div>
                    <h1>Better care starts with <span>being connected.</span></h1>
                    <p class="hero-description">
                        {{ tagline }} We bring appointments, patient records, and care teams together
                        to make every visit a little easier.
                    </p>
                    <div class="hero-actions">
                        <Link v-if="canLogin" :href="route('login')" class="primary-action">
                            Access the staff portal <i class="bi bi-arrow-right" />
                        </Link>
                        <a href="#services" class="secondary-action">
                            Explore the platform <i class="bi bi-arrow-down" />
                        </a>
                    </div>
                    <div class="hero-note">
                        <div class="note-avatars" aria-hidden="true">
                            <span><i class="bi bi-person-fill" /></span>
                            <span><i class="bi bi-person-fill" /></span>
                            <span><i class="bi bi-person-fill" /></span>
                        </div>
                        <span>Care teams working together, for you.</span>
                    </div>
                </div>

                <div class="hero-art" aria-label="Illustration of connected hospital care">
                    <div class="art-orbit orbit-one" />
                    <div class="art-orbit orbit-two" />
                    <div class="art-sun"><i class="bi bi-plus-lg" /></div>
                    <div class="art-building">
                        <div class="building-top">
                            <div class="building-cross"><i class="bi bi-plus-lg" /></div>
                            <span>{{ hospitalName }}</span>
                        </div>
                        <div class="building-windows">
                            <i v-for="window in 12" :key="window" />
                        </div>
                        <div class="building-door"><i class="bi bi-door-open" /></div>
                    </div>
                    <div class="art-plant plant-left"><i class="bi bi-flower1" /></div>
                    <div class="art-plant plant-right"><i class="bi bi-flower1" /></div>
                    <div class="floating-card appointment-card">
                        <span class="card-icon calendar-icon"><i class="bi bi-calendar2-check" /></span>
                        <span><strong>Care, coordinated</strong><small>Appointments made simple</small></span>
                        <i class="bi bi-check-circle-fill card-check" />
                    </div>
                    <div class="floating-card care-card">
                        <span class="care-pulse"><i class="bi bi-heart-pulse" /></span>
                        <span><strong>Here for every step</strong><small>People-first healthcare</small></span>
                    </div>
                    <span class="art-sparkle sparkle-one">✳</span>
                    <span class="art-sparkle sparkle-two">✦</span>
                </div>
            </section>

            <section id="services" class="services-section">
                <div class="section-heading">
                    <div>
                        <div class="eyebrow">ONE PLACE, BETTER CARE</div>
                        <h2>Everything your care team needs.</h2>
                    </div>
                    <p>Thoughtful tools that help healthcare professionals spend less time on admin and more time with people.</p>
                </div>

                <div class="service-grid">
                    <article class="service-card">
                        <span class="service-icon patients-icon"><i class="bi bi-people" /></span>
                        <h3>Patient-first records</h3>
                        <p>Keep essential patient information and clinical notes easy for authorized care teams to find.</p>
                    </article>
                    <article class="service-card">
                        <span class="service-icon appointments-icon"><i class="bi bi-calendar2-week" /></span>
                        <h3>Smoother appointments</h3>
                        <p>Coordinate schedules, follow-ups, and visits from one clear, shared view.</p>
                    </article>
                    <article class="service-card">
                        <span class="service-icon pharmacy-icon"><i class="bi bi-capsule" /></span>
                        <h3>Connected services</h3>
                        <p>Bring pharmacy, billing, and hospital operations into the same care journey.</p>
                    </article>
                </div>
            </section>
        </main>

        <footer class="welcome-footer">
            <Link href="/" class="footer-brand">
                <span class="footer-mark"><i class="bi bi-heart-pulse-fill" /></span>
                {{ hospitalName }}
            </Link>
            <span>{{ tagline }}</span>
            <Link v-if="canLogin" :href="route('login')" class="footer-login">Staff portal <i class="bi bi-arrow-right" /></Link>
        </footer>
    </div>
</template>

<style scoped>
.welcome-page {
    --ink: #173a42;
    --muted: #71858a;
    --teal: #157f79;
    --teal-dark: #116b68;
    --mint: #e8f4ef;
    --line: #e8efec;
    min-height: 100vh;
    padding: 0 clamp(22px, 7vw, 108px);
    color: var(--ink);
    background:
        radial-gradient(ellipse at 86% 22%, rgba(221, 241, 231, .68), transparent 25%),
        #fbfcfa;
    font-family: Figtree, ui-sans-serif, system-ui, sans-serif;
}

.welcome-nav {
    display: flex;
    min-height: 88px;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--line);
}

.brand-lockup,
.welcome-nav-actions,
.hero-actions,
.hero-note,
.footer-brand {
    display: flex;
    align-items: center;
}

.brand-lockup {
    gap: 12px;
    color: var(--ink);
    font-size: 17px;
    font-weight: 750;
    letter-spacing: -.035em;
    text-decoration: none;
}

.brand-mark,
.footer-mark {
    display: grid;
    width: 40px;
    height: 40px;
    place-items: center;
    border-radius: 13px;
    color: #fff;
    background: var(--teal);
    font-size: 20px;
}

.brand-logo {
    width: 42px;
    height: 42px;
    object-fit: contain;
}

.welcome-nav-actions {
    gap: clamp(18px, 3vw, 38px);
}

.nav-text-link,
.nav-login {
    color: #50686e;
    font-size: 14px;
    font-weight: 650;
    text-decoration: none;
    transition: color .2s ease;
}

.nav-text-link:hover,
.nav-login:hover,
.footer-login:hover {
    color: var(--teal);
}

.nav-register {
    padding: 11px 18px;
    border: 1px solid #d5e5df;
    border-radius: 10px;
    color: var(--teal-dark);
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: background .2s ease, border-color .2s ease;
}

.nav-register:hover {
    border-color: #b7d7cc;
    background: #f0f8f4;
}

.welcome-hero {
    display: grid;
    min-height: 570px;
    grid-template-columns: 1fr 1fr;
    align-items: center;
    gap: 5vw;
    padding: 52px 0 68px;
}

.hero-copy {
    max-width: 580px;
    padding-bottom: 12px;
}

.eyebrow {
    display: flex;
    align-items: center;
    gap: 9px;
    color: var(--teal);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .16em;
}

.eyebrow-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #45ac91;
    box-shadow: 0 0 0 4px #e1f1e9;
}

.hero-copy h1 {
    margin: 24px 0 20px;
    color: #173a42;
    font-size: clamp(43px, 5.3vw, 70px);
    font-weight: 760;
    letter-spacing: -.065em;
    line-height: 1.06;
}

.hero-copy h1 span {
    color: var(--teal);
}

.hero-description {
    max-width: 490px;
    margin: 0;
    color: var(--muted);
    font-size: 17px;
    line-height: 1.8;
}

.hero-actions {
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 30px;
}

.primary-action,
.secondary-action {
    display: inline-flex;
    min-height: 50px;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 0 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
}

.primary-action {
    color: #fff;
    background: var(--teal);
    box-shadow: 0 8px 18px rgba(21, 127, 121, .16);
}

.primary-action:hover {
    transform: translateY(-2px);
    color: #fff;
    background: var(--teal-dark);
    box-shadow: 0 11px 22px rgba(21, 127, 121, .22);
}

.secondary-action {
    border: 1px solid #e1eae6;
    color: #466066;
    background: rgba(255, 255, 255, .75);
}

.secondary-action:hover {
    background: #fff;
}

.hero-note {
    gap: 13px;
    margin-top: 32px;
    color: #71858a;
    font-size: 13px;
    font-weight: 550;
}

.note-avatars {
    display: flex;
    padding-left: 3px;
}

.note-avatars span {
    display: grid;
    width: 30px;
    height: 30px;
    margin-left: -4px;
    place-items: center;
    border: 2px solid #fbfcfa;
    border-radius: 50%;
    color: #477d73;
    background: #dcece3;
    font-size: 13px;
}

.note-avatars span:nth-child(2) {
    color: #8c674e;
    background: #f1e2d4;
}

.note-avatars span:nth-child(3) {
    color: #557798;
    background: #dce8f0;
}

.hero-art {
    position: relative;
    display: grid;
    min-height: 440px;
    place-items: center;
    isolation: isolate;
}

.hero-art::before {
    position: absolute;
    z-index: -2;
    width: min(80%, 390px);
    aspect-ratio: 1;
    border: 1px solid #d9eae1;
    border-radius: 50%;
    background:
        radial-gradient(circle at 50% 43%, #edf6f0 0 46%, transparent 46.5%),
        radial-gradient(circle at 50% 50%, #f5faf6 0 63%, transparent 63.5%);
    content: "";
}

.art-orbit {
    position: absolute;
    z-index: -1;
    border: 1px solid #e3eee8;
    border-radius: 50%;
}

.orbit-one {
    width: min(95%, 465px);
    aspect-ratio: 1;
}

.orbit-two {
    width: min(110%, 535px);
    aspect-ratio: 1;
    border-style: dashed;
    opacity: .8;
}

.art-building {
    position: relative;
    width: min(66%, 278px);
    min-height: 265px;
    margin-top: 30px;
    padding: 22px 22px 0;
    overflow: hidden;
    border: 1px solid #d9e9e1;
    border-radius: 15px 15px 5px 5px;
    background: linear-gradient(145deg, #fff 0%, #f7fbf8 100%);
    box-shadow: 0 28px 55px rgba(52, 104, 87, .12);
}

.building-top {
    display: flex;
    align-items: center;
    gap: 9px;
    padding-bottom: 17px;
    border-bottom: 1px solid #e6efea;
    color: #56716f;
    font-size: 10px;
    font-weight: 700;
}

.building-cross {
    display: grid;
    width: 25px;
    height: 25px;
    place-items: center;
    border-radius: 8px;
    color: #fff;
    background: #e27670;
    font-size: 17px;
}

.building-windows {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px 10px;
    padding: 22px 10px 0;
}

.building-windows i {
    height: 31px;
    border: 1px solid #d5e9e0;
    border-radius: 6px;
    background: linear-gradient(145deg, #dff0e7, #edf6f1);
}

.building-windows i:nth-child(3n) {
    background: #d8ece6;
}

.building-door {
    position: absolute;
    bottom: 0;
    left: calc(50% - 24px);
    display: grid;
    width: 48px;
    height: 57px;
    place-items: center;
    border: 1px solid #cde2d8;
    border-bottom: 0;
    border-radius: 22px 22px 0 0;
    color: #4a9784;
    background: #e8f4ed;
    font-size: 22px;
}

.art-sun {
    position: absolute;
    top: 16%;
    right: 16%;
    display: grid;
    width: 51px;
    height: 51px;
    place-items: center;
    border: 1px solid #dcebe1;
    border-radius: 50%;
    color: #d69a58;
    background: #fffaf0;
    font-size: 21px;
}

.art-plant {
    position: absolute;
    bottom: 13%;
    color: #75a88d;
    font-size: 49px;
}

.plant-left {
    left: 12%;
    transform: rotate(-18deg);
}

.plant-right {
    right: 10%;
    color: #93b69b;
    font-size: 42px;
    transform: rotate(12deg);
}

.floating-card {
    position: absolute;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 13px 15px;
    border: 1px solid #e9f0ec;
    border-radius: 12px;
    background: rgba(255, 255, 255, .96);
    box-shadow: 0 13px 32px rgba(28, 73, 62, .11);
}

.floating-card strong,
.floating-card small {
    display: block;
    white-space: nowrap;
}

.floating-card strong {
    color: #25464c;
    font-size: 11px;
}

.floating-card small {
    margin-top: 3px;
    color: #819397;
    font-size: 9px;
}

.appointment-card {
    top: 23%;
    left: 0;
}

.care-card {
    right: 0;
    bottom: 18%;
}

.card-icon,
.care-pulse {
    display: grid;
    width: 36px;
    height: 36px;
    flex: 0 0 auto;
    place-items: center;
    border-radius: 10px;
    font-size: 16px;
}

.calendar-icon {
    color: #528b79;
    background: #e6f4eb;
}

.care-pulse {
    color: #d8746a;
    background: #fff0ed;
}

.card-check {
    color: #45a886;
    font-size: 16px;
}

.art-sparkle {
    position: absolute;
    color: #dfab68;
}

.sparkle-one {
    top: 19%;
    left: 18%;
    font-size: 17px;
}

.sparkle-two {
    right: 18%;
    bottom: 30%;
    color: #69a993;
    font-size: 20px;
}

.services-section {
    padding: 38px 0 66px;
    border-top: 1px solid var(--line);
}

.section-heading {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 30px;
    margin-bottom: 25px;
}

.section-heading h2 {
    margin: 9px 0 0;
    color: var(--ink);
    font-size: clamp(25px, 3vw, 34px);
    font-weight: 740;
    letter-spacing: -.045em;
}

.section-heading > p {
    max-width: 390px;
    margin: 0 0 3px;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.7;
}

.service-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.service-card {
    padding: 24px 23px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: rgba(255, 255, 255, .82);
    transition: transform .2s ease, box-shadow .2s ease;
}

.service-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgba(31, 77, 65, .07);
}

.service-icon {
    display: grid;
    width: 43px;
    height: 43px;
    place-items: center;
    border-radius: 12px;
    font-size: 19px;
}

.patients-icon {
    color: #397f78;
    background: #e5f2ec;
}

.appointments-icon {
    color: #6684a3;
    background: #eaf0f6;
}

.pharmacy-icon {
    color: #bd8061;
    background: #fbefe6;
}

.service-card h3 {
    margin: 17px 0 8px;
    color: #24434a;
    font-size: 16px;
    font-weight: 720;
}

.service-card p {
    margin: 0;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
}

.welcome-footer {
    display: flex;
    min-height: 74px;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    border-top: 1px solid var(--line);
    color: #87979a;
    font-size: 12px;
}

.footer-brand {
    gap: 8px;
    color: #3a5b60;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
}

.footer-mark {
    width: 25px;
    height: 25px;
    border-radius: 8px;
    font-size: 13px;
}

.footer-login {
    color: #526e72;
    font-weight: 700;
    text-decoration: none;
}

@media (max-width: 850px) {
    .welcome-page {
        padding-right: 28px;
        padding-left: 28px;
    }

    .welcome-hero {
        min-height: initial;
        grid-template-columns: 1fr;
        gap: 12px;
        padding-top: 62px;
    }

    .hero-copy {
        max-width: 680px;
    }

    .hero-art {
        min-height: 410px;
    }

    .section-heading {
        align-items: start;
        flex-direction: column;
        gap: 12px;
    }
}

@media (max-width: 600px) {
    .welcome-page {
        padding-right: 20px;
        padding-left: 20px;
    }

    .welcome-nav {
        min-height: 72px;
    }

    .brand-lockup {
        gap: 8px;
        font-size: 14px;
    }

    .brand-mark {
        width: 34px;
        height: 34px;
        border-radius: 11px;
    }

    .welcome-nav-actions {
        gap: 12px;
    }

    .welcome-nav-actions .nav-text-link:first-child {
        display: none;
    }

    .nav-register {
        padding: 9px 11px;
        font-size: 12px;
    }

    .nav-text-link,
    .nav-login {
        font-size: 12px;
    }

    .welcome-hero {
        padding: 48px 0 40px;
    }

    .hero-copy h1 {
        margin-top: 18px;
        font-size: clamp(41px, 12vw, 57px);
    }

    .hero-description {
        font-size: 15px;
    }

    .hero-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .primary-action,
    .secondary-action {
        width: 100%;
    }

    .hero-art {
        min-height: 355px;
        margin: 0 -12px;
        transform: scale(.9);
    }

    .appointment-card {
        left: -1%;
    }

    .care-card {
        right: -1%;
    }

    .floating-card {
        gap: 7px;
        padding: 10px;
    }

    .floating-card strong {
        font-size: 10px;
    }

    .floating-card small {
        font-size: 8px;
    }

    .service-grid {
        grid-template-columns: 1fr;
    }

    .service-card {
        padding: 20px;
    }

    .welcome-footer {
        min-height: 70px;
        flex-wrap: wrap;
        padding: 14px 0;
    }

    .welcome-footer > span {
        display: none;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
    }
}
</style>
