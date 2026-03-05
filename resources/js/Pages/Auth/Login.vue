<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const showPassword = ref(false)

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
    <Head title="Log in" />

    <div class="login-shell">
        <div class="login-card">
    <!-- Left panel: branding -->
        <div class="brand-panel">
            <div class="brand-noise"></div>
            <div class="brand-content">
                <div class="brand-logo">
                    <svg viewBox="0 0 48 48" fill="none">
                        <rect width="48" height="48" rx="10" fill="#6B0000"/>
                        <text x="50%" y="56%" dominant-baseline="middle" text-anchor="middle"
                            font-family="serif" font-size="13" font-weight="900" fill="#FFFFFF"
                            letter-spacing="0.5">GUESS</text>
                    </svg>
                </div>
                <div class="brand-text">
                    <h1>STORE TIME IN/OUT</h1>
                    <p>Attendance Management</p>
                </div>
                <div class="brand-tagline">
                    <span>Track.</span>
                    <span>Monitor.</span>
                    <span>Manage.</span>
                </div>
            </div>
            <div class="brand-footer">
                <span>© 2026 Guess Philippines</span>
            </div>
        </div>

        <!-- Right panel: form -->
        <div class="form-panel">
            <div class="form-wrap">
                <div class="form-header">
                    <h2>Welcome back, Admin</h2>
                    <p>Please sign-in</p>
                </div>

                <div v-if="status" class="status-msg">{{ status }}</div>

                <form @submit.prevent="submit" class="login-form">
                    <div class="field-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrap" :class="{ error: form.errors.email }">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                placeholder="admin@example.com"
                                required
                                autofocus
                                autocomplete="username"
                            />
                        </div>
                        <span class="field-error" v-if="form.errors.email">{{ form.errors.email }}</span>
                    </div>

                    <div class="field-group">
                        <label for="password">Password</label>
                        <div class="input-wrap" :class="{ error: form.errors.password }">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            />
                            <button type="button" class="eye-btn" @click="showPassword = !showPassword" tabindex="-1">
                                <!-- Eye open -->
                                <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <!-- Eye closed -->
                                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <span class="field-error" v-if="form.errors.password">{{ form.errors.password }}</span>
                    </div>

                    <button type="submit" class="btn-login" :disabled="form.processing">
                        <span v-if="form.processing" class="spinner"></span>
                        <span v-else>Sign In</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap');

* { box-sizing: border-box; margin: 0; padding: 0; }

.login-card {
    display: flex;
    width: 100%;
    max-width: 820px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.12), 0 4px 16px rgba(0,0,0,0.08);
    overflow: hidden;
}

.login-shell {
    display: flex;
    min-height: 100vh;
    font-family: 'Sora', sans-serif;
    background: #f4f4f5;
    align-items: center;
    justify-content: center;
    padding: 32px;
}

/* ── Left brand panel ── */
.brand-panel {
    width: 340px;
    flex-shrink: 0;
    background: #3E0703;
    position: relative;
    display: flex;
    flex-direction: column;
    padding: 48px 36px;
    overflow: hidden;
    
}

/* Subtle noise texture overlay */
.brand-noise {
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
    opacity: 0.4;
    pointer-events: none;
}

/* Decorative circles */
.brand-panel::before {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(140,16,7,0.4) 0%, transparent 70%);
    top: -100px;
    right: -150px;
    pointer-events: none;
}
.brand-panel::after {
    content: '';
    position: absolute;
    width: 300px;
    height: 300px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(109,41,50,0.3) 0%, transparent 70%);
    bottom: -80px;
    left: -80px;
    pointer-events: none;
}

.brand-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 32px;
    position: relative;
    z-index: 1;
}

.brand-logo {
    display: flex;
    align-items: center;
    gap: 14px;
}
.brand-logo svg { width: 48px; height: 48px; }

.brand-text h1 {
    font-size: 22px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 2px;
    text-transform: uppercase;
}
.brand-text p {
    font-size: 11px;
    color: rgba(255,255,255,0.5);
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-top: 4px;
}

.brand-tagline {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.brand-tagline span {
    font-size: 36px;
    font-weight: 800;
    color: rgba(255,255,255,0.08);
    letter-spacing: -1px;
    line-height: 1;
}
.brand-tagline span:nth-child(2) { color: rgba(255,255,255,0.05); padding-left: 20px; }
.brand-tagline span:nth-child(3) { color: rgba(255,255,255,0.03); padding-left: 40px; }

.brand-footer {
    position: relative;
    z-index: 1;
    font-size: 11px;
    color: rgba(255,255,255,0.25);
}

/* ── Right form panel ── */
.form-panel {
    flex: 1;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 40px;
    
    border: 1px solid #e2e8f0;
    border-left: none;
}

.form-wrap {
    width: 100%;
    max-width: 400px;
}

.form-header {
    margin-bottom: 36px;
}
.form-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    letter-spacing: -0.5px;
}
.form-header p {
    font-size: 13px;
    color: #6b7280;
    margin-top: 6px;
}

.status-msg {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #86efac;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 20px;
}

.login-form { display: flex; flex-direction: column; gap: 20px; }

.field-group { display: flex; flex-direction: column; gap: 7px; }
.field-group label {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    letter-spacing: 0.3px;
}

.input-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.input-wrap:focus-within {
    border-color: #8C1007;
    box-shadow: 0 0 0 3px rgba(140,16,7,0.08);
}
.input-wrap.error { border-color: #dc2626; }
.input-wrap svg {
    width: 16px;
    height: 16px;
    color: #9ca3af;
    flex-shrink: 0;
}
.input-wrap input {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    padding: 12px 0;
    font-size: 13.5px;
    font-family: 'Sora', sans-serif;
    color: #1a1a1a;
}
.input-wrap input::placeholder { color: #bbb; }

.field-error { font-size: 11px; color: #dc2626; }
.eye-btn { background: none; border: none; cursor: pointer; padding: 4px; display: flex; color: #9ca3af; transition: color 0.18s; flex-shrink: 0; }
.eye-btn:hover { color: #6b7280; }
.eye-btn svg { width: 16px; height: 16px; }

.btn-login {
    margin-top: 8px;
    width: 100%;
    padding: 13px;
    background: #8C1007;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    font-family: 'Sora', sans-serif;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-login:hover:not(:disabled) { background: #6D2932; }
.btn-login:active:not(:disabled) { transform: scale(0.99); }
.btn-login:disabled { opacity: 0.5; cursor: not-allowed; }

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
    .login-card { flex-direction: column; max-width: 400px; }
    .brand-panel { width: 100%; padding: 32px 24px; min-height: 200px; }
    .brand-tagline { display: none; }
    .brand-content { justify-content: flex-start; gap: 16px; }
    .form-panel { border-left: 1px solid #e2e8f0; border-top: none; padding: 36px 28px; }
}
</style>