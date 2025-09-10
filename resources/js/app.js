// Import styles
import '../css/app.css';
import '@toast-ui/editor/dist/toastui-editor.css';
import * as Diff from 'diff';
import Editor from '@toast-ui/editor';
import Swal from 'sweetalert2';
import * as lucide from 'lucide';
import { BookOpen, FileText, Folder, File, Save, Menu, Edit2, Edit3, Eye, ArrowLeft } from 'lucide';

// Make libraries available globally
window.Diff = Diff;
window.Editor = Editor;
window.Swal = Swal;

// Configure Lucide icons
const icons = { BookOpen, FileText, Folder, File, Save, Menu, Edit2, Edit3, Eye, ArrowLeft };
window.createIcons = () => {
    lucide.createIcons({
        icons: icons
    });
};

// Initialize Lucide icons
window.createIcons();

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
    state.indexUrl = options.indexUrl;
    state.diffPreviewElement = document.getElementById('diffPreview');

    // Initialize Toast UI Editor
    // Check if Toast UI Editor is available and use correct reference
    const ToastEditor = window.toastui?.Editor || window.Editor;
    state.editor = new ToastEditor({
        el: document.getElementById('editor'),
        initialValue: state.currentContent,
        previewStyle: 'none',
        height: '100%',
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

    // Use the Diff library with fallback
    const diffLib = window.Diff;
    const diff = diffLib.diffLines(original, current);

    const lines = diff.map(part => {
        const escaped = escapeHtml(part.value);
        if (part.added) {
            return `<div class="diff-line py-1 px-2 bg-green-50 text-green-700 font-mono text-sm">+ ${escaped}</div>`;
        }
        if (part.removed) {
            return `<div class="diff-line py-1 px-2 bg-red-50 text-red-700 font-mono text-sm">- ${escaped}</div>`;
        }
        return `<div class="diff-line py-1 px-2 text-gray-600 font-mono text-sm">  ${escaped}</div>`;
    }).join('');

    // Set diff preview with themed container
    state.diffPreviewElement.innerHTML = `<div class="p-4 rounded-lg overflow-x-auto bg-gray-50/50">${lines}</div>`;
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
        container: 'font-[Inter]',
        popup: 'bg-white rounded-xl shadow-lg border border-gray-200',
        title: 'text-lg font-semibold text-gray-800 pb-2',
        htmlContainer: 'text-gray-600 text-sm',
        confirmButton: 'px-4 py-2 bg-[#0F6FDE] hover:bg-[#0C5CBD] text-white text-sm font-semibold rounded-lg transition-colors duration-200',
        cancelButton: 'px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition-colors duration-200',
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
        popup: 'bg-white rounded-lg shadow-md border border-gray-200 font-[Inter]',
        title: 'text-gray-700 text-sm',
        timerProgressBar: 'bg-[#0F6FDE]'
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
                folderName: state.folderName
            })
        });

        // Close the loading spinner
        Swal.close();

        const data = await response.json();

        if (response.ok) {
            Toast.fire({
                icon: "success",
                title: data.message
            });
            window.location.href = state.indexUrl;
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
