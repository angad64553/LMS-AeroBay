(function() {
    var selectedImage = null;
    var overlay = null;
    var startX = 0;
    var startY = 0;
    var startWidth = 0;
    var startHeight = 0;
    var activeHandle = '';

    function addStyles() {
        if (document.getElementById('moove-editor-image-resize-style')) {
            return;
        }

        var style = document.createElement('style');
        style.id = 'moove-editor-image-resize-style';
        style.textContent = ''
            + '.moove-image-resize-overlay {'
            + 'position:absolute; border:2px solid #0f6fff; pointer-events:none; z-index:99999;'
            + 'box-sizing:border-box;'
            + '}'
            + '.moove-image-resize-handle {'
            + 'position:absolute; width:12px; height:12px; background:#fff; border:2px solid #0f6fff;'
            + 'border-radius:2px; box-sizing:border-box; pointer-events:auto;'
            + '}'
            + '.moove-image-resize-handle[data-handle="se"] { right:-7px; bottom:-7px; cursor:nwse-resize; }'
            + '.moove-image-resize-handle[data-handle="sw"] { left:-7px; bottom:-7px; cursor:nesw-resize; }'
            + '.moove-image-resize-handle[data-handle="ne"] { right:-7px; top:-7px; cursor:nesw-resize; }'
            + '.moove-image-resize-handle[data-handle="nw"] { left:-7px; top:-7px; cursor:nwse-resize; }';
        document.head.appendChild(style);
    }

    function isEditableImage(target) {
        if (!target || target.tagName !== 'IMG') {
            return false;
        }

        return !!target.closest('[contenteditable="true"], .editor_atto_content, .tox-edit-area');
    }

    function createOverlay() {
        if (overlay) {
            return overlay;
        }

        overlay = document.createElement('div');
        overlay.className = 'moove-image-resize-overlay';

        ['nw', 'ne', 'sw', 'se'].forEach(function(handle) {
            var element = document.createElement('span');
            element.className = 'moove-image-resize-handle';
            element.setAttribute('data-handle', handle);
            overlay.appendChild(element);
        });

        document.body.appendChild(overlay);
        return overlay;
    }

    function updateOverlay() {
        if (!selectedImage || !overlay) {
            return;
        }

        var rect = selectedImage.getBoundingClientRect();
        overlay.style.left = (rect.left + window.scrollX) + 'px';
        overlay.style.top = (rect.top + window.scrollY) + 'px';
        overlay.style.width = rect.width + 'px';
        overlay.style.height = rect.height + 'px';
        overlay.style.display = 'block';
    }

    function hideOverlay() {
        selectedImage = null;
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    function selectImage(image) {
        selectedImage = image;
        createOverlay();
        updateOverlay();
    }

    function getEditorTextarea() {
        if (!selectedImage) {
            return null;
        }

        var editor = selectedImage.closest('[contenteditable="true"]');
        if (!editor || !editor.id) {
            return null;
        }

        return document.getElementById(editor.id.replace(/editable$/, ''));
    }

    function syncTextarea() {
        var textarea = getEditorTextarea();
        var editor = selectedImage ? selectedImage.closest('[contenteditable="true"]') : null;

        if (textarea && editor) {
            textarea.value = editor.innerHTML;
            textarea.dispatchEvent(new Event('change', {bubbles: true}));
        }
    }

    function startResize(event) {
        if (!selectedImage) {
            return;
        }

        activeHandle = event.target.getAttribute('data-handle');
        startX = event.clientX;
        startY = event.clientY;
        startWidth = selectedImage.getBoundingClientRect().width;
        startHeight = selectedImage.getBoundingClientRect().height;

        event.preventDefault();
        document.addEventListener('mousemove', resizeImage);
        document.addEventListener('mouseup', stopResize);
    }

    function resizeImage(event) {
        if (!selectedImage) {
            return;
        }

        var deltaX = event.clientX - startX;
        var deltaY = event.clientY - startY;
        var width = startWidth;
        var height = startHeight;
        var ratio = startHeight / startWidth;

        if (activeHandle.indexOf('e') !== -1) {
            width = startWidth + deltaX;
        } else if (activeHandle.indexOf('w') !== -1) {
            width = startWidth - deltaX;
        }

        if (event.shiftKey) {
            if (activeHandle.indexOf('s') !== -1) {
                height = startHeight + deltaY;
                width = height / ratio;
            } else if (activeHandle.indexOf('n') !== -1) {
                height = startHeight - deltaY;
                width = height / ratio;
            }
        } else {
            height = width * ratio;
        }

        width = Math.max(40, Math.round(width));
        height = Math.max(30, Math.round(height));

        selectedImage.style.width = width + 'px';
        selectedImage.style.height = height + 'px';
        selectedImage.setAttribute('width', width);
        selectedImage.setAttribute('height', height);
        updateOverlay();
        syncTextarea();
    }

    function stopResize() {
        document.removeEventListener('mousemove', resizeImage);
        document.removeEventListener('mouseup', stopResize);
        syncTextarea();
    }

    function init() {
        addStyles();

        document.addEventListener('click', function(event) {
            if (isEditableImage(event.target)) {
                selectImage(event.target);
                event.preventDefault();
                return;
            }

            if (overlay && overlay.contains(event.target)) {
                return;
            }

            hideOverlay();
        }, true);

        document.addEventListener('mousedown', function(event) {
            if (event.target.classList.contains('moove-image-resize-handle')) {
                startResize(event);
            }
        });

        window.addEventListener('scroll', updateOverlay, true);
        window.addEventListener('resize', updateOverlay);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
