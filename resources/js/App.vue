<template>
    <!-- Every route owns its own full-screen layout:
         - auth/public routes use AuthLayout
         - the wizard uses Steps.vue, the dashboard uses Dashboard.vue -->
    <router-view />
</template>


<script setup>
// App shell only. Every route owns its own full-screen layout (AuthLayout / Steps / Dashboard),
// so there is no global chrome here — just <router-view /> plus the brand tokens in <style> below.
</script>
<style>
/*
 * Aktly Lite — global brand tokens (hex), consumed by legacy component CSS via var(--x).
 * Palette: Émeraude & Ardoise. Tailwind utilities use the HSL tokens in resources/css/app.css.
 */
:root {
    --main-color: #059669;       /* emerald-600 */
    --second-color: #64748b;     /* slate-500 */
    --primary: #059669;          /* emerald-600 */
    --primary-light: #d1fae5;    /* emerald-100 / mint */
    --primary-dark: #047857;     /* emerald-700 */
    --secondary: #0f766e;        /* teal-700 */
    --gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
    --success: #059669;
    --warning: #f59e0b;
    --danger: #ef4444;
    --error: #ef4444;
    --dark: #0f172a;             /* slate-900 */
    --light: #f8fafc;            /* slate-50 */
    --gray: #64748b;             /* slate-500 */
    --white: #ffffff;
    --shadow: 0 10px 30px rgba(2, 44, 34, 0.08);
    --transition: all 0.3s ease;
}
* {
    font-family: "Inter", sans-serif;
}
.gridiant {
    background: linear-gradient(160deg, #064e3b 0%, #059669 55%, #34d399 100%);
}
.progress-bar {
    background-color: #059669;
}
.progress {
    background-color: #e3e8f2;
}


::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #ffffff;
}

::-webkit-scrollbar-thumb {
    background: #10b981;
    border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
    background: #047857;
}


</style>
