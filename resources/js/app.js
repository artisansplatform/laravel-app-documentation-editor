// Import styles
import '../css/app.scss';
import '@toast-ui/editor/dist/toastui-editor.css';
import * as Diff from 'diff';
import Editor from '@toast-ui/editor';
import 'bootstrap';
import Swal from 'sweetalert2';

// Make libraries available globally
window.Diff = Diff;
window.Editor = Editor;
window.Swal = Swal;

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
            return `<div class="diff-line diff-added">+ ${escaped}</div>`;
        }
        if (part.removed) {
            return `<div class="diff-line diff-removed">- ${escaped}</div>`;
        }
        return `<div class="diff-line diff-context">  ${escaped}</div>`;
    }).join('');

    // Set diff preview with themed container
    state.diffPreviewElement.innerHTML = `<div class="p-4 border rounded overflow-x-auto" style="background-color: var(--editor-content-bg); color: var(--editor-text); border-color: var(--editor-border);">${lines}</div>`;
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

const Toast = window.Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
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

    const result = await Swal.fire({
        title: 'Save Changes?',
        text: 'Are you sure you want to save these changes?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, save changes',
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    state.isSubmitting = true;
    
    // Show the loading spinner
    Swal.fire({
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
    Swal.fire({
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
