<style>
    :root.dark {
        --gh-bg: #13131b;
        --gh-topbar: #1a1a24;
        --gh-surface: #171720;
        --gh-surface-2: #1d1d2a;
        --gh-border: #303040;
        --gh-primary: #8234e9;
        --gh-text: #e0e0ee;
        --gh-muted: #9898a7;
        --gh-badge-bg: #002f27;
        --gh-badge-text: #29d57b;
        --gh-danger: #ef4444;
        --gh-warning: #f59e0b;
        --gh-success: #22c55e;
    }

    html.dark body.fi-body {
        background: var(--gh-bg) !important;
        color: var(--gh-text) !important;
    }

    html.dark .fi-topbar nav {
        background: var(--gh-topbar) !important;
        box-shadow: none !important;
        border-bottom: 1px solid var(--gh-border) !important;
    }

    html.dark .fi-sidebar,
    html.dark .fi-sidebar-header {
        background: var(--gh-topbar) !important;
        border-right: 1px solid var(--gh-border) !important;
    }

    html.dark .fi-main {
        color: var(--gh-text) !important;
    }

    html.dark .fi-section,
    html.dark .fi-modal-window,
    html.dark .fi-ta-ctn,
    html.dark .fi-ta-content,
    html.dark .fi-fo-section,
    html.dark .fi-wi {
        background: var(--gh-surface) !important;
        border-color: var(--gh-border) !important;
    }

    html.dark .fi-dropdown-panel,
    html.dark .fi-modal-window,
    html.dark .fi-sidebar-nav,
    html.dark .fi-input-wrp {
        border-color: var(--gh-border) !important;
    }

    html.dark .fi-btn.fi-color-primary:not(.fi-btn-outlined) {
        background: var(--gh-primary) !important;
    }

    html.dark .fi-btn.fi-color-primary:not(.fi-btn-outlined):hover {
        filter: brightness(1.08);
    }

    html.dark .fi-btn.fi-color-gray:not(.fi-btn-outlined) {
        background: var(--gh-border) !important;
        color: var(--gh-text) !important;
        border-color: transparent !important;
    }

    html.dark .fi-btn.fi-color-gray:not(.fi-btn-outlined):hover {
        filter: brightness(1.08);
    }

    html.dark .fi-badge.fi-color-success {
        background: var(--gh-badge-bg) !important;
        color: var(--gh-badge-text) !important;
        border-color: rgba(41, 213, 123, 0.35) !important;
    }

    html.dark .fi-badge.fi-color-danger {
        background: rgba(239, 68, 68, 0.14) !important;
        color: #f87171 !important;
        border-color: rgba(239, 68, 68, 0.25) !important;
    }

    html.dark .fi-badge.fi-color-warning {
        background: rgba(245, 158, 11, 0.14) !important;
        color: #fbbf24 !important;
        border-color: rgba(245, 158, 11, 0.25) !important;
    }

    html.dark .fi-badge.fi-color-info {
        background: rgba(59, 130, 246, 0.14) !important;
        color: #93c5fd !important;
        border-color: rgba(59, 130, 246, 0.25) !important;
    }

    html.dark .text-gray-950,
    html.dark .dark\:text-white {
        color: var(--gh-text) !important;
    }

    html.dark .text-gray-600,
    html.dark .text-gray-500,
    html.dark .text-gray-400,
    html.dark .dark\:text-gray-400,
    html.dark .dark\:text-gray-300,
    html.dark .dark\:text-gray-200 {
        color: var(--gh-muted) !important;
    }

    html.dark .ring-gray-950\/5,
    html.dark .dark\:ring-white\/10,
    html.dark .dark\:ring-white\/20 {
        --tw-ring-color: rgba(48, 48, 64, 0.55) !important;
    }

    html.dark .border-gray-200,
    html.dark .border-gray-300,
    html.dark .dark\:border-gray-800,
    html.dark .dark\:border-gray-700 {
        border-color: var(--gh-border) !important;
    }

    html.dark .fi-main .bg-white {
        background-color: var(--gh-surface) !important;
    }

    html.dark .fi-main .bg-gray-50 {
        background-color: var(--gh-surface) !important;
    }

    html.dark .fi-main .bg-gray-100 {
        background-color: var(--gh-surface-2) !important;
    }

    html.dark .fi-main select,
    html.dark .fi-main input[type='text'],
    html.dark .fi-main input[type='email'],
    html.dark .fi-main input[type='password'],
    html.dark .fi-main textarea {
        background-color: var(--gh-surface-2) !important;
        color: var(--gh-text) !important;
        border-color: var(--gh-border) !important;
    }
</style>
