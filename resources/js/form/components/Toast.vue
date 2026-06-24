<template>
    <TransitionGroup
        name="toast"
        tag="div"
        class="toast-container"
    >
        <div
            v-for="toast in toasts"
            :key="toast.id"
            :class="['toast', `toast-${toast.type}`]"
        >
            <div class="toast-icon">
                <i :class="getIcon(toast.type)"></i>
            </div>
            <div class="toast-content">
                <p class="toast-message">{{ toast.message }}</p>
            </div>
            <button
                @click="removeToast(toast.id)"
                class="toast-close"
            >
                <i class="fas fa-times"></i>
            </button>
        </div>
    </TransitionGroup>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const toasts = ref([]);
let toastIdCounter = 0;

const getIcon = (type) => {
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle',
    };
    return icons[type] || icons.info;
};

const showToast = (message, type = 'info', duration = 5000) => {
    const id = ++toastIdCounter;
    const toast = {
        id,
        message,
        type,
    };
    
    toasts.value.push(toast);
    
    // Auto remove after duration
    if (duration > 0) {
        setTimeout(() => {
            removeToast(id);
        }, duration);
    }
    
    return id;
};

const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index > -1) {
        toasts.value.splice(index, 1);
    }
};

// Expose methods to parent
defineExpose({
    showToast,
    removeToast,
});

// Global toast functions
window.toast = {
    success: (message, duration) => showToast(message, 'success', duration),
    error: (message, duration) => showToast(message, 'error', duration),
    warning: (message, duration) => showToast(message, 'warning', duration),
    info: (message, duration) => showToast(message, 'info', duration),
};
</script>

<style scoped>
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 400px;
    pointer-events: none;
}

.toast {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    background: white;
    pointer-events: auto;
    min-width: 300px;
    animation: slideInRight 0.3s ease-out;
}

.toast-icon {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.toast-content {
    flex: 1;
    min-width: 0;
}

.toast-message {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    color: #1f2937;
    word-wrap: break-word;
}

.toast-close {
    flex-shrink: 0;
    background: transparent;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toast-close:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #374151;
}

/* Toast Types */
.toast-success {
    border-left: 4px solid #10b981;
}

.toast-success .toast-icon {
    color: #10b981;
}

.toast-error {
    border-left: 4px solid #ef4444;
}

.toast-error .toast-icon {
    color: #ef4444;
}

.toast-warning {
    border-left: 4px solid #f59e0b;
}

.toast-warning .toast-icon {
    color: #f59e0b;
}

.toast-info {
    border-left: 4px solid #3b82f6;
}

.toast-info .toast-icon {
    color: #3b82f6;
}

/* Animations */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.toast-enter-active {
    transition: all 0.3s ease-out;
}

.toast-leave-active {
    transition: all 0.3s ease-in;
}

.toast-enter-from {
    transform: translateX(100%);
    opacity: 0;
}

.toast-leave-to {
    transform: translateX(100%);
    opacity: 0;
}

/* Responsive */
@media (max-width: 640px) {
    .toast-container {
        right: 10px;
        left: 10px;
        max-width: none;
    }
    
    .toast {
        min-width: auto;
        width: 100%;
    }
}
</style>

