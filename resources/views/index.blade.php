<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Manager - Dynamic Documentation</title>

    {{ Artisansplatform\LaravelAppDocumentationEditor\LaravelAppDocumentationEditor::css() }}
    {{ Artisansplatform\LaravelAppDocumentationEditor\LaravelAppDocumentationEditor::js() }}

</head>
<body>
    <div class="glass-bg"></div>

    <div class="container-fluid">
        <div class="editor-header">
            <div class="d-flex align-items-center">
                <h1>Document Manager</h1>
            </div>
        </div>

        <div class="row g-0">
            <!-- Left Navigation Column -->
            <div class="col-md-3 col-lg-3">
                <div class="left-navbar">
                    <div class="navbar-header">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-link d-md-none ms-auto" id="mobile-menu-close" aria-label="Close menu">
                                <i class="fas fa-times" style="color: #0d6efd; font-size: 1.25rem;"></i>
                            </button>
                        </div>
                    </div>

                    <div class="accordion accordion-flush" id="documentationAccordion">
                        @forelse($directories as $folder => $files)
                            <div class="accordion-item">
                                @if(count($files) === 1)
                                    <!-- Single file folder - direct link -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <a
                                                class="accordion-button single-file-folder"
                                                href="{{ route('laravel-app-documentation-editor.index', ['folderName' => $folder, 'filePath' => $files[0]['file_path']]) }}"
                                            >
                                                <i class="fas fa-file-alt me-2 text-primary"></i>
                                                {{ $folder }}
                                            </a>
                                        </h2>
                                    </div>
                                @else
                                    <!-- Multi-file folder - collapsible -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading{{ $loop->index }}">
                                            <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapse{{ $loop->index }}"
                                                    aria-expanded="false" aria-controls="collapse{{ $loop->index }}">
                                                <i class="fas fa-folder folder-icon me-2"></i>
                                                {{ $folder }}
                                            </button>
                                        </h2>
                                        <div id="collapse{{ $loop->index }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $loop->index }}"
                                            data-bs-parent="#documentationAccordion">
                                            <div class="accordion-body">
                                                @foreach($files as $file)
                                                    <a href="{{ route('laravel-app-documentation-editor.index', ['folderName' => $file['file_name'], 'filePath' => $file['file_path']]) }}"
                                                        class="file-item"
                                                    >
                                                        <i class="fas fa-file-alt me-2"></i>
                                                        {{ $file['file_name'] }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="p-3 text-center">
                                <p class="text-muted">No documentation found</p>
                            </div>
                        @endforelse

                        <!-- Empty state (when no docs found) -->
                        <div class="accordion-item d-none" id="no-docs">
                            <div class="p-3 text-center">
                                <p class="text-muted">No documentation found</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Column -->
            <div class="col-md-9 col-lg-9">
                <div class="main-content" id="content-area">
                    <div class="container-fluid fade-in py-4">
                        <!-- Mobile menu button will be inserted here by JavaScript -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card p-4 mb-5 border shadow">
                                    <div class="card-header d-flex align-items-center justify-content-between border-bottom pb-3">
                                        <div>
                                            <i class="fas fa-book-open me-2" style="color: #0d6efd;"></i>
                                            <h1 class="h3 mb-0"> {{ $title }} </h1>
                                        </div>

                                        <div>
                                            @if ($hasEditAccess && request()->has('filePath') && request()->has('folderName'))
                                                <div>
                                                    <a href="{{ route('laravel-app-documentation-editor.edit') }}?filePath={{ request()->string('filePath') }}&folderName={{ request()->string('folderName') }}"
                                                        class="btn btn-sm btn-primary">
                                                        Edit Document
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <div class="document-content prose">
                                            {!! $content ?? '<p class="text-muted">Select a file from the sidebar to view its documentation</p>' !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button class="mobile-menu-btn" id="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize floating elements
            const floatingElements = document.querySelectorAll('.floating-element');

            floatingElements.forEach((element, index) => {
                // Random starting position
                const randomLeft = Math.random() * 100;
                element.style.left = randomLeft + 'vw';

                // Random animation duration between 15s and 30s
                const duration = 15 + Math.random() * 15;
                element.style.animationDuration = duration + 's';

                // Random animation delay
                const delay = Math.random() * 10;
                element.style.animationDelay = delay + 's';
            });

            // Add mobile menu toggle button at the top of the content area for small screens
            const mainContent = document.querySelector('.main-content');
            const mobileMenuButton = document.createElement('button');
            mobileMenuButton.id = 'mobile-menu-toggle';
            mobileMenuButton.className = 'btn btn-sm btn-outline-primary d-md-none mb-3';
            mobileMenuButton.innerHTML = '<i class="fas fa-bars me-2"></i>Menu';

            if (window.innerWidth <= 768) {
                mainContent.insertBefore(mobileMenuButton, mainContent.firstChild);
            }

            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mobileMenuClose = document.getElementById('mobile-menu-close');
            const leftNavbar = document.querySelector('.left-navbar');

            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    leftNavbar.classList.add('active');
                });
            }

            if (mobileMenuClose) {
                mobileMenuClose.addEventListener('click', function() {
                    leftNavbar.classList.remove('active');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const isClickInsideNavbar = leftNavbar.contains(event.target);
                const isClickOnToggle = mobileMenuToggle && mobileMenuToggle.contains(event.target);

                if (window.innerWidth <= 768 && !isClickInsideNavbar && !isClickOnToggle && leftNavbar.classList.contains('active')) {
                    leftNavbar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
