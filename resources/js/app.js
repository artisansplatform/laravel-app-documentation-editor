// Import styles
import '../css/app.css';
import '@toast-ui/editor/dist/toastui-editor.css';
import '@toast-ui/editor/dist/theme/toastui-editor-dark.css';
import * as Diff from 'diff';
import Editor from '@toast-ui/editor';
import Swal from 'sweetalert2';
import * as lucide from 'lucide';
import {
    ArrowLeft, BookOpen, ChevronRight, Edit2, Edit3, Eye, File, FileText, Folder,
    GitBranch, GitPullRequest, Menu, Moon, Pencil, Save, Search, Sun, TriangleAlert, X,
} from 'lucide';

// Make libraries available globally
window.Diff = Diff;
window.Editor = Editor;
window.Swal = Swal;

// Configure Lucide icons
const icons = {
    ArrowLeft, BookOpen, ChevronRight, Edit2, Edit3, Eye, File, FileText, Folder,
    GitBranch, GitPullRequest, Menu, Moon, Pencil, Save, Search, Sun, TriangleAlert, X,
};
window.createIcons = () => {
    lucide.createIcons({
        icons: icons
    });
};

/**
 * Theme
 * Follows the system preference until the user picks one explicitly.
 */
const THEME_STORAGE_KEY = 'laravel-app-documentation-editor-theme';
const systemDarkQuery = window.matchMedia('(prefers-color-scheme: dark)');

function readStoredTheme() {
    try {
        return localStorage.getItem(THEME_STORAGE_KEY);
    } catch (error) {
        return null;
    }
}

function isDarkTheme() {
    return document.documentElement.classList.contains('dark');
}

function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.dispatchEvent(new CustomEvent('documentation-editor:theme-change', { detail: { theme } }));
}

function toggleTheme() {
    const nextTheme = isDarkTheme() ? 'light' : 'dark';
    try {
        localStorage.setItem(THEME_STORAGE_KEY, nextTheme);
    } catch (error) {
        // Storage unavailable; the choice lasts for this page only.
    }
    applyTheme(nextTheme);
}

systemDarkQuery.addEventListener('change', (event) => {
    if (readStoredTheme() === null) {
        applyTheme(event.matches ? 'dark' : 'light');
    }
});

/**
 * Sidebar
 * Off-canvas toggle on small screens and client-side filtering of the document list.
 */
function initSidebar() {
    const sidebar = document.querySelector('[data-sidebar]');
    if (!sidebar) {
        return;
    }

    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const setSidebarOpen = (isOpen) => {
        sidebar.classList.toggle('-translate-x-full', !isOpen);
        backdrop?.classList.toggle('hidden', !isOpen);
    };

    document.querySelectorAll('[data-sidebar-open]').forEach((button) => {
        button.addEventListener('click', () => setSidebarOpen(true));
    });
    document.querySelectorAll('[data-sidebar-close]').forEach((button) => {
        button.addEventListener('click', () => setSidebarOpen(false));
    });
    backdrop?.addEventListener('click', () => setSidebarOpen(false));

    const filterInput = sidebar.querySelector('[data-nav-filter]');
    if (!filterInput) {
        return;
    }

    filterInput.addEventListener('input', () => filterNavigation(sidebar, filterInput.value));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setSidebarOpen(false);
        }

        const isTyping = ['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)
            || document.activeElement?.isContentEditable;
        if (event.key === '/' && !isTyping) {
            event.preventDefault();
            setSidebarOpen(true);
            filterInput.focus();
        }
    });
}

function filterNavigation(sidebar, rawSearchTerm) {
    const searchTerm = rawSearchTerm.trim().toLowerCase();
    const matches = (element) => element.textContent.toLowerCase().includes(searchTerm);
    let visibleEntryCount = 0;

    sidebar.querySelectorAll('[data-nav-entry]').forEach((entry) => {
        const group = entry.querySelector('details');

        if (!group) {
            const isVisible = matches(entry);
            entry.classList.toggle('hidden', !isVisible);
            visibleEntryCount += isVisible ? 1 : 0;
            return;
        }

        const groupNameMatches = matches(group.querySelector('summary'));
        let visibleChildCount = 0;

        group.querySelectorAll('[data-nav-child]').forEach((child) => {
            const isVisible = groupNameMatches || matches(child);
            child.classList.toggle('hidden', !isVisible);
            visibleChildCount += isVisible ? 1 : 0;
        });

        entry.classList.toggle('hidden', visibleChildCount === 0);
        group.open = searchTerm ? visibleChildCount > 0 : group.hasAttribute('data-active-group');
        visibleEntryCount += visibleChildCount > 0 ? 1 : 0;
    });

    sidebar.querySelector('[data-nav-empty]')?.classList.toggle('hidden', visibleEntryCount > 0);
}

/**
 * Pane tabs
 * Switches between the editor and changes panes on small screens.
 */
function initPaneTabs() {
    const tabs = document.querySelectorAll('[data-pane-tab]');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((otherTab) => {
                const isSelected = otherTab === tab;
                otherTab.setAttribute('aria-selected', String(isSelected));
                document.querySelector(`[data-pane="${otherTab.dataset.paneTab}"]`)?.classList.toggle('hidden', !isSelected);
            });
        });
    });
}

function initPage() {
    window.createIcons();
    initSidebar();
    initPaneTabs();
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', toggleTheme);
    });
}

// Works whether the script is loaded in the head or after the page has parsed
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPage);
} else {
    initPage();
}

/**
 * Document Editor
 * Manages the editor functionality for the document manager
 */

// State variables for the editor
let state = {
    originalContent: '',
    currentContent: '',
    editor: null,
    diffPreviewElement: null,
    contentHeight: 0,
    isSubmitting: false,
    filePath: '',
    folderName: '',
    baseSha: null,
    indexUrl: '',
};

/**
 * Initialize the editor with the given content
 * @param {Object} options - Configuration options
 */
function initEditor(options = {}) {
    // Set up state from options
    state.originalContent = options.content || '';
    state.currentContent = options.content || '';
    state.filePath = options.filePath || '';
    state.folderName = options.folderName || '';
    state.baseSha = options.baseSha || null;
    state.indexUrl = options.indexUrl;
    state.diffPreviewElement = document.getElementById('diffPreview');

    // Initialize Toast UI Editor
    // Check if Toast UI Editor is available and use correct reference
    const ToastEditor = window.toastui?.Editor || window.Editor;
    state.editor = new ToastEditor({
        el: document.getElementById('editor'),
        initialValue: state.currentContent,
        previewStyle: 'tab',
        height: '100%',
        theme: isDarkTheme() ? 'dark' : 'light',
        usageStatistics: false,
        viewer: false,
        hideModeSwitch: true,
        toolbarVisible: true,
        toolbarItems: [
            ['heading', 'bold', 'italic', 'strike'],
            ['hr', 'quote'],
            ['ul', 'ol', 'task'],
            ['link', 'code', 'codeblock']
        ],
        events: {
            change: () => {
                state.currentContent = state.editor.getMarkdown();
                updateDiffPreview();
                calculateContentHeight();
            }
        }
    });

    updateDiffPreview();
    calculateContentHeight();

    // The rendered preview gets the full width; the diff only matters while writing
    state.editor.on('changePreviewTabPreview', () => setChangesPaneVisible(false));
    state.editor.on('changePreviewTabWrite', () => setChangesPaneVisible(true));

    document.addEventListener('documentation-editor:theme-change', (event) => {
        document.querySelector('#editor .toastui-editor-defaultUI')
            ?.classList.toggle('toastui-editor-dark', event.detail.theme === 'dark');
    });

    window.addEventListener('beforeunload', (event) => {
        if (hasUnsavedChanges() && !state.isSubmitting) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
}

/**
 * Show or hide the changes pane on wide screens
 * @param {boolean} isVisible - Whether the changes pane should be shown
 */
function setChangesPaneVisible(isVisible) {
    document.querySelector('[data-editor-layout]')?.classList.toggle('md:grid-cols-2', isVisible);
    document.querySelector('[data-pane="changes"]')?.classList.toggle('md:flex', isVisible);
}

/**
 * URL of the document being edited, falling back to the documentation home
 * @returns {string}
 */
function documentUrl() {
    if (!state.filePath || !state.folderName) {
        return state.indexUrl;
    }

    const url = new URL(state.indexUrl, window.location.origin);
    url.searchParams.set('folderName', state.folderName);
    url.searchParams.set('filePath', state.filePath);

    return url.toString();
}

function hasUnsavedChanges() {
    return state.currentContent !== state.originalContent;
}

/**
 * Helper function to define minimum editor height
 * @returns {string} Minimum height in pixels
 */
function defineMinHeight() {
    calculateContentHeight();
    return state.contentHeight < 300 ? '300px' : '800px';
}

/**
 * Calculate content height based on number of lines
 */
function calculateContentHeight() {
    const lines = state.currentContent.split('\n').length;
    state.contentHeight = lines * 24; // Approximate line height
}

/**
 * Update the diff preview comparing original and current content
 */
function updateDiffPreview() {
    const original = state.originalContent.trim();
    const current = state.currentContent.trim();

    const diffParts = window.Diff.diffLines(original, current);
    let addedLineCount = 0;
    let removedLineCount = 0;

    const rows = diffParts.flatMap((part) => {
        const kind = part.added ? 'added' : (part.removed ? 'removed' : 'context');
        const partLines = part.value.replace(/\n$/, '').split('\n');

        if (kind === 'added') {
            addedLineCount += partLines.length;
        } else if (kind === 'removed') {
            removedLineCount += partLines.length;
        }

        return partLines.map((line) => renderDiffRow(kind, line));
    });

    updateChangeIndicators(addedLineCount, removedLineCount);

    if (addedLineCount === 0 && removedLineCount === 0) {
        state.diffPreviewElement.innerHTML = `
            <div class="flex h-full flex-col items-center justify-center px-6 py-20 text-center">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">No changes yet</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Edits you make will show up here as a line-by-line diff.</p>
            </div>`;
        return;
    }

    state.diffPreviewElement.innerHTML = `<div class="py-3">${rows.join('')}</div>`;
}

const diffRowStyles = {
    added: {
        marker: '+',
        row: 'bg-emerald-50 text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200',
        gutter: 'text-emerald-600 dark:text-emerald-400',
    },
    removed: {
        marker: '-',
        row: 'bg-rose-50 text-rose-900 dark:bg-rose-500/10 dark:text-rose-200',
        gutter: 'text-rose-600 dark:text-rose-400',
    },
    context: {
        marker: '',
        row: 'text-slate-500 dark:text-slate-400',
        gutter: '',
    },
};

/**
 * Render a single diff line
 * @param {'added'|'removed'|'context'} kind - Type of change
 * @param {string} line - Raw line content
 * @returns {string} - Row HTML
 */
function renderDiffRow(kind, line) {
    const style = diffRowStyles[kind];

    return `<div class="flex font-mono text-[13px] leading-6 ${style.row}">`
        + `<span class="w-8 shrink-0 select-none text-center ${style.gutter}">${style.marker}</span>`
        + `<span class="min-w-0 whitespace-pre-wrap break-words pr-4">${escapeHtml(line) || ' '}</span>`
        + `</div>`;
}

/**
 * Reflect the current diff in the header status and the changes pane
 * @param {number} addedLineCount - Lines added
 * @param {number} removedLineCount - Lines removed
 */
function updateChangeIndicators(addedLineCount, removedLineCount) {
    const isDirty = hasUnsavedChanges();
    const status = document.querySelector('[data-change-status]');

    if (status) {
        status.dataset.dirty = String(isDirty);
        status.querySelector('[data-change-status-label]').textContent = isDirty ? 'Unsaved changes' : 'No changes';
    }

    const stats = document.querySelector('[data-diff-stats]');
    if (stats) {
        stats.innerHTML = isDirty
            ? `<span class="text-emerald-600 dark:text-emerald-400">+${addedLineCount}</span> `
                + `<span class="text-rose-600 dark:text-rose-400">-${removedLineCount}</span>`
            : '';
    }
}

/**
 * Helper function to escape HTML
 * @param {string} input - Raw string to escape
 * @returns {string} - HTML-escaped string
 */
function escapeHtml(input) {
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
}

// Custom styling for SweetAlert
const customSwalTheme = window.Swal.mixin({
    customClass: {
        container: 'font-sans',
        popup: 'rounded-xl shadow-xl border border-slate-200 dark:border-slate-800',
        title: 'text-lg font-semibold text-slate-900 pb-2',
        htmlContainer: 'text-slate-600 text-sm',
        confirmButton: 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200',
        cancelButton: 'px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors duration-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200',
        actions: 'gap-2 pt-4'
    },
    buttonsStyling: false
});

const Toast = window.Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    customClass: {
        popup: 'rounded-lg shadow-lg border border-slate-200 font-sans dark:border-slate-800',
        title: 'text-slate-700 text-sm',
        timerProgressBar: 'bg-blue-600'
    },
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    }
});

/**
 * Save changes to the server
 */
async function saveChanges(updateRoute) {
    if (state.currentContent === state.originalContent) {
        Toast.fire({
            icon: "info",
            title: "Please update the documentation before submitting."
        });
        return;
    }

    const result = await customSwalTheme.fire({
        title: 'Save Changes?',
        text: 'Are you sure you want to save these changes?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, save changes',
        cancelButtonText: 'Cancel',
        didOpen: () => {
            createIcons();
        }
    });

    if (!result.isConfirmed) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    state.isSubmitting = true;

    // Show the loading spinner
    customSwalTheme.fire({
        title: 'Saving changes...',
        html: 'Please wait while we save your changes',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        // Using fetch API with the appropriate route
        const response = await fetch(updateRoute, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                content: state.currentContent,
                filePath: state.filePath,
                folderName: state.folderName,
                baseSha: state.baseSha
            })
        });

        // Close the loading spinner
        Swal.close();

        const data = await response.json();

        if (response.ok) {
            // Wait for the user to close the dialog; redirecting right away hid the confirmation
            const pullRequestLink = data.pr_url
                ? `<a href="${escapeHtml(data.pr_url)}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1.5 font-medium text-blue-600 hover:underline dark:text-blue-400">View pull request &rarr;</a>`
                : '';

            const confirmation = data.pr_url
                ? 'Your changes have been submitted as a pull request for review.'
                : 'Your changes have been submitted.';

            await customSwalTheme.fire({
                icon: 'success',
                // titleText renders as plain text; title would render the server message as HTML
                titleText: data.message || 'Pull request created',
                html: `<p>${confirmation}</p>${pullRequestLink}`,
                confirmButtonText: 'Back to document',
                allowOutsideClick: false,
            });

            window.location.href = documentUrl();
        } else if (response.status === 409) {
            // The base branch moved on; keep the user's edits on screen so they can copy them
            state.isSubmitting = false;
            customSwalTheme.fire({
                icon: 'warning',
                title: 'Document changed',
                text: data.message,
            });
        } else {
            throw new Error(data.message || 'Failed to save changes');
        }
    } catch (error) {
        // Close the loading spinner in case of error
        Swal.close();

        state.isSubmitting = false;
        Toast.fire({
            icon: 'error',
            title: 'Error saving changes',
            text: error.message || 'An error occurred while saving changes'
        });
    }
}

/**
 * Clean up when needed
 */
function destroyEditor() {
    if (state.editor) {
        state.editor.destroy();
    }
}

/**
 * Helper function to make a fetch request with a loading indicator
 * @param {string} url - The URL to fetch
 * @param {Object} options - Fetch options
 * @param {string} loadingMessage - Message to show while loading
 * @returns {Promise} - Fetch response
 */
async function fetchWithLoader(url, options = {}, loadingMessage = 'Loading...') {
    // Show the loading spinner
    customSwalTheme.fire({
        title: loadingMessage,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(url, options);
        Swal.close();
        return response;
    } catch (error) {
        Swal.close();
        throw error;
    }
}

// Export functions to global scope
window.DocumentEditor = {
    init: initEditor,
    saveChanges: saveChanges,
    destroy: destroyEditor,
    fetchWithLoader: fetchWithLoader
};
